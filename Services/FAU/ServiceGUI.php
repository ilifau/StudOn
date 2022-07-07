<?php

namespace FAU;

/**
 * Search for events from campo
 *
 * @ilCtrl_calls FAU\ServiceGUI:
 */
class ServiceGUI extends BaseGUI
{
    public function executeCommand()
    {
        switch($this->ctrl->getNextClass($this))
        {
            case 'fau\study\gui\search':
                $this->ctrl->forwardCommand($this->dic->fau()->study()->guis()->search());
                break;

            // process command, if current class is responsible to do so
            default:
                $cmd = $this->ctrl->getCmd('');
                $this->tpl->setContent('unknown command: ' . $cmd);
        }
    }
}