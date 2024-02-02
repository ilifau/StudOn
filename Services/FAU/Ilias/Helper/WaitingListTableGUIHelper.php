<?php

namespace FAU\Ilias\Helper;

/**
 * trait for providing additional ilWaitingListTableGUI methods
 */
trait WaitingListTableGUIHelper 
{
    // fau: fair sub#112 - new function addToDos
    /**
     * add messages and fill button to admin
     */
    protected function addToDos()
    {
        global $DIC;

        if (empty($registration = $DIC->fau()->ilias()->getRegistration($this->getRepositoryObject()))) {
            return;
        }
        $todo_messages = [];

        // check if waiting registrations have to be confirmed
        if ($this->getWaitingList()->getCountToConfirm() > 0) {
            $todo_messages[] = $this->lng->txt('sub_to_confirm_message');
        }

        // check if places are free and can be filled (fair time is over)
        if ($registration->canBeFilled()) {
            if ($this->getRepositoryObject()->isParallelGroup()) {
                $todo_messages[] = $this->lng->txt('sub_to_fill_message_group');
            } else {
                $todo_messages[] = $this->lng->txt('sub_to_fill_message');
                $this->addCommandButton('confirmFillFreePlaces', $this->lng->txt('sub_fill_free_places'));
            }

        }

        $DIC->ui()->mainTemplate()->setOnScreenMessage('info', implode('<br />', $todo_messages));
    }
    // fau.
}