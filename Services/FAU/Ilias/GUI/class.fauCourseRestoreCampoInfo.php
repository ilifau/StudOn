<?php

use FAU\BaseGUI;
use FAU\Study\Data\ImportId;
use FAU\Sync\SyncWithIlias;

/**
 * GUI for restoring the Campo Title and Description
 * @ilCtrl_IsCalledBy fauCourseRestoreCampoInfoGUI: ilObjCourseGUI
 */
class fauCourseRestoreCampoInfoGUI extends BaseGUI
{
    protected ilObjCourse $object;

    /**
     * Init the transfer with a course object
     * This should be called before executeCommand();
     *
     * @param ilObjCourse $course
     */
    public function init(ilObjCourse $course)
    {
        $this->object = $course;
    }

    /**
     * Execute a command (main entry point)
     * @access public
     */
    public function executeCommand()
    {
        if (!isset($this->object)) {
            throw new ilCtrlException('called fauCourseRestoreCampoInfo:executeCommand() without init()');
        }

        $cmd = $this->ctrl->getCmd("selectTargetCourse");
        switch ($cmd) {
            case "returnToParent":
            case "showTransferOptions":
            case "doTransfer":
            case "resetTitleAndDescription":
                $this->$cmd();
                break;
            default:
                $this->tpl->setContent('unknown command: ' . $cmd);
        }
    }

    /**
     * Return to the parent GUI
     */
    protected function returnToParent()
    {
        $this->ctrl->returnToParent($this);
    }

    /**
     * Show the form for the transfer options
     */
    protected function showTransferOptions()
    {
        $form = $this->initTransferOptionsForm();
        $this->tpl->setContent($form->getHTML());
    }

    /**
     * Init the form for the transfer options
     */
    protected function initTransferOptionsForm() :  ilPropertyFormGUI
    {

        //TODO: select groups or just confirm
        $post = $this->request->getParsedBody();
        $ref_id = (int) $post['target_ref_id'];
        $this->checkTarget($ref_id);

        $form = new ilPropertyFormGUI();
        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->setTitle($this->lng->txt('fau_transfer_course'));

        $target = new ilHiddenInputGUI('target_ref_id');
        $target->setValue($ref_id);
        $form->addItem($target);

        $selected = new ilNonEditableValueGUI($this->lng->txt('fau_transfer_selected_course'));
        $selected->setValue(ilObject::_lookupTitle(ilObject::_lookupObjId($ref_id)));
        $form->addItem($selected);

        $title = new ilCheckboxInputGUI($this->lng->txt('fau_transfer_update_title'), 'update_title');
        $title->setInfo($this->lng->txt('fau_transfer_update_title_info'));
        $form->addItem($title);

        $move = new ilCheckboxInputGUI($this->lng->txt('fau_transfer_move_members'), 'move_members');
        $move->setInfo($this->lng->txt('fau_transfer_move_members_info'));
        $form->addItem($move);

        if ($this->access->checkAccess('delete','', $this->object->getRefId(), 'crs')) {
            $delete = new ilCheckboxInputGUI($this->lng->txt('fau_transfer_delete_source'), 'delete_source');
            $delete->setInfo($this->lng->txt('fau_transfer_delete_source_info'));
            $form->addItem($delete);
        }

        if ($this->object->hasParallelGroups()) {
            $head = new ilFormSectionHeaderGUI();
            $head->setTitle($this->lng->txt('fau_transfer_assign_groups'));
            $head->setInfo($this->lng->txt('fau_transfer_assign_groups_info'));
            $form->addItem($head);

            $options = [];
            $options[0] = $this->lng->txt('move');
            foreach ($this->dic->fau()->ilias()->objects()->getChildGroupsList($ref_id) as $group_ref_id => $group_title) {
                $options[$group_ref_id] = $group_title;
            }
            foreach ($this->dic->fau()->ilias()->objects()->getParallelGroupsInfos($this->object->getRefId()) as $group) {
                $assign = new ilSelectInputGUI($group->getTitle(), 'group_' . $group->getRefId());
                $assign->setOptions($options);
                $assign->setRequired(true);
                $form->addItem($assign);
            }

            $title = new ilCheckboxInputGUI($this->lng->txt('fau_transfer_update_group_titles'), 'update_group_titles');
            $title->setInfo($this->lng->txt('fau_transfer_update_group_titles_info'));
            $form->addItem($title);
        }

        $form->addCommandButton('doTransfer', $this->lng->txt('fau_transfer'));
        $form->addCommandButton('returnToParent', $this->lng->txt('cancel'));
        return $form;
    }


    /**
     * Do the actual transfer
     */
    protected function doTransfer()
    {

        //TODO: do the saving of the title and description and so on
        $post = $this->request->getParsedBody();
        $ref_id = (int) $post['target_ref_id'];
        $this->checkTarget($ref_id);

        $form = $this->initTransferOptionsForm();
        if (!$form->checkInput()) {
            $form->setValuesByPost();
            $this->tpl->setContent($form->getHTML());
            return;
        }

        $update_title = (bool) $form->getInput('update_title');
        $move_members = (bool) $form->getInput('move_members');
        if ($this->access->checkAccess('delete','', $this->object->getRefId(), 'crs')) {
            $delete_source = (bool) $form->getInput('delete_source');
        }
        else {
            $delete_source = false;
        }
        $assign_groups = [];
        $update_group_titles = (bool) $form->getInput('update_group_titles');


        if ($this->object->hasParallelGroups()) {
            $used = [];
            $assign_failed = false;
            foreach ($this->dic->fau()->ilias()->objects()->getParallelGroupsInfos($this->object->getRefId()) as $group) {
                $target_ref_id = (int) $form->getInput('group_' . $group->getRefId());
                if (!empty($target_ref_id)) {
                    if (in_array($target_ref_id, $used)) {
                        /** @var ilSelectInputGUI $item */
                        $item = $form->getItemByPostVar('group_' . $group->getRefId());
                        $item->setAlert($this->lng->txt('fau_transfer_group_multiple_selected'));
                        $assign_failed = true;
                    }

                    $assign_groups[$group->getRefId()] = $target_ref_id;
                    $used[] = $target_ref_id;
                }
            }
            if ($assign_failed) {
                $form->setValuesByPost();
                ilUtil::sendFailure($this->lng->txt('fau_transfer_groups_multiple_selected'));
                $this->tpl->setContent($form->getHTML());
                return;
            }

        }

        $target = new ilObjCourse($ref_id, true);
        $this->transfer->moveCampoConnection($this->object, $target, $update_title, $move_members, $delete_source, $assign_groups, $update_group_titles);

        ilUtil::sendSuccess($this->lng->txt('fau_transfer_success'), true);
        $this->ctrl->redirectToURL(ilLink::_getLink($ref_id));
    }

    /**
     * Check if the target is valid
     * @param int $ref_id
     */
    protected function checkTarget(int $ref_id)
    {
        //TODO: is needed?
        $obj_id = ilObject::_lookupObjId($ref_id);
        $import_id = ImportId::fromString(ilObject::_lookupImportId($obj_id));

        if ($ref_id == $this->object->getRefId()) {
            ilUtil::sendFailure($this->lng->txt('fau_transfer_failed_same_object'), true);
            $this->returnToParent();
        }
        if (ilObject::_lookupType($ref_id, true) != 'crs') {
            ilUtil::sendFailure($this->lng->txt('fau_transfer_failed_no_course'), true);
            $this->returnToParent();
        }
        if (!$this->access->checkAccess('write','', $ref_id, 'crs')) {
            ilUtil::sendFailure($this->lng->txt('fau_transfer_failed_no_write'), true);
            $this->returnToParent();
        }
        if (!$this->access->checkAccess('manage_members','', $ref_id, 'crs')) {
            ilUtil::sendFailure($this->lng->txt('fau_transfer_failed_no_manage_members'), true);
            $this->returnToParent();
        }
        if ($import_id->isForCampo()) {
            ilUtil::sendFailure($this->lng->txt('fau_transfer_failed_already_connected'), true);
            $this->returnToParent();
        }
    }


    /**
     * Show the options to split a course
     */
    protected function showSplitOptions()
    {
        ilUtil::sendInfo($this->lng->txt('fau_split_course_message'));
        $form = $this->initSplitOptionsForm();
        $this->tpl->setContent($form->getHTML());
    }

    /**
     * Init the form for the split options
     */
    protected function initSplitOptionsForm()
    {
        $form = new ilPropertyFormGUI();
        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->setTitle($this->lng->txt('fau_split_course'));

        if ($this->access->checkAccess('delete','', $this->object->getRefId(), 'crs')) {
            $delete = new ilCheckboxInputGUI($this->lng->txt('fau_split_delete_source'), 'delete_source');
            $delete->setInfo($this->lng->txt('fau_split_delete_source_info'));
            $form->addItem($delete);
        }

        $form->addCommandButton('doSplit', $this->lng->txt('fau_split'));
        $form->addCommandButton('returnToParent', $this->lng->txt('cancel'));
        return $form;
    }

    /**
     * Show the options to solve a campo conflict
     */
    protected function showSolveOptions()
    {
        ilUtil::sendInfo($this->lng->txt('fau_solve_conflict_message'));
        $form = $this->initSolveForm();
        $this->tpl->setContent($form->getHTML());
    }

    protected function initSolveForm() : ilPropertyFormGUI
    {
        //TODO: Vorlage für mich und select etc?

        $form = new ilPropertyFormGUI();
        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->setTitle($this->lng->txt('fau_solve_campo_conflict'));

        $pathGUI = new ilPathGUI();
        $pathGUI->enableTextOnly(false);
        $pathGUI->enableHideLeaf(false);

        $radio = new ilRadioGroupInputGUI($this->lng->txt('fau_solve_connect_course'), 'target_ref_id');
        $radio->setRequired(true);
        $import_id = \FAU\Study\Data\ImportId::fromString($this->object->getImportId());
        foreach ($this->dic->fau()->study()->repo()->getObjectIdsWithImportId($import_id) as $obj_id)
        {
            if (ilObject::_lookupType($obj_id) == 'crs') {
                foreach (ilObject::_getAllReferences($obj_id) as $ref_id) {
                    if (!ilObject::_isInTrash($ref_id)) {
                        $course = new ilObjCourse($ref_id);
                        $members = $course->getMembersObject()->getCountMembers();
                        $waiting = $course->getWaitingList()->getCountUsers();
                        $info = [];
                        $info[] = $course->getOfflineStatus() ? $this->lng->txt('offline') : $this->lng->txt('online');
                        $info[] = sprintf($this->lng->txt('fau_solve_members_waiting'), $members, $waiting);
                        $info[] = $pathGUI->getPath(1, $ref_id);

                        $option = new ilRadioOption($course->getTitle()
                            . ($obj_id == $this->object->getId() ? ' <b>('. $this->lng->txt('fau_solve_this_course') . ')</b>' : ''),
                            $ref_id,
                            implode("<br>", $info)
                        );
                        $radio->addOption($option);
                    }
                }
            }
            else {
                $option = new ilRadioOption(ilObject::_lookupTitle($obj_id));
                $option->setDisabled(true);
                $radio->addOption($option);
            }
        }
        $form->addItem($radio);

        $form->addCommandButton('doSolve', $this->lng->txt('fau_solve_select_course'));
        $form->addCommandButton('returnToParent', $this->lng->txt('cancel'));
        return $form;

    }
}