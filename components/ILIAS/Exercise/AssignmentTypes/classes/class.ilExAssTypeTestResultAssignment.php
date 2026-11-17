<?php

/* Copyright (c) 1998-2018 ILIAS open source, Extended GPL, see docs/LICENSE */

use ILIAS\Test\TestDIC;

/**
 * fau: exAssTest - new class ilExAssTypeTestResultAssignment.
 * Relation between an assignment and the test providing its results
 */
class ilExAssTypeTestResultAssignment extends ActiveRecord
{
    public static function returnDbTableName(): string
    {
        return 'exc_ass_test_result';
    }

    /**
     * @con_is_primary true
     * @con_is_unique  true
     * @con_has_field  true
     * @con_fieldtype  integer
     * @con_is_notnull true
     * @con_length     4
     */
    protected ?int $id = null;

    /**
     * @con_has_field  true
     * @con_fieldtype  integer
     * @con_length     4
     * @con_is_notnull true
     */
    protected int $exercise_id = 0;

    /**
     * @con_has_field  true
     * @con_fieldtype  integer
     * @con_length     4
     * @con_is_notnull true
     */
    protected int $test_ref_id = 0;

    public static function findOrGetInstance($primary_key, array $add_constructor_args = []): self
    {
        /** @var self $record */
        $record = parent::findOrGetInstance($primary_key, $add_constructor_args);
        return $record;
    }

    public function getId(): int
    {
        return (int) $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
        // reset the exercise id to force a lookup when record is stored
        $this->exercise_id = 0;
    }

    public function getExerciseId(): int
    {
        return $this->exercise_id;
    }

    public function setExerciseId(int $exercise_id): void
    {
        $this->exercise_id = $exercise_id;
    }

    /**
     * Save the record, ensure the matching exercise id being saved
     */
    public function store(): void
    {
        if (empty($this->getExerciseId())) {
            $ass = new ilExAssignment($this->getId());
            $this->setExerciseId($ass->getExerciseId());
        }
        parent::store();
    }

    public function getTestRefId(): int
    {
        return $this->test_ref_id;
    }

    public function setTestRefId(int $test_ref_id): void
    {
        $this->test_ref_id = $test_ref_id;
    }

    /**
     * Store the result of a test
     * @return int[] ids of the users whose status is updated
     */
    public function storeResult(ilObjTest $test, ilTestSession $session): array
    {
        global $DIC;
        $lng = $DIC->language();
        $lng->loadLanguageModule('exc');

        // check if assignment still exists
        // now test relationship is deleted with an assignment
        // but formerly it may not have be cleaned up if an assignment is deleted
        // the test queries just by its own ref id and calls submitResult for all found records
        // a deleted assignment will result in learning progress error in status update
        $assignment = new ilExAssignment($this->getId());
        if (empty($assignment->getExerciseId())) {
            return [];
        }

        $state = ilExcAssMemberState::getInstanceByIds($this->getId(), $session->getUserId());
        if ($state->isInTeam()) {
            $user_ids = $state->getTeamObject()->getMembers();
        } elseif (!empty($state->getTeamObject())) {
            $user_ids = [];
        } else {
            $user_ids = [$session->getUserId()];
        }

        $repository = TestDIC::dic()['results.data.repository'];
        $result = $repository->getTestResult($session->getActiveId())
            ?? $repository->updateTestResultCache($session->getActiveId());
        if ($result === null) {
            return [];
        }

        $comments = [];
        $time = new ilDateTime(time(), IL_CAL_UNIX);
        ilDatePresentation::setUseRelativeDates(false);
        $comments[] = $lng->txt('label_time_transfer') . ilDatePresentation::formatDate($time);
        if (!empty($state->getTeamObject())) {
            $comments[] = $lng->txt('label_scored_participant') . ilObjUser::_lookupFullname($session->getUserId());
        }
        $comments[] = $lng->txt('label_scored_pass') . $this->getPassNumber(ilObjTest::_getResultPass($session->getActiveId()));
        $comments[] = $lng->txt('label_started_pass') . $this->getPassNumber($session->getLastStartedPass());
        $comments[] = $lng->txt('label_finished_pass') . $this->getPassNumber($session->getLastFinishedPass());

        foreach ($user_ids as $user_id) {
            $status = new ilExAssignmentMemberStatus($this->getId(), $user_id);
            $status->setStatus($result->isPassed() ? 'passed' : 'failed');
            $status->setReturned(true);
            $status->setSent(false); // needed to trigger sent time update
            $status->setSent(true);
            $status->setMark((string) $result->getReachedPoints());
            $status->setComment(implode(' | ', $comments));
            $status->setNotice($result->getMarkOfficial());
            $status->update();
        }
        return $user_ids;
    }

    /**
     * Update the related assignments with the results of a test session
     */
    public static function updateAssignments(ilObjTest $test, ilTestSession $session): void
    {
        global $DIC;
        $db = $DIC->database();

        $ref_ids = ilObject::_getAllReferences($test->getId());

        /** @var self[] $assTests */
        $assTests = self::where($db->in('test_ref_id', $ref_ids, false, 'integer'))->get();
        foreach ($assTests as $assTest) {
            $assTest->storeResult($test, $session);
        }
    }

    /**
     * Get the number of a test pass for display
     */
    protected function getPassNumber(?int $pass = null): int|string
    {
        if (!isset($pass) || $pass < 0) {
            return '-';
        }
        return $pass + 1;
    }
}
