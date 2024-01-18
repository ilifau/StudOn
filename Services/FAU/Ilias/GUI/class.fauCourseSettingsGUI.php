<?php

use FAU\BaseGUI;
use FAU\Study\Data\Course;
use FAU\Ilias\FairSub;

class fauCourseSettingsGUI extends BaseGUI
{
    /**
     * Add the campo settings to a form
     */
    public function addCampoSettingsToForm(ilPropertyFormGUI $form, ilObjCourse|ilObjGroup $object): void
    {
        $import_id = \FAU\Study\Data\ImportId::fromString($object->getImportId());
        if ($import_id->isForCampo() && ilCust::administrationIsVisible()) {
            foreach($this->dic->fau()->study()->repo()->getCoursesByIliasObjId($object->getId()) as $course) {
                $header = new ilFormSectionHeaderGUI();
                $header->setTitle($this->lng->txt('fau_campo_settings'));
                $form->addItem($header);
                $radio = new ilRadioGroupInputGUI($this->lng->txt('fau_campo_send_passed_label'), 'send_passed');
                $radio->setInfo($this->lng->txt('fau_campo_send_passed_info'));
                $radio->setValue($course->getSendPassed());
                $option = new ilRadioOption($this->lng->txt('fau_campo_send_passed_none'), Course::SEND_PASSED_NONE);
                $radio->addOption($option);
                $option = new ilRadioOption($this->lng->txt('fau_campo_send_passed_lp'), Course::SEND_PASSED_LP);
                $radio->addOption($option);
                $radio->setAlert($this->lng->txt('fau_campo_send_passed_alert'));
                $form->addItem($radio);
                return;
            }
        }
    }

    /**
     * Save the campo settings from a form
     */
    public function saveCampoSettingsFromForm(ilPropertyFormGUI $form, ilObjCourse|ilObjGroup $object): void
    {
        $import_id = \FAU\Study\Data\ImportId::fromString($object->getImportId());
        if ($import_id->isForCampo() && ilCust::administrationIsVisible()) {
            foreach($this->dic->fau()->study()->repo()->getCoursesByIliasObjId($object->getId()) as $course) {
                $course = $course->withSendPassed((string) $form->getInput('send_passed'));
                $this->dic->fau()->study()->repo()->save($course);
                return;
            }
        }
    }

    /**
     * Add the fairSub settings to the gui
     */
    public function addFairSubSettingsToForm(ilCheckboxInputGUI $gui, ilObjCourse $object): void
    {/*
        ... remove item 
        $form->removeItemByPostVar("desc");
        Beipsiel in ilObjItemGroupGUI
*/
        
        //$import_id = \FAU\Study\Data\ImportId::fromString($object->getImportId());
        
        if (ilCust::administrationIsVisible()) {
   //         foreach($this->dic->fau()->study()->repo()->getCoursesByIliasObjId($object->getId()) as $course) {
                if ($object->getSubscriptionFair() < 0) {
                    $fair_date = new ilNonEditableValueGUI($this->lng->txt('sub_fair_date'));
                    $fair_date_info = $this->lng->txt('sub_fair_inactive_message');
                    $fair_date_link = '<br />» <a href="' . '' /*$this->ctrl->getLinkTarget($this, 'activateSubFair')*/ . '">' . $this->lng->txt('sub_fair_activate') . '</a>';
                    $wait_options = array(
                        'auto' => 'sub_fair_inactive_autofill',
                        'manu' => 'sub_fair_inactive_waiting',
                        'no_list' => 'sub_fair_inactive_no_list'
                    );
                } else {
                    $fair_date = new ilDateTimeInputGUI($this->lng->txt('sub_fair_date'), 'subscription_fair');
                    $fair_date->setShowTime(true);
                    $fair_date->setDate(new ilDateTime($object->getSubscriptionFair(), IL_CAL_UNIX));
                    $fair_date_info = $this->lng->txt('sub_fair_date_info');
                    $fair_date_link = '<br />» <a href="' . ''/*$this->ctrl->getLinkTarget($this, 'confirmDeactivateSubFair')*/ . '">' . $this->lng->txt('sub_fair_deactivate') . '</a>';
                    $wait_options = array(
                        'auto' => 'sub_fair_autofill',
                        'auto_manu' => 'sub_fair_auto_manu',
                        'manu' => 'sub_fair_waiting',
                        'no_list' => 'sub_fair_no_list'
                    );
                }
            
                $fair_date->setInfo($fair_date_info . (ilCust::deactivateFairTimeIsAllowed() ? $fair_date_link : ''));
                $gui->addSubItem($fair_date);
            
                $wait = new ilRadioGroupInputGUI($this->lng->txt('crs_waiting_list'), 'waiting_list');
                foreach ($wait_options as $postvalue => $langvar) {
                    $option = new ilRadioOption($this->lng->txt($langvar), $postvalue);
                    $option->setInfo($this->lng->txt($langvar . '_info'));
                    $wait->addOption($option);
                }
            
                if ($object->hasWaitingListAutoFill()) {
                    $wait->setValue('auto');
                } elseif ($object->getSubscriptionAutoFill() && $object->enabledWaitingList()) {
                    $wait->setValue('auto_manu');
                } elseif ($object->enabledWaitingList()) {
                    $wait->setValue('manu');
                } else {
                    $wait->setValue('no_list');
                }
            
                $gui->addSubItem($wait);
                return;
          //  }
        }
    }



    /**
     * Save the fairSub settings from a form
     */
    public function saveFairSubettingsFromForm(ilPropertyFormGUI $form, ilObjCourse|ilObjGroup $object): void
    {
        $import_id = \FAU\Study\Data\ImportId::fromString($object->getImportId());
        if ($import_id->isForCampo() && ilCust::administrationIsVisible()) {
            foreach($this->dic->fau()->study()->repo()->getCoursesByIliasObjId($object->getId()) as $course) {
                $course = $course->withSendPassed((string) $form->getInput('send_passed'));
                $this->dic->fau()->study()->repo()->save($course);
                return;
            }
        }
    }    

}
