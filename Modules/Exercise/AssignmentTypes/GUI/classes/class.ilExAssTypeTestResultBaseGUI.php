<?php
// fau: exAssTest - new class ilExAssTypeTestResultGUI.

require_once(__DIR__ . "/../../classes/class.ilExAssTypeTestResultAssignment.php");

/**
 * Test Result assignment type  base gui implementations
 */
abstract class ilExAssTypeTestResultBaseGUI implements ilExAssignmentTypeGUIInterface
{
    use ilExAssignmentTypeGUIBase;

    /** @var  ilAccessHandler $access */
    protected $access;

    /** @var ilCtrl $ctrl */
    protected $ctrl;

    /** @var  ilLanguage $lng */
    protected $lng;

    /** @var ilTabsGUI */
    protected $tabs;

    /** @var  ilToolbarGUI $toolbar */
    protected $toolbar;

    /** @var ilTemplate $tpl */
    protected $tpl;


    /**
     * Constructor
     */
    public function __construct()
    {
        global $DIC;
        $this->access = $DIC->access();
        $this->ctrl = $DIC->ctrl();
        $this->lng = $DIC->language();
        $this->tabs = $DIC->tabs();
        $this->toolbar = $DIC->toolbar();
        $this->tpl = $DIC['tpl'];
    }

    /**
     * @inheritdoc
     */
    public function addEditFormCustomProperties(ilPropertyFormGUI $form)
    {
        require_once (__DIR__ . '/../form/class.ilExAssTypeTestResultSelectInputGUI.php');

        $input = new ilExAssTypeTestResultSelectInputGUI($this->lng->txt('exc_ass_type_test_object'), 'test_ref_id');
        $input->setRequired(true);
        $input->setSelectableTypes(['tst']);
        $input->setInfo($this->lng->txt('exc_ass_type_test_object_info'));
        $form->addItem($input);
    }

    /**
     * @inheritdoc
     */
    public function importFormToAssignment(ilExAssignment $a_ass, ilPropertyFormGUI $a_form)
    {
        $assTest = ilExAssTypeTestResultAssignment::findOrGetInstance($a_ass->getId());
        $assTest->setTestRefId((int) $a_form->getInput('test_ref_id'));
        $assTest->save();
    }

    /**
     * @inheritdoc
     */
    public function getFormValuesArray(ilExAssignment $ass)
    {
        $assTest = ilExAssTypeTestResultAssignment::findOrGetInstance($ass->getId());
        return ['test_ref_id' => $assTest->getTestRefId()];
    }

    /**
     * @inheritdoc
     */
    public function getOverviewContent(ilInfoScreenGUI $a_info, ilExSubmission $a_submission)
    {
    }
}
