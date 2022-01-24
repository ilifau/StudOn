<?php
// fau: exAssTest - new class ilExAssTypeTestResultTeamGUI.

require_once(__DIR__ . "/class.ilExAssTypeTestResultBaseGUI.php");

/**
 * Test Result assignment type base gui implementation
 */
class ilExAssTypeTestResultTeamGUI extends ilExAssTypeTestResultBaseGUI implements ilExAssignmentTypeGUIInterface
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    public function addEditFormCustomProperties(ilPropertyFormGUI $form)
    {
        parent::addEditFormCustomProperties($form);
    }

    /**
     * @inheritdoc
     */
    public function importFormToAssignment(ilExAssignment $a_ass, ilPropertyFormGUI $a_form)
    {
        parent::importFormToAssignment($a_ass, $a_form);
    }

    /**
     * @inheritdoc
     */
    public function getFormValuesArray(ilExAssignment $ass)
    {
        return parent::getFormValuesArray($ass);
    }

    /**
     * @inheritdoc
     */
    public function getOverviewContent(ilInfoScreenGUI $a_info, ilExSubmission $a_submission)
    {
        parent::getOverviewContent($a_info, $a_submission);
    }
}
