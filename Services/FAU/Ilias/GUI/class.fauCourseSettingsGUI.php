<?php

use FAU\BaseGUI;
use FAU\Study\Data\Course;

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

}
