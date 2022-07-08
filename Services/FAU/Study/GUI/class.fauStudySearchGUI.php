<?php

use FAU\BaseGUI;

/**
 * Search for events from campo
 *
 * @ilCtrl_Calls: fauStudySearchGUI
 */
class fauStudySearchGUI extends BaseGUI
{
    /**
     * Execute a command
     */
    public function executeCommand()
    {
        $cmd = $this->ctrl->getCmd('show');
        switch ($cmd)
        {
            case "show":
                $this->$cmd();
                break;

            default:
                $this->tpl->setContent('unknown command: ' . $cmd);
        }

        $this->tpl->setTitle($this->lng->txt('fau_search'));
        $this->tpl->setTitleIcon(ilObject::_getIcon("", "big", "src"));
        $this->tpl->printToStdout();
    }

    protected function show()
    {
        $this->tpl->setContent('view search');
    }



}