<?php
// fau: exAssTest - new class ilExSubmissionTestResultTeamGUI.

require_once(__DIR__ . "/class.ilExSubmissionTestResultBaseGUI.php");

/**
 * Test result based submissions for teams (copies status and mark from the test)
 *
 * @ilCtrl_Calls ilExSubmissionTestResultTeamGUI:
 * @ingroup ModulesExercise
 */
class ilExSubmissionTestResultTeamGUI extends ilExSubmissionTestResultBaseGUI
{
    /**
     * @var ilObjUser
     */
    protected $user;

    /** @var ilExAssTypeTestResultAssignment */
    protected $assTestResult;

    /**
     * Constructor
     */
    public function __construct(ilObjExercise $a_exercise, ilExSubmission $a_submission)
    {
        parent::__construct($a_exercise, $a_submission);
    }

    /**
     * @inheritdoc
     */
    public function executeCommand()
    {
        parent::executeCommand();
    }

    /**
     * @inheritdoc
     */
    public static function getOverviewContent(ilInfoScreenGUI $a_info, ilExSubmission $a_submission)
    {
        parent::getOverviewContent( $a_info,  $a_submission);
    }
}
