<?php
// fau: exAssTest - new assignment type for test results

/**
 * ILIAS test result as assignment type type
 */
class ilExAssTypeTestResult implements ilExAssignmentTypeInterface
{
    /**
     * @var ilLanguage
     */
    protected $lng;

    /**
     * Constructor
     *
     * @param ilLanguage|null $a_lng
     */
    public function __construct(ilLanguage $a_lng = null)
    {
        global $DIC;

        $this->lng = ($a_lng)
            ? $a_lng
            : $DIC->language();
    }

    /**
     * @inheritdoc
     */
    public function isActive()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function usesTeams()
    {
        return false;
    }

    // fau: exAssHook - new function hasFiles()
    public function hasFiles()
    {
        return false;
    }
    // fau.

    /**
     * @inheritdoc
     */
    public function usesFileUpload()
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getTitle()
    {
        $lng = $this->lng;

        return $lng->txt("exc_type_test_result");
    }

    /**
     * @inheritdoc
     */
    public function getSubmissionType()
    {
        return ilExSubmission::TYPE_TEST_RESULT;
    }

    /**
     * @inheritdoc
     */
    public function isSubmissionAssignedToTeam()
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function cloneSpecificProperties(ilExAssignment $source, ilExAssignment $target)
    {
        require_once(__DIR__ . "/class.ilExAssTypeTestResultAssignment.php");
        $sourceTest = ilExAssTypeTestResultAssignment::findOrGetInstance($source->getId());
        $targetTest = ilExAssTypeTestResultAssignment::findOrGetInstance($target->getId());
        $targetTest->setExerciseId($target->getExerciseId());
        $targetTest->setTestRefId($sourceTest->getTestRefId());
        $targetTest->save();
    }


    /**
     * @inheritdoc
     */
    public function isManualGradingSupported($a_ass): bool {
        return ilObjExerciseAccess::checkExtendedGradingAccess($a_ass->getExerciseId(), false);
    }
}
