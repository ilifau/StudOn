<?php

namespace FAU\Ilias;

use ILIAS\DI\Container;
use ilObjCourse;
use FAU\Study\Data\ImportId;
use ilConditionHandler;
use ilChangeEvent;
use ilObjGroup;
use ilObject;
use ilObjRole;
use ilFAUAppEventListener;
use ilParticipants;
use FAU\Study\Data\Term;
use ilWaitingList;

class Transfer
{
    const FORMER_COURSE_MEMBER = 'Vorheriges Kursmitglied';
    const FORMER_GROUP_MEMBER = 'Vorheriges Gruppenmitglied';

    protected Container $dic;

    public function __construct(Container $dic)
    {
        $this->dic = $dic;
    }

    /**
     * Move the campo connection from one course to another
     */
    public function moveCampoConnection(
        ilObjCourse $source,
        ilObjCourse $target,
        bool $update_title,
        bool $move_old_members,
        bool $delete_source,
        array $assign_groups,
        bool $update_group_titles
    )
    {
        // deactivate event listener to avoid messages to campo
        $listener_active = ilFAUAppEventListener::getInstance()->isActive();
        ilFAUAppEventListener::getInstance()->setActive(false);

        $importId = ImportId::fromString($source->getImportId());
        $target->setImportId($importId->toString());
        if ($update_title) {
            $target->setTitle($source->getTitle());
            $target->setDescription(($source->getDescription()));
        }
        $target->update();

        if ($move_old_members) {
            $this->changeMembersRole($target->getRefId());
        }
        $this->moveCourseParticipants($source, $target);

        if ($source->hasParallelGroups()) {
            if (!empty($id = $this->dic->fau()->tools()->settings()->getCourseDidacticTemplateId())) {
                $target->applyDidacticTemplate($id);
            }
            foreach ($this->dic->fau()->ilias()->objects()->findChildParallelGroups($source->getRefId()) as $ref_id) {
                if (isset($assign_groups[$ref_id])) {
                    $this->reassignParallelGroup($ref_id, $assign_groups[$ref_id], $update_group_titles, $move_old_members);
                }
                else {
                    $this->moveObject($ref_id, $target->getRefId());
                }
            }
        }
        else {
            foreach ($this->dic->fau()->study()->repo()->getCoursesByIliasObjId($source->getId()) as $course) {
                $course = $course->withIliasObjId($target->getId())->withIliasProblem(null);
                $this->dic->fau()->study()->repo()->save($course);
            }
        }

        $importId = ImportId::fromString($source->getImportId());
        $target->setImportId($importId->toString());
        $target->update();

        $source->setImportId(null);
        $source->setOfflineStatus(true);
        $source->setDescription($this->dic->language()->txt('fau_transfer_source_desc'));
        $source->update();

        if ($delete_source) {
            $source->delete();
        }


        ilFAUAppEventListener::getInstance()->setActive($listener_active);
    }

    /**
     * Split a course with parallel groups to separate courses
     */
    public function splitCampoCourse(ilObjCourse $parent, bool $delete_parent) : bool
    {
        if (!$parent->hasParallelGroups()) {
            return false;
        }

        // deactivate event listener to avoid messages to campo
        $listener_active = ilFAUAppEventListener::getInstance()->isActive();
        ilFAUAppEventListener::getInstance()->setActive(false);

        $cat_ref_id = $this->dic->repositoryTree()->getParentId($parent->getRefId());

        foreach ($this->dic->fau()->ilias()->objects()->findChildParallelGroups($parent->getRefId()) as $ref_id) {
            $source = new ilObjGroup($ref_id);

            $import_id = ImportId::fromString($source->getImportId());
            $term = Term::fromString($import_id->getTermId());
            $event = $this->dic->fau()->study()->repo()->getEvent($import_id->getEventId());
            $course = $this->dic->fau()->study()->repo()->getCourse($import_id->getCourseId());

            $target = $this->dic->fau()->ilias()->objects()->createIliasCourse($cat_ref_id, $term, $event, $course);
            $this->changeParallelGroupToCourse($parent, $source, $target);
            $this->dic->fau()->sync()->roles()->updateRolesInIliasObject($target->getRefId(), null, $course->getCourseId(), $event->getEventId(), $term);

            $this->dic->fau()->study()->repo()->save($course->withIliasObjId($target->getId()));
            $source->setImportId(null);
            $parent->setDescription($this->dic->language()->txt('fau_transfer_source_desc'));
            $source->update();
        }

        $parent->setImportId(null);
        $parent->setOfflineStatus(true);
        $parent->setDescription($this->dic->language()->txt('fau_transfer_source_desc'));
        $parent->setSubscriptionType(IL_CRS_SUBSCRIPTION_DEACTIVATED);
        $parent->setSubscriptionLimitationType(IL_CRS_SUBSCRIPTION_DEACTIVATED);
        $parent->update();

        if ($delete_parent) {
            $parent->delete();
        }

        ilFAUAppEventListener::getInstance()->setActive($listener_active);
        return true;
    }

    /**
     * Change an ilias course to a course with enclosed parallel grup
     * @return int  ref_id of the created nested group
     */
    public function changeCampoCourseToNested(ilObjCourse $source) : int
    {
        // deactivate event listener to avoid messages to campo
        $listener_active = ilFAUAppEventListener::getInstance()->isActive();
        ilFAUAppEventListener::getInstance()->setActive(false);

        $import_id = ImportId::fromString($source->getImportId());
        $term = Term::fromString($import_id->getTermId());

        $course = $this->dic->fau()->study()->repo()->getCourse($import_id->getCourseId());
        $event = $this->dic ->fau()->study()->repo()->getEvent($import_id->getEventId());

        if (!isset($course) || !isset($event)) {
            return false;
        }

        // create group in course with needed settings
        $target = $this->dic->fau()->ilias()->objects()->createIliasGroup($source->getRefId(), $term, $event, $course);
        $target->setImportId($import_id->toString());
        $target->setOwner($source->getOwner());
        $target->setTitle($this->dic->fau()->ilias()->objects()->buildTitle($term, $event, $course));
        $target->setDescription($this->dic->fau()->ilias()->objects()->buildDescription($event, $course));
        $target->enableMembershipLimitation($source->isSubscriptionMembershipLimited());
        $target->setMaxMembers($source->getSubscriptionMaxMembers());
        $target->setMinMembers($source->getSubscriptionMinMembers());
        $target->update();

        // change the course to a parent course for parallel groups
        $import_id = $import_id->withCourseId(null);
        $source->setImportId($import_id->toString());
        $source->setTitle($this->dic->fau()->ilias()->objects()->buildTitle($term, $event, null));
        $source->setDescription($this->dic->fau()->ilias()->objects()->buildDescription($event, null));
        $source->enableSubscriptionMembershipLimitation(false);
        $source->setSubscriptionMaxMembers(null);
        $source->setSubscriptionMinMembers(null);
        $source->update();
        $source->applyDidacticTemplate($this->dic->fau()->tools()->settings()->getCourseDidacticTemplateId());

        // save the new course relation
        $this->dic->fau()->study()->repo()->save($course->withIliasObjId($target->id));

        $this->addCourseParticipantsToGroup($source, $target);
        $this->dic->fau()->ilias()->repo()->copyWaitingList($source->getId(), $target->getId());

        ilFAUAppEventListener::getInstance()->setActive($listener_active);
        return $target->getRefId();
    }

    /**
     * Solve conflicts with multipe campo connections (import ids)
     */
    public function solveCourseConflicts(ImportId $import_id, ilObjCourse $target)
    {
        // set the correct object id in the campo course
        $course = $this->dic->fau()->study()->repo()->getCourse($import_id->getCourseId());
        $this->dic->fau()->study()->repo()->save($course->withIliasObjId($target->getId()));

        foreach ($this->dic->fau()->study()->repo()->getObjectIdsWithImportId($import_id) as $obj_id) {
            if ($obj_id == $target->getId()) {
                continue;
            }

            if (ilObject::_lookupType($obj_id) == 'crs') {
                foreach (ilObject::_getAllReferences($obj_id) as $ref_id) {
                    if (!ilObject::_isInTrash($ref_id)) {
                        $source = new ilObjCourse($ref_id);
                        $this->moveParticipants($source->getMembersObject(), $target->getMembersObject(), IL_CRS_MEMBER, IL_CRS_MEMBER);
                        $this->dic->fau()->ilias()->repo()->copyWaitingList($source->getId(), $target->getId());
                        $this->dic->fau()->ilias()->repo()->clearWaitingList($source->getId());
                        $source->setImportId(null);
                        $source->setOfflineStatus(true);
                        $source->update();
                    }
                }
            }

            // other object types (should not happen)
            $this->dic->fau()->sync()->repo()->removeObjectFauImportId($obj_id);
        }
    }

    /**
     * Change a parallel group to a course
     */
    protected function changeParallelGroupToCourse(ilObjCourse $parent, ilObjGroup $source, ilObjCourse $target)
    {
        // take most settings from the parent course
        $target->cloneSettings($parent);

        // take specific settings from the group
        $target->setTitle($source->getTitle());
        $target->setDescription($source->getDescription());
        $target->setImportId($source->getImportId());
        $target->setOwner($source->getOwner());
        $target->setImportantInformation($source->getInformation());
        $target->enableSubscriptionMembershipLimitation($source->isMembershipLimited());
        $target->setSubscriptionMaxMembers($source->getMaxMembers());
        $target->setSubscriptionMinMembers($source->getMinMembers());
        $target->update();

        /** @noinspection PhpParamsInspection */
        foreach ($this->dic->repositoryTree()->getChildIds($source->getRefId()) as $child_id) {
            if (!ilObject::_isInTrash($child_id)) {
                $this->moveObject($child_id, $target->getRefId());
            }
        }

        $this->moveGroupToCourseParticipants($parent, $source, $target);
        $this->dic->fau()->ilias()->repo()->copyWaitingList($source->getId(), $target->getId());
        $this->dic->fau()->ilias()->repo()->clearWaitingList($source->getId());
    }


    /**
     * Move the assignment of a parallel group
     */
    protected function reassignParallelGroup(int $old_ref_id, int $new_ref_id, bool $update_title, bool $move_old_members)
    {
        $source = new ilObjGroup($old_ref_id, true);
        $target = new ilObjGroup($new_ref_id, true);

        if (!empty($id = $this->dic->fau()->tools()->settings()->getGroupDidacticTemplateId())) {
            $target->applyDidacticTemplate($id);
        }
        if ($update_title) {
            $target->setTitle($source->getTitle());
            $target->setDescription($source->getDescription());
        }
        $target->setImportId($source->getImportId());
        $target->setRegistrationStart(null);
        $target->setRegistrationEnd(null);
        $target->enableUnlimitedRegistration(true);
        $target->update();

        $source->setImportId(null);
        $source->setDescription($this->dic->language()->txt('fau_transfer_source_group_desc'));
        $source->update();

        foreach ($this->dic->fau()->study()->repo()->getCoursesByIliasObjId($source->getId()) as $course) {
            $course = $course->withIliasObjId($target->getId())->withIliasProblem(null);
            $this->dic->fau()->study()->repo()->save($course);
        }

        if ($move_old_members) {
            $this->changeMembersRole($target->getRefId());
        }

        $this->moveGroupParticipants($source, $target);
    }


    /**
     * Move the parallel groups from one course to another
     * @see ilContainerGUI::pasteObject() - CUT
     */
    protected function moveObject(int $ref_id, int $target_ref_id)
    {
        $tree = $this->dic->repositoryTree();

        $old_parent = $tree->getParentId($ref_id);
        $tree->moveTree($ref_id, $target_ref_id);
        $this->dic->rbac()->admin()->adjustMovedObjectPermissions($ref_id, $old_parent);

        ilConditionHandler::_adjustMovedObjectConditions($ref_id);

        $node_data = $tree->getNodeData($ref_id);
        $old_parent_data = $tree->getNodeData($old_parent);
        ilChangeEvent::_recordWriteEvent(
            $node_data['obj_id'],
            $this->dic->user()->getId(),
            'remove',
            $old_parent_data['obj_id']
        );
        ilChangeEvent::_recordWriteEvent(
            $node_data['obj_id'],
            $this->dic->user()->getId(),
            'add',
            ilObject::_lookupObjId($target_ref_id)
        );
        ilChangeEvent::_catchupWriteEvents($node_data['obj_id'], $this->dic->user()->getId());
    }


    /**
     * Move the course participants from one course to another
     */
    protected function moveCourseParticipants(ilObjCourse $source, ilObjCourse $target)
    {
        // do first to keep the module selection
        $this->dic->fau()->user()->repo()->moveMembers($source->getId(), $target->getId());

        $sourceMembers = $source->getMembersObject();
        $targetMembers = $target->getMembersObject();

        $this->moveParticipants($sourceMembers, $targetMembers, IL_CRS_ADMIN, IL_CRS_ADMIN);
        $this->moveParticipants($sourceMembers, $targetMembers, IL_CRS_TUTOR, IL_CRS_TUTOR);
        $this->moveParticipants($sourceMembers, $targetMembers, IL_CRS_MEMBER, IL_CRS_MEMBER);
    }

    /**
     * Move the group participants from one group to another
     */
    protected function moveGroupParticipants(ilObjGroup $source, ilObjGroup $target)
    {
        // do first to keep the module selection
        $this->dic->fau()->user()->repo()->moveMembers($source->getId(), $target->getId());

        $sourceMembers = $source->getMembersObject();
        $targetMembers = $target->getMembersObject();

        $this->moveParticipants($sourceMembers, $targetMembers, IL_GRP_ADMIN, IL_GRP_ADMIN);
        $this->moveParticipants($sourceMembers, $targetMembers, IL_GRP_MEMBER, IL_GRP_MEMBER);
    }


    /**
     * Move the group participants from group to a course
     */
    protected function moveGroupToCourseParticipants(ilObjCourse $parent, ilObjGroup $source, ilObjCourse $target)
    {
        // do first to keep the module selection
        $this->dic->fau()->user()->repo()->moveMembers($source->getId(), $target->getId());

        $parentMembers = $parent->getMembersObject();
        $sourceMembers = $source->getMembersObject();
        $targetMembers = $target->getMembersObject();

        $this->moveParticipants($parentMembers, $targetMembers, IL_CRS_ADMIN, IL_CRS_ADMIN);
        $this->moveParticipants($sourceMembers, $targetMembers, IL_GRP_ADMIN, IL_CRS_ADMIN);
        $this->moveParticipants($sourceMembers, $targetMembers, IL_GRP_MEMBER, IL_CRS_MEMBER);
    }

    /**
     * Move the group participants from group to a course
     */
    protected function addCourseParticipantsToGroup(ilObjCourse $source, ilObjGroup $target)
    {
        $sourceMembers = $source->getMembersObject();
        $targetMembers = $target->getMembersObject();


        // do first to keep the module selection
        $this->dic->fau()->user()->repo()->moveMembers($source->getId(), $target->getId());
        
        foreach ($this->dic->fau()->user()->repo()->getMembersOfObject($target->getId()) as $member) {
            // don't add the event responsibles to the group
            if ($member->isCourseResponsible() || $member->isInstructor() || $member->isIndividualInstructor()) {
                $targetMembers->add($member->getUserId(), IL_GRP_ADMIN);
            }
        }

        foreach($sourceMembers->getMembers() as $user_id) {
            $targetMembers->add($user_id, IL_GRP_MEMBER);
        }
    }


    /**
     * Move Participants from one object to another
     */
    protected function moveParticipants(ilParticipants $source, ilParticipants $target, int $source_role, int $target_role)
    {
        $ids = [];
        switch ($source_role) {
            case IL_CRS_ADMIN:
            case IL_GRP_ADMIN:
                $ids = $source->getAdmins();
                break;
            case IL_CRS_MEMBER:
            case IL_GRP_MEMBER:
                $ids = $source->getMembers();
                break;
            case IL_CRS_TUTOR:
                $ids = $source->getTutors();
                break;
        }
        foreach ($ids as $id) {
            if ($id != $this->dic->user()->getId()) {
                $source->delete($id);
            }
            $target->add($id, $target_role);
        }
    }



    /**
     * Move the members of a course to a "old member" role
     * @param int    $ref_id
     */
    protected function changeMembersRole(int $ref_id)
    {
        $type = ilObject::_lookupType($ref_id, true);
        switch ($type) {
            case 'crs':
                $former_role_title = self::FORMER_COURSE_MEMBER . ' (' . $ref_id . ')';
                $member_role_title = 'il_crs_member';
                break;

            case 'grp':
                $former_role_title = self::FORMER_GROUP_MEMBER . ' (' . $ref_id . ')';
                $member_role_title = 'il_grp_member';
                break;

            default:
                return;
        }

        $former_role_id = null;
        $member_role_id = null;
        foreach ($this->dic->rbac()->review()->getLocalRoles($ref_id) as $role_id) {
            $title = ilObject::_lookupTitle($role_id);
            if ($title == $former_role_title) {
                $former_role_id = $role_id;
            }
            if (substr($title, 0, strlen($member_role_title)) == $member_role_title) {
                $member_role_id = $role_id;
            }
        }

        // former role has to be added
        if (empty($former_role_id)) {
            $role = new ilObjRole();
            $role->setTitle($former_role_title);
            $role->create();
            $former_role_id = $role->getId();
            $this->dic->rbac()->admin()->assignRoleToFolder($former_role_id, $ref_id);

            // set default permissions of the new role
            if (!empty($member_role_id)) {
                $this->dic->rbac()->admin()->copyRoleTemplatePermissions($member_role_id, $ref_id, $ref_id, $former_role_id, true);
            }

            // set the actual permissions
            $ops = $this->dic->rbac()->review()->getOperationsOfRole($former_role_id, $type, $ref_id);
            $this->dic->rbac()->admin()->grantPermission($former_role_id, $ops, $ref_id);


            // change existing objects
            /** @noinspection PhpParamsInspection */
            $protected = $this->dic->rbac()->review()->isProtected($ref_id, $former_role_id);
            $role->changeExistingObjects(
                $ref_id,
                $protected ? ilObjRole::MODE_PROTECTED_DELETE_LOCAL_POLICIES : ilObjRole::MODE_UNPROTECTED_DELETE_LOCAL_POLICIES,
                array('all')
            );
        }

        // change the roles of the existing members
        $this->dic->fau()->ilias()->repo()->changeRoleAssignments($member_role_id, $former_role_id);
    }
}