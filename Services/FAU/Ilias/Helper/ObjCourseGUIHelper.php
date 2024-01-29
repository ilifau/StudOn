<?php

namespace FAU\Ilias\Helper;
use ilCust;
use ilConfirmationGUI;

/**
 * trait for providing additional ilObjCourseGUI methods
 */
trait ObjCourseGUIHelper 
{
    // fau: fairSub#27 - activation and deactivation of the fair period
    public function activateSubFairObject()
    {
        if (!ilCust::deactivateFairTimeIsAllowed()) {
            $this->tpl->setOnScreenMessage('failure', $this->lng->txt('permission_denied'), true);
        } else {
            $this->object->setSubscriptionFair($this->object->getSubscriptionStart() + $this->object->getSubscriptionMinFairSeconds());
            $this->object->setSubscriptionAutoFill(true);
            $this->object->update();
            $this->tpl->setOnScreenMessage('success', $this->lng->txt('sub_fair_activated'), true);
        }
        $this->ctrl->redirect($this, 'edit');
    }

    public function deactivateSubFairObject()
    {
        if (!ilCust::deactivateFairTimeIsAllowed()) {
            $this->tpl->setOnScreenMessage('failure', $this->lng->txt('permission_denied'), true);
        } elseif ($this->object->inSubscriptionFairTime()) {
            $this->tpl->setOnScreenMessage('failure', $this->lng->txt('sub_fair_deactivate_in_phase'), true);
        } else {
            $this->object->setSubscriptionFair(-1);
            $this->object->setSubscriptionAutoFill(false);
            $this->object->update();
            $this->tpl->setOnScreenMessage('success', $this->lng->txt('sub_fair_deactivated'), true);
        }
        $this->ctrl->redirect($this, 'edit');
    }

    public function confirmDeactivateSubFairObject()
    {
        if (!ilCust::deactivateFairTimeIsAllowed()) {
            $this->tpl->setOnScreenMessage('failure', $this->lng->txt('permission_denied'), true);
        } elseif ($this->object->inSubscriptionFairTime()) {
            $this->tpl->setOnScreenMessage('failure', $this->lng->txt('sub_fair_deactivate_in_phase'), true);
            $this->ctrl->redirect($this, 'edit');
        }

        $c_gui = new ilConfirmationGUI();
        $c_gui->setFormAction($this->ctrl->getFormAction($this, "edit"));
        $c_gui->setHeaderText($this->lng->txt('sub_fair_deactivate_question')
            . '<p class="small">' . $this->lng->txt('sub_fair_deactivate_explanation') . '</p>');
        $c_gui->setCancel($this->lng->txt("cancel"), "edit");
        $c_gui->setConfirm($this->lng->txt("confirm"), "deactivateSubFair");

        $this->tpl->setContent($c_gui->getHTML());
    }
    // fau.  fairSub - activation and deactivation of the fair period 
}