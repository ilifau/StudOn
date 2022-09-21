<?php

namespace FAU\Ilias;

use ILIAS\DI\Container;
use ilObjCourse;
use FAU\Study\Data\ImportId;
use ilConditionHandler;
use ilChangeEvent;

class Transfer
{
    protected Container $dic;

    public function __construct(Container $dic)
    {
        $this->dic = $dic;
    }

    /**
     * Move the campo connection from one course to another
     */
    public function moveCampoConnection(ilObjCourse $source, ilObjCourse $target)
    {
        if ($source->hasParallelGroups()) {
            if (!empty($id = $this->dic->fau()->tools()->settings()->getCourseDidacticTemplateId())) {
                $target->applyDidacticTemplate($id);
            }
            $this->moveParallelGroups($source, $target);
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
    }


    /**
     * Move the parallel groups from one course to another
     * @see ilContainerGUI::pasteObject() - CUT
     */
    protected function moveParallelGroups(ilObjCourse $source, ilObjCourse $target)
    {
        $tree = $this->dic->repositoryTree();
        foreach ($this->dic->fau()->ilias()->objects()->findChildParallelGroups($source->getRefId()) as $ref_id) {
            $old_parent = $tree->getParentId($ref_id);
            $tree->moveTree($ref_id, $target->getRefId());
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
                $target->getId()
            );
            ilChangeEvent::_catchupWriteEvents($node_data['obj_id'], $this->dic->user()->getId());
        }
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
}