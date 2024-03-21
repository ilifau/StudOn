<?php

use FAU\BaseGUI;
use FAU\Ilias\Transfer;
use FAU\Study\Data\ImportId;
use FAU\Study\Data\Term;

/**
 * GUI for transferring a campo connection to another course
 * @ilCtrl_IsCalledBy fauCourseTransferGUI: ilObjCourseGUI
 */
class fauCourseTransferGUI extends BaseGUI
{
    protected Transfer $transfer;
    protected ilObjCourse $object;

    protected int $target_ref_id = 0;
    protected int $target_obj_id = 0;

    protected ImportId $current_import_id;
    protected ImportId $target_import_id;

    /**
     * Init the transfer with a course object
     * Init s posted target
     * This should be called before executeCommand();
     *
     * @param ilObjCourse $course
     */
    public function init(ilObjCourse $course)
    {
        $this->transfer = $this->dic->fau()->ilias()->transfer();
        $this->object = $course;
        $this->current_import_id = ImportId::fromString($this->object->getImportId());
        $this->target_import_id = new ImportId();

        $post = $this->request->getParsedBody();
        if (isset($post['target_ref_id'])) {
            $this->target_ref_id = (int) $post['target_ref_id'];
            $this->target_obj_id = (int) ilObject::_lookupObjId($this->target_ref_id);
            $this->target_import_id = ImportId::fromString(ilObject::_lookupImportId($this->target_obj_id));
        }
    }

    /**
     * Execute a command (main entry point)
     * @access public
     */
    public function executeCommand()
    {
        if (!isset($this->object)) {
            throw new ilCtrlException('called fauCourseTransferGUI:executeCommand() without init()');
        }

        $cmd = $this->ctrl->getCmd("selectTargetCourse");
        switch ($cmd) {
            case "returnToParent":
            case "selectTargetCourse":
            case "showTransferOptions":
            case "doTransfer":
            case "showSplitOptions":
            case "doSplit":
            case "showSolveOptions":
            case "doSolve":
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
     * Check the basic requirements for a target
     */
    protected function checkTargetBasic()
    {
        if (ilObject::_lookupType($this->target_ref_id, true) != 'crs') {
            ilUtil::sendFailure($this->lng->txt('fau_transfer_failed_no_course'), true);
            $this->returnToParent();
        }
        if (!$this->access->checkAccess('write','', $this->target_ref_id, 'crs')) {
            ilUtil::sendFailure($this->lng->txt('fau_transfer_failed_no_write'), true);
            $this->returnToParent();
        }
        if (!$this->access->checkAccess('manage_members','', $this->target_ref_id, 'crs')) {
            ilUtil::sendFailure($this->lng->txt('fau_transfer_failed_no_manage_members'), true);
            $this->returnToParent();
        }
    }

    
    /**
     * Check if the target is valid for a course transfer
     */
    protected function checkTargetTransfer()
    {
        $this->checkTargetBasic();

        if ($this->target_ref_id == $this->object->getRefId()) {
            ilUtil::sendFailure($this->lng->txt('fau_transfer_failed_same_object'), true);
            $this->returnToParent();
        }
        if ($this->target_import_id->isForCampo()) {

            if ($this->target_import_id->getEventId() != $this->current_import_id->getEventId()) {
                ilUtil::sendFailure($this->lng->txt('fau_transfer_failed_already_connected_other'), true);
                $this->returnToParent();
            }
            
            if (empty($this->target_import_id->getCourseId()) != empty($this->current_import_id->getCourseId())) {
                ilUtil::sendFailure($this->lng->txt('fau_transfer_failed_nesting_differs'), true);
                $this->returnToParent();
            }
            
            $term = Term::fromString($this->target_import_id->getTermId());
            if ($this->dic->fau()->study()->getTermEndTime($term) >= time()) {
                ilUtil::sendFailure($this->lng->txt('fau_transfer_failed_already_connected_term'), true);
                $this->returnToParent();
            }
        }
    }


    /**
     * Select a target course
     */
    protected function selectTargetCourse()
    {
        $this->tpl->setOnScreenMessage('info', $this->lng->txt('fau_transfer_course_message'));

        $toolbar_gui = new ilToolbarGUI();
        $toolbar_gui->addFormButton($this->lng->txt('fau_transfer_next'),'showTransferOptions');
        $toolbar_gui->addFormButton($this->lng->txt('cancel'),'returnToParent');
        $toolbar_gui->setFormAction($this->ctrl->getFormAction($this));
        $toolbar_gui->setCloseFormTag(false);
        $html = $toolbar_gui->getHTML();

        $explorer_gui = new fauRepositorySelectionExplorerGUI($this, 'selectTargetCourse');
        $explorer_gui->setSelectMode('target_ref_id', false);
        $explorer_gui->setTypeWhiteList(['root', 'cat']);
        $explorer_gui->setSelectableTypes(['crs']);
        if ($explorer_gui->handleCommand()) {
            return;
        }
        $html .= $explorer_gui->getHTML();

        $toolbar_gui = new ilToolbarGUI();
        $toolbar_gui->addFormButton($this->lng->txt('fau_transfer_next'),'showTransferOptions');
        $toolbar_gui->addFormButton($this->lng->txt('cancel'),'returnToParent');
        $toolbar_gui->setOpenFormTag(false);
        $html .= $toolbar_gui->getHTML();

        $this->tpl->setContent($html);
    }

    /**
     * Show the form for the transfer options
     */
    protected function showTransferOptions()
    {
        $this->checkTargetTransfer();
        $form = $this->initTransferOptionsForm();
        $this->tpl->setContent($form->getHTML());
    }

    /**
     * Init the form for the transfer options
     */
    protected function initTransferOptionsForm() :  ilPropertyFormGUI
    {
        $form = new ilPropertyFormGUI();
        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->setTitle($this->lng->txt('fau_transfer_course'));

        $target = new ilHiddenInputGUI('target_ref_id');
        $target->setValue($this->target_ref_id);
        $form->addItem($target);

        $selected = new ilNonEditableValueGUI($this->lng->txt('fau_transfer_selected_course'));
        $selected->setValue(ilObject::_lookupTitle($this->target_obj_id));
        $form->addItem($selected);

        $title = new ilCheckboxInputGUI($this->lng->txt('fau_transfer_update_title'), 'update_title');
        $title->setInfo($this->lng->txt('fau_transfer_update_title_info'));
        $form->addItem($title);

        $move = new ilCheckboxInputGUI($this->lng->txt('fau_transfer_move_members'), 'move_members');
        $move->setInfo($this->lng->txt('fau_transfer_move_members_info'));
        if ($this->target_import_id->isForCampo()) {
            $move->setChecked(true);
            $move->setDisabled(true);
        }
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
            foreach ($this->dic->fau()->ilias()->objects()->getChildGroupsList($this->target_ref_id) as $group_ref_id => $group_title) {
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
        $this->checkTargetTransfer();
        $form = $this->initTransferOptionsForm();
        if (!$form->checkInput()) {
            $form->setValuesByPost();
            $this->tpl->setContent($form->getHTML());
            return;
        }

        $update_title = (bool) $form->getInput('update_title');
        if ($this->target_import_id->isForCampo()) {
            $move_members = true;
        }
        else {
        $move_members = (bool) $form->getInput('move_members');
        }
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
                $this->tpl->setOnScreenMessage('failure', $this->lng->txt('fau_transfer_groups_multiple_selected'));
                $this->tpl->setContent($form->getHTML());
                return;
            }

        }

        $target = new ilObjCourse($this->target_ref_id, true);
        $this->transfer->moveCampoConnection($this->object, $target, $update_title, $move_members, $delete_source, $assign_groups, $update_group_titles);

        $this->tpl->setOnScreenMessage('success', $this->lng->txt('fau_transfer_success'), true);
        $this->ctrl->redirectToURL(ilLink::_getLink($this->target_ref_id));
    }

    
    /**
     * Show the options to split a course
     */
    protected function showSplitOptions()
    {
        $this->tpl->setOnScreenMessage('info', $this->lng->txt('fau_split_course_message'));
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
     * Do the splitting of a course
     */
    protected function doSplit()
    {
        $form = $this->initSplitOptionsForm();
        if (!$form->checkInput()) {
            $form->setValuesByPost();
            $this->tpl->setContent($form->getHTML());
            return;
        }

        $cat_ref_id = $this->dic->repositoryTree()->getParentId($this->object->getRefId());
        if ($this->access->checkAccess('delete','', $this->object->getRefId(), 'crs')) {
            $delete_source = (bool) $form->getInput('delete_source');
        }
        else {
            $delete_source = false;
        }

        $this->transfer->splitCampoCourse($this->object, $delete_source);

        $this->tpl->setOnScreenMessage('success', $this->lng->txt('fau_split_success'), true);
        $this->ctrl->redirectToURL(ilLink::_getLink($cat_ref_id));
    }

    /**
     * Show the options to solve a campo conflict
     */
    protected function showSolveOptions()
    {
        $this->tpl->setOnScreenMessage('info', $this->lng->txt('fau_solve_conflict_message'));
        $form = $this->initSolveForm();
        $this->tpl->setContent($form->getHTML());
    }

    /**
     * Show a course selection to solve conflicts of courses with identical import id
     */
    protected function initSolveForm() : ilPropertyFormGUI
    {
        $form = new ilPropertyFormGUI();
        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->setTitle($this->lng->txt('fau_solve_campo_conflict'));

        $pathGUI = new ilPathGUI();
        $pathGUI->enableTextOnly(false);
        $pathGUI->enableHideLeaf(false);

        $radio = new ilRadioGroupInputGUI($this->lng->txt('fau_solve_connect_course'), 'target_ref_id');
        $radio->setRequired(true);
        foreach ($this->dic->fau()->study()->repo()->getObjectIdsWithImportId($this->current_import_id) as $obj_id)
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

/**
     * Solve conflicts of courses with an identical import id
     */
    protected function doSolve()
    {
        $this->checkTargetBasic();
        
        $this->dic->fau()->ilias()->transfer()->solveCourseConflicts($this->target_import_id, new ilObjCourse($this->target_ref_id));
        ilUtil::sendSuccess($this->lng->txt('fau_solve_success'), true);
        $this->ctrl->redirectToURL(ilLink::_getLink($this->target_ref_id));
    }
}