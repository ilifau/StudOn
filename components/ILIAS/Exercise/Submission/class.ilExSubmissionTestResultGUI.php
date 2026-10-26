<?php

/* Copyright (c) 1998-2018 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * fau: exAssTest - new class ilExSubmissionTestResultGUI.
 * Test result based submissions (copies status and mark from the test)
 *
 * @ilCtrl_Calls ilExSubmissionTestResultGUI:
 */
class ilExSubmissionTestResultGUI extends ilExSubmissionBaseGUI
{
    public function executeCommand(): void
    {
        $ilCtrl = $this->ctrl;

        if (!in_array(
            $this->submission->getSubmissionType(),
            [ilExSubmission::TYPE_TEST_RESULT, ilExSubmission::TYPE_TEST_RESULT_TEAM]
        ) || !$this->submission->canView()) {
            return;
        }

        $class = $ilCtrl->getNextClass($this);
        $cmd = $ilCtrl->getCmd("callTest");

        switch ($class) {
            default:
                if (in_array($cmd, ["callTest", "syncTestResults"])) {
                    $this->$cmd();
                }
                break;
        }
    }

    public static function getOverviewContent(
        ilInfoScreenGUI $a_info,
        ilExSubmission $a_submission
    ): void {
    }

    /**
     * Add user to the exercise and call the test
     */
    public function callTest(): void
    {
        // no team yet
        if ($this->submission->getSubmissionType() == ilExSubmission::TYPE_TEST_RESULT_TEAM
            && $this->submission->hasNoTeamYet()) {
            return;
        }

        $assTest = ilExAssTypeTestResultAssignment::findOrGetInstance($this->assignment->getId());
        if ($this->submission->canSubmit() && !empty($assTest->getTestRefId())) {
            $members = new ilExerciseMembers($this->exercise);
            if (!$members->isAssigned($this->user->getId())) {
                $members->assignMember($this->user->getId());
            }
            $this->ctrl->redirectToURL(ilLink::_getLink($assTest->getTestRefId(), 'tst'));
        }
    }

    /**
     * Synchronize the test results of all participants
     */
    public function syncTestResults(): void
    {
        global $DIC;

        if (!ilObjExerciseAccess::checkExtendedGradingAccess($this->assignment->getExerciseId(), false)) {
            $this->tpl->setOnScreenMessage("failure", $this->lng->txt("permission_denied"), true);
            $this->returnToParentObject();
        }

        $assTest = ilExAssTypeTestResultAssignment::findOrGetInstance($this->assignment->getId());
        $testObj = new ilObjTest($assTest->getTestRefId());
        $sessionFactory = new ilTestSessionFactory($testObj, $DIC->database(), $this->user);
        $partList = $testObj->getActiveParticipantList();

        $members = new ilExerciseMembers($this->exercise);
        $users = array_intersect($partList->getAllUserIds(), $members->getMembers());

        $synced = [];
        foreach ($users as $user_id) {
            $active_id = $partList->getParticipantByUsrId($user_id)->getActiveId();
            $sessionObj = $sessionFactory->getSession($active_id);
            $tstamp = (int) $sessionObj->getSubmittedTimestamp();

            // check if user is already synced by a team member, keep a newer result time
            if (isset($synced[$user_id]) && $synced[$user_id] > $tstamp) {
                continue;
            }
            $affected = $assTest->storeResult($testObj, $sessionObj);

            // store the result time for check with other team members
            foreach ($affected as $affected_id) {
                $synced[$affected_id] = $tstamp;
            }
        }

        $this->tpl->setOnScreenMessage("success", $this->lng->txt("exc_ass_type_test_synced"), true);
        $this->returnToParentObject();
    }
}
