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
                    $this->moveParallelGroup($ref_id, $target->getRefId());
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
    protected function moveParallelGroup(int $ref_id, int $target_ref_id)
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

        foreach ($sourceMembers->getAdmins() as $id) {
            $targetMembers->add($id, IL_CRS_ADMIN);
            if ($id != $this->dic->user()->getId()) {
                $sourceMembers->delete($id);
            }
        }
        foreach ($sourceMembers->getTutors() as $id) {
            $targetMembers->add($id, IL_CRS_TUTOR);
            if ($id != $this->dic->user()->getId()) {
                $sourceMembers->delete($id);
            }
        }
        foreach ($sourceMembers->getMembers() as $id) {
            $targetMembers->add($id, IL_CRS_MEMBER);
            if ($id != $this->dic->user()->getId()) {
                $sourceMembers->delete($id);
            }
        }

        $this->dic->fau()->user()->repo()->moveMembers($source->getId(), $target->getId());
    }

    /**
     * Move the group participants from one group to another
     */
    protected function moveGroupParticipants(ilObjGroup $source, ilObjGroup $target)
    {
        $sourceMembers = $source->getMembersObject();
        $targetMembers = $target->getMembersObject();

        foreach ($sourceMembers->getAdmins() as $id) {
            $targetMembers->add($id, IL_GRP_ADMIN);
            if ($id != $this->dic->user()->getId()) {
                $sourceMembers->delete($id);
            }
        }
        foreach ($sourceMembers->getMembers() as $id) {
            $targetMembers->add($id, IL_GRP_MEMBER);
            if ($id != $this->dic->user()->getId()) {
                $sourceMembers->delete($id);
            }
        }
        $this->dic->fau()->user()->repo()->moveMembers($source->getId(), $target->getId());
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