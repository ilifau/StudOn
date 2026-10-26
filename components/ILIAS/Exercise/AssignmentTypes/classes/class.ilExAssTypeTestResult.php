<?php

/* Copyright (c) 1998-2018 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * fau: exAssTest - new assignment type for test results
 */
class ilExAssTypeTestResult implements ilExAssignmentTypeInterface
{
    protected ilLanguage $lng;

    public function __construct(?ilLanguage $a_lng = null)
    {
        global $DIC;

        $this->lng = ($a_lng)
            ? $a_lng
            : $DIC->language();
    }

    public function isActive(): bool
    {
        return true;
    }

    public function usesTeams(): bool
    {
        return false;
    }

    public function hasFiles(): bool
    {
        return false;
    }

    public function usesFileUpload(): bool
    {
        return false;
    }

    public function getTitle(): string
    {
        return $this->lng->txt("exc_type_test_result");
    }

    public function getSubmissionType(): string
    {
        return ilExSubmission::TYPE_TEST_RESULT;
    }

    public function isSubmissionAssignedToTeam(): bool
    {
        return false;
    }

    public function cloneSpecificProperties(ilExAssignment $source, ilExAssignment $target): void
    {
        $sourceTest = ilExAssTypeTestResultAssignment::findOrGetInstance($source->getId());
        $targetTest = ilExAssTypeTestResultAssignment::findOrGetInstance($target->getId());
        $targetTest->setExerciseId($target->getExerciseId());
        $targetTest->setTestRefId($sourceTest->getTestRefId());
        $targetTest->save();
    }

    public function supportsWebDirAccess(): bool
    {
        return false;
    }

    public function getStringIdentifier(): string
    {
        return '';
    }
}
