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
     * Submit a test result
     *
     * @param int $user_id
     * @param bool $passed
     * @param float $points
     * @param string $mark_short
     * @param string $mark_official
     * @param int $tstamp
     * @return array    affected user ids
     */
    public function submitResult($user_id, $passed, $points, $mark_short, $mark_official, $tstamp)
    {
        // check if assignment still exists
        // now test relationship is deleted with an assignment
        // but formerly it may not have be cleaned up if an assignment is deleted
        // the test queries just by its own ref id and calls submitResult for all found records
        // a deleted assignment will result in learning progress error in status update
        $assignment = new ilExAssignment($this->getId());
        if (empty($assignment->getExerciseId())) {
            return [];
        }

        $state = ilExcAssMemberState::getInstanceByIds($this->getId(), $user_id);

        $user_ids = [];
//        if ($state->isSubmissionAllowed()) {

            if ($state->isInTeam()) {
                $user_ids = $state->getTeamObject()->getMembers();
            }
            elseif (!empty($state->getTeamObject())) {
                $user_ids = [];
            }
            else {
                $user_ids = [$user_id];
            }

            foreach ($user_ids as $user_id) {
                $status = new ilExAssignmentMemberStatus($this->getId(), $user_id);
                $status->setStatus($passed ? 'passed' : 'failed');
                $status->setReturned(1);
                $status->setMark($points);
                $status->setComment($mark_official);
                $status->setNotice($mark_official);
                if ($status->getFeedback() == null) {
                    $status->setFeedback(0);
                }
                $status->update();
            }
//        }

        return $user_ids;
    }
}