<?php
// fau: exAssTest - new class ilExAssTypeTestResultGUI.

use ILIAS\Exercise\Assignment\PropertyAndActionBuilderUI;

require_once(__DIR__ . "/class.ilExAssTypeTestResultBaseGUI.php");

/**
 * Test Result assignment type gui implementation
 */
class ilExAssTypeTestResultGUI extends ilExAssTypeTestResultBaseGUI implements ilExAssignmentTypeGUIInterface
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
    public function addEditFormCustomProperties(ilPropertyFormGUI $form): void
    {
        parent::addEditFormCustomProperties($form);
    }

    /**
     * @inheritdoc
     */
    public function importFormToAssignment(ilExAssignment $a_ass, ilPropertyFormGUI $a_form): void
    {
        parent::importFormToAssignment($a_ass, $a_form);
    }

    /**
     * @inheritdoc
     */
    public function getFormValuesArray(ilExAssignment $ass): mixed
    {
        return parent::getFormValuesArray($ass);
    }

    /**
     * @inheritdoc
     */
    public function getOverviewContent(ilInfoScreenGUI $a_info, ilExSubmission $a_submission): void
    {
        parent::getOverviewContent($a_info, $a_submission);
    }

    /**
     * @inheritdoc
     */    
    public function buildSubmissionPropertiesAndActions(PropertyAndActionBuilderUI $builder): void {
        parent::buildSubmissionPropertiesAndActions($builder);
    }
}