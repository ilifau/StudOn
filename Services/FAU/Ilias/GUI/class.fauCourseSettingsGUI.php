<?php

use FAU\BaseGUI;

class fauCourseSettingsGUI extends BaseGUI
{
    /**
     * Add the campo settings to a form
     * @param ilPropertyFormGUI $form
     * @param ilObjCourse|ilObjGroup $object
     * @return void
     */
    public function addCampoSettingsToForm($form, $object)
    {
        $import_id = \FAU\Study\Data\ImportId::fromString($object->getImportId());
        if ($import_id->isForCampo()) {
            foreach($this->dic->fau()->study()->repo()->getCoursesByIliasObjId($object->getId()) as $course) {
                $header = new ilFormSectionHeaderGUI();
                $header->setTitle($this->lng->txt('fau_campo_settings'));
                $form->addItem($header);
                $radio = new ilRadioGroupInputGUI($this->lng->txt('fau_campo_needs_passed_label'), 'needs_passed');
                $radio->setInfo($this->lng->txt('fau_campo_needs_passed_info'));
                $radio->setValue($course->getNeedsPassed() ? 1 : 0);
                $option = new ilRadioOption($this->lng->txt('fau_campo_needs_passed_false'), 0);
                $radio->addOption($option);
                $option = new ilRadioOption($this->lng->txt('fau_campo_needs_passed_true'), 1);
                $radio->addOption($option);
                $form->addItem($radio);
                return;
            }
        }
    }

    /**
     * Save the campo settings from a form
     * @param ilPropertyFormGUI $form
     * @param ilObjCourse|ilObjGroup $object
     * @return void
     */
    public function saveCampoSettingsFromForm($form, $object)
    {
        $import_id = \FAU\Study\Data\ImportId::fromString($object->getImportId());
        if ($import_id->isForCampo()) {
            foreach($this->dic->fau()->study()->repo()->getCoursesByIliasObjId($object->getId()) as $course) {
                $course = $course->withNeedsPassed((int) $form->getInput('needs_passed'));
                $this->dic->fau()->study()->repo()->save($course);
                return;
            }
        }
    }

}
