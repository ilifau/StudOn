<?php
// fau: exAssTest - new class ilExAssTypeTestResultAssignment.


class ilExAssTypeTestResultAssignment extends ActiveRecord
{
    /**
     * @return string
     * @description Return the Name of your Database Table
     */
    public static function returnDbTableName()
    {
        return 'exc_ass_test_result';
    }

    /**
     * @var int
     * @con_is_primary true
     * @con_is_unique  true
     * @con_has_field  true
     * @con_fieldtype  integer
     * @con_is_notnull true
     * @con_length     4
     */
    protected $id;

    /**
     * @var int
     * @con_has_field  true
     * @con_fieldtype  integer
     * @con_length     4
     * @con_is_notnull true
     */
    protected $exercise_id = 0;

    /**
     * @var int
     * @con_has_field  true
     * @con_fieldtype  integer
     * @con_length     4
     * @con_is_notnull true
     */
    protected $test_ref_id = 0;

    /**
     * Wrapper to declare the return type
     * @param int   $primary_key
     * @param array $add_constructor_args
     * @return self
     */
    public static function findOrGetInstance($primary_key, array $add_constructor_args = array())
    {
        /** @var self $record */
        $record =  parent::findOrGetInstance($primary_key, $add_constructor_args);
        return $record;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id)
    {
        $this->id = $id;

        // reset the exercise id to force a lookup when record is stored
        $this->exercise_id = 0;
    }

    /**
     * @return int
     */
    public function getExerciseId()
    {
        return $this->exercise_id;
    }

    /**
     * @param int $exercise_id
     */
    public function setExerciseId(int $exercise_id)
    {
        $this->exercise_id = $exercise_id;
    }

    /**
     * Save the record
     * ensure the matching exercise id being saved
     */
    public function store() {
        if (empty($this->getExerciseId())) {
            $ass = new ilExAssignment($this->getId());
            $this->setExerciseId($ass->getExerciseId());
        }
        parent::store();
    }

    /**
     * @return int
     */
    public function getTestRefId()
    {
        return $this->test_ref_id;
    }

    /**
     * @param int $test_ref_id
     */
    public function setTestRefId( $test_ref_id)
    {
        $this->test_ref_id = $test_ref_id;
    }


    /**
     * Store the result of a test
     */
    public function storeResult(ilObjTest $test, ilTestSession $session)
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

        $user_ids = [];
        if ($state->isInTeam()) {
            $user_ids = $state->getTeamObject()->getMembers();
        }
        elseif (!empty($state->getTeamObject())) {
            $user_ids = [];
        }
        else {
            $user_ids = [$session->getUserId()];
        }

        $results = $test->getResultsForActiveId($session->getActiveId());

        $comments = [];
        $time = new ilDateTime(time(), IL_CAL_UNIX);
        ilDatePresentation::setUseRelativeDates(false);
        $comments[] = $lng->txt('label_time_transfer'). ilDatePresentation::formatDate($time);


        if (!empty($state->getTeamObject())) {
            $comments[] = $lng->txt('label_scored_participant'). ilObjUser::_lookupFullname($session->getUserId());
        }

        $comments[] = $lng->txt('label_scored_pass') . $this->getPassNumber(ilObjTest::_getResultPass($session->getActiveId()));
        $comments[] = $lng->txt('label_started_pass') . $this->getPassNumber($session->getLastStartedPass());
        $comments[] = $lng->txt('label_finished_pass') . $this->getPassNumber($session->getLastFinishedPass());

        foreach ($user_ids as $user_id) {
            $status = new ilExAssignmentMemberStatus($this->getId(), $user_id);
            $status->setStatus($results['passed'] ? 'passed' : 'failed');
            $status->setReturned(1);
            $status->setMark($results['reached_points']);
            $status->setComment(implode(' | ', $comments));
            $status->setNotice($results['mark_official']);
            if ($status->getFeedback() == null) {
                $status->setFeedback(0);
            }
            $status->update();
        }

        return $user_ids;
    }

    /**
     * Update the related assignments with the results of a test session
     * @param ilObjTest     $test
     * @param ilTestSession $session
     */
    public static function updateAssignments(ilObjTest $test, ilTestSession $session)
    {
        global $DIC;
        $db = $DIC->database();

        $ref_ids = ilObject::_getAllReferences($test->getId());

        /** @var  self[] $assTests */
        $assTests = self::where($db->in('test_ref_id', $ref_ids, false, 'integer'))->get();

        if (!empty($assTests)) {
            foreach ($assTests as $assTest) {
                $assTest->storeResult($test, $session);
            }
        }
    }

    /**
     * Get the numbr of a test pass for display
     * @param null $pass
     */
    protected function getPassNumber($pass = null)
    {
        if (!isset($pass) || $pass < 0) {
            return '-';
        }
        else {
            return (int) $pass + 1;
        }
    }
}