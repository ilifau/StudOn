<?php

/* Copyright (c) 1998-2018 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * fau: exAssTest - new assignment type for test results of teams
 */
class ilExAssTypeTestResultTeam extends ilExAssTypeTestResult
{
    public function usesTeams(): bool
    {
        return true;
    }

    public function getTitle(): string
    {
        return $this->lng->txt("exc_type_test_result_team");
    }

    public function getSubmissionType(): string
    {
        return ilExSubmission::TYPE_TEST_RESULT_TEAM;
    }
}
