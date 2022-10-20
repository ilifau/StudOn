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
        $this->dic->fau()->ilias()->repo()->moveWaitingList($source->getId(), $target->getId());
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
        $sourceMembers = $source->getMembersObject();
        $targetMembers = $target->getMembersObject();

        $this->moveParticipants($sourceMembers, $targetMembers, IL_CRS_ADMIN, IL_CRS_ADMIN);
        $this->moveParticipants($sourceMembers, $targetMembers, IL_CRS_TUTOR, IL_CRS_TUTOR);
        $this->moveParticipants($sourceMembers, $targetMembers, IL_CRS_MEMBER, IL_CRS_MEMBER);

        $this->dic->fau()->user()->repo()->moveMembers($source->getId(), $target->getId());
    }

    /**
     * Move the group participants from one group to another
     */
    protected function moveGroupParticipants(ilObjGroup $source, ilObjGroup $target)
    {
        $sourceMembers = $source->getMembersObject();
        $targetMembers = $target->getMembersObject();

        $this->moveParticipants($sourceMembers, $targetMembers, IL_GRP_ADMIN, IL_GRP_ADMIN);
        $this->moveParticipants($sourceMembers, $targetMembers, IL_GRP_MEMBER, IL_GRP_MEMBER);

        $this->dic->fau()->user()->repo()->moveMembers($source->getId(), $target->getId());
    }



    /**
     * Move the group participants from group to a course
     */
    protected function moveGroupToCourseParticipants(ilObjCourse $parent, ilObjGroup $source, ilObjCourse $target)
    {
        $parentMembers = $parent->getMembersObject();
        $sourceMembers = $source->getMembersObject();
        $targetMembers = $target->getMembersObject();

        $this->moveParticipants($parentMembers, $targetMembers, IL_CRS_ADMIN, IL_CRS_ADMIN);
        $this->moveParticipants($sourceMembers, $targetMembers, IL_GRP_ADMIN, IL_CRS_ADMIN);
        $this->moveParticipants($sourceMembers, $targetMembers, IL_GRP_MEMBER, IL_CRS_MEMBER);

        $this->dic->fau()->user()->repo()->moveMembers($source->getId(), $target->getId());
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
                $former_role_title = self::FORMER_COURSE_MEMBER;
                $member_role_title = 'il_crs_member';
                break;

            case 'grp':
                $former_role_title = self::FORMER_GROUP_MEMBER;
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