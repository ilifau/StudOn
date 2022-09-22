<?php

use FAU\BaseGUI;
use FAU\Ilias\Transfer;
use FAU\Study\Data\ImportId;

/**
 * GUI for transferring a campo connection to another course
 * @ilCtrl_IsCalledBy fauCourseTransferGUI: ilObjCourseGUI
 */
class fauCourseTransferGUI extends BaseGUI
{
    protected Transfer $transfer;
    protected ilObjCourse $object;

    /**
     * Init the transfer with a course object
     * This should be called before executeCommand();
     *
     * @param ilObjCourse $course
     */
    public function init(ilObjCourse $course)
    {
        $this->transfer = $this->dic->fau()->ilias()->transfer();
        $this->object = $course;
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
            case "handleTargetCourseSelected":
                $this->$cmd();
                break;
            default:
                $this->tpl->setContent('unknown command: ' . $cmd);
        }
    }

    /**
     * Return to the parent GUI
     */
    public function returnToParent()
    {
        $this->ctrl->returnToParent($this);
    }

    /**
     * Select a target course
     */
    public function selectTargetCourse()
    {
        ilUtil::sendInfo($this->lng->txt('fau_transfer_course_message'));

        $toolbar_gui = new ilToolbarGUI();
        $toolbar_gui->addFormButton($this->lng->txt('fau_transfer'),'handleTargetCourseSelected');
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
        $toolbar_gui->addFormButton($this->lng->txt('fau_transfer'),'handleTargetCourseSelected');
        $toolbar_gui->addFormButton($this->lng->txt('cancel'),'returnToParent');
        $toolbar_gui->setOpenFormTag(false);
        $html .= $toolbar_gui->getHTML();

        $this->tpl->setContent($html);
    }

    /**
     * Handle the target course selection
     */
    public function handleTargetCourseSelected()
    {
        $post = $this->request->getParsedBody();
        $ref_id = (int) $post['target_ref_id'];
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
        if ($import_id->isForCampo()) {
            ilUtil::sendFailure($this->lng->txt('fau_transfer_failed_already_connected'), true);
            $this->returnToParent();
        }

        $target = new ilObjCourse($ref_id, true);
        $this->transfer->moveCampoConnection($this->object, $target);

        ilUtil::sendSuccess($this->lng->txt('fau_transfer_success'), true);
        $this->returnToParent();
    }
}