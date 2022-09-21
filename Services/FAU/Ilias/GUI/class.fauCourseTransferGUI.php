<?php

use FAU\BaseGUI;
use FAU\Ilias\Transfer;

/**
 * GUI for transferring a campo connection to another course
 * @ilCtrl_IsCalledBy: fauCourseTransferGUI: ilObjCourseGUI
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
            case "selectTarget":
                $this->$cmd();
            default:
                $this->tpl->setContent('unknown command: ' . $cmd);
        }
    }

    /**
     * Select a target course
     */
    public function selectTargetCourse()
    {
        $this->tpl->setContent('select target course: ');
    }
}