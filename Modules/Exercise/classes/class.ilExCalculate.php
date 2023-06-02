<?php

/**
 * fau: exCalc - new class for result calculation.
 *
 * @author Fred Neumann <fred.neumann@fau.de>
 * @ingroup ModulesExercise
*/
class ilExCalculate
{
    const FUNCTION_AVERAGE = 'average';
    const FUNCTION_SUM = 'sum';

    const SELECT_MARKED = 'marked';
    const SELECT_MANDATORY = 'mandatory';
    const SELECT_NUMBER = 'number';

    const ORDER_HIGHEST = 'highest';
    const ORDER_LOWEST = 'lowest';

    const COMPARE_LOWER = 'lower';
    const COMPARE_HIGHER = 'higher';
    const COMPARE_LOWER_EQUAL= 'lower_equal';
    const COMPARE_HIGHER_EQUAL = 'higher_equal';

    const BASE_PERCENT = 'percent';
    const BASE_ABSOLUTE = 'absolute';

    const STATUS_NOTGRADED = 'notgraded';
    const STATUS_PASSED = 'passed';
    const STATUS_FAILED = 'failed';


    /** @var string  */
    public $mark_function = self::FUNCTION_AVERAGE;

    /** @var string  */
    public $mark_select = self::SELECT_MARKED;

    /** @var string  */
    public $mark_select_order = self::ORDER_HIGHEST;

    /** @var integer */
    public $mark_select_count = 0;

    /** @var bool  */
    public $mark_force_zero = false;

    /** @var bool */
    public $status_calculate = false;

    /** @var string  */
    public $status_compare = self::COMPARE_HIGHER_EQUAL;

    /** @var float  */
    public $status_compare_value = 0;

    /** @var string  */
    public $status_compare_base = self::BASE_ABSOLUTE;

    /** @var string  */
    public $status_default = self::STATUS_NOTGRADED;

    /** @var ilObjExercise */
    protected $exercise;

    /**
     * @var ilExAssignment[]   indexed by assignment id
     * @see initAssignments
     */
    protected $assignments;

    
    /**
     * Constructor
     *
     * @param ilObjExercise	$exercise
     */
    public function __construct(ilObjExercise $exercise)
    {
        $this->exercise = $exercise;
        $this->readOptions();
    }

    /**
     * Set the exercise (needed for cloning exercises)
     * (options will nor be read)
     * @param ilObjExercise	$exercise
     */

    public function setExercise(ilObjExercise $exercise)
    {
        $this->exercise = $exercise;
    }
    
    /**
     * Read the options for this exercise from the database
     */
    public function readOptions()
    {
        global $DIC;
        $ilDB = $DIC->database();

        $query = 'SELECT * FROM exc_calc_options WHERE obj_id='
            . $ilDB->quote($this->exercise->getId(), 'integer');
        $result = $ilDB->query($query);
        
        while ($row = $ilDB->fetchAssoc($result)) {
            switch($row['option_key']) {
                case 'mark_function':
                    $this->mark_function = (string) $row['option_value'];
                    break;
                case 'mark_select':
                    $this->mark_select = (string) $row['option_value'];
                    break;
                case 'mark_select_order':
                    $this->mark_select_order = (string) $row['option_value'];
                    break;
                case 'mark_select_count':
                    $this->mark_select_count = (int) $row['option_value'];
                    break;
                case 'mark_force_zero':
                    $this->mark_force_zero = (bool) $row['option_value'];
                    break;
                case 'status_calculate':
                    $this->status_calculate = (bool) $row['option_value'];
                    break;
                case 'status_compare':
                    $this->status_compare = (string) $row['option_value'];
                    break;
                case 'status_compare_value':
                    $this->status_compare_value = (float) $row['option_value'];
                    break;
                case 'status_compare_base':
                    $this->status_compare_base = (string) $row['option_value'];
                    break;
                case 'status_default':
                    $this->status_default = (string) $row['option_value'];
                    break;
            }
        }
    }
    
    
    /**
     * write the options for this test to the datebase
     */
    public function writeOptions()
    {
        global $DIC;
        $ilDB = $DIC->database();
        
        $query = 'DELETE FROM exc_calc_options WHERE obj_id='
            . $ilDB->quote($this->exercise->getId(), 'integer');
        $ilDB->manipulate($query);

        $params = [
            ['mark_function', (string) $this->mark_function],
            ['mark_select', (string) $this->mark_select],
            ['mark_select_order', (string) $this->mark_select_order],
            ['mark_select_count',(string)  $this->mark_select_count],
            ['mark_force_zero', (string)  $this->mark_force_zero],
            ['status_calculate', (string) $this->status_calculate],
            ['status_compare', (string) $this->status_compare],
            ['status_compare_value', (string) $this->status_compare_value],
            ['status_compare_base', (string) $this->status_compare_base],
            ['status_default', (string) $this->status_default],
        ];

        $query = 'INSERT INTO exc_calc_options(obj_id, option_key, option_value) VALUES ('
            . $ilDB->quote($this->exercise->getId(), 'integer') . ', ?, ?)';

        $prepared = $ilDB->prepareManip($query, array('text', 'text'));
        $ilDB->executeMultiple($prepared, $params);

        // trigger status re-calculation
        if ($this->exercise->getPassMode() == ilObjExercise::PASS_MODE_CALC) {
            $this->exercise->updateAllUsersStatus();
        }
    }

    /**
     * Delete the options
     */
    public function deleteOptions()
    {
        global $DIC;
        $ilDB = $DIC->database();

        $query = 'DELETE FROM exc_calc_options WHERE obj_id='
            . $ilDB->quote($this->exercise->getId(), 'integer');
        $ilDB->manipulate($query);
    }

    /**
     * TODO: Generate a description text for the Calculation settings
     */
    public function getDescriptionText()
    {
        return '';
    }


    /**
     * Init the list of assignments for calculation
     * Prevent a read in the constructor
     */
    protected function initAssignments()
    {
        if (!isset($this->assignments)) {
            $this->assignments = [];
            foreach(ilExAssignment::getInstancesByExercise($this->exercise->getId()) as $assignment) {
                $this->assignments[$assignment->getId()] = $assignment;
            }
        }
    }

    /**
     * Get the available member status instances
     * @param array $a_usr_ids
     * @return ilExAssignmentMemberStatus[][] indexed by usr_id and ass_ids
     */
    protected function getMemberStatusInstances($a_usr_ids = [])
    {
        // get the list of assignments
        $this->initAssignments();
        $ass_ids = array_keys($this->assignments);

        // load the saved states
        $states = [];
        foreach (ilExAssignmentMemberStatus::getMultiple($a_usr_ids, $ass_ids) as $status) {

            // treat status as not yet marked if result time is not reached
            if ((int) $this->assignments[$status->getAssignmentId()]->getResultTime() > time()) {
                $status->setMark(null);
                $status->setStatus(self::STATUS_NOTGRADED);
                $status->setPlagFlag(ilExAssignmentMemberStatus::PLAG_NONE);
            }

            $states[$status->getUserId()][$status->getAssignmentId()] = $status;
        }


        return $states;
    }


    /**
     * Calculate the overall results and store them in the learning progress
     * @param int[]  $a_usr_ids list of user ids  (empty for all users)
     */
    public function calculateResults($a_usr_ids = [])
    {
        // get the list of users
        $usr_ids = (count($a_usr_ids) ? $a_usr_ids : ilExerciseMembers::_getMembers($this->exercise->getId()));

        // get the status objects
        $states = $this->getMemberStatusInstances($usr_ids);
        
        // calculate and write the overall mark and status
        foreach ($usr_ids as $usr_id) {
            $mark = $this->calculateMarkOfUser((array)  $states[$usr_id]);

            $marks_obj = new ilLPMarks($this->exercise->getId(), $usr_id);
            $marks_obj->setMark($mark);
            $marks_obj->update();
            
            if ($this->hasOpenResultTime()) {
                $status = self::STATUS_NOTGRADED;
            }
            elseif ($this->status_calculate) {
                $status = $this->calculateStatusByMark($mark);
            }
            else {
                $status = ilExerciseMembers::_lookupStatus($this->exercise->getId(), $usr_id);
            }

            // always save the status to set the current status time
            ilExerciseMembers::_writeStatus($this->exercise->getId(), $usr_id, $status);
        }
    }

    /**
     * Calculate the the preliminary status of a user (this is not saved)
     * @param int  $a_usr_id
     * @return string  $status
     */
    public function calculatePreliminaryStatus($a_usr_id)
    {
        if ($this->status_calculate) {
            $states = $this->getMemberStatusInstances([$a_usr_id]);
            $mark = $this->calculateMarkOfUser((array)  $states[$a_usr_id]);
            return $this->calculateStatusByMark($mark);
        }
        else {
            return ilExerciseMembers::_lookupStatus($this->exercise->getId(), $a_usr_id);
        }
    }

    /**
     * Check if at least one assignment has a not yet reached result time
     * @return bool
     */
    protected function hasOpenResultTime()
    {
        $this->initAssignments();
        foreach ($this->assignments as $ass) {
            if ((int) $ass->getResultTime() > time()) {
                return true;
            }
        }
        return false;
    }


    /**
     * calculate the mark depending on assignment results
     *
     * @param 	ilExAssignmentMemberStatus[]  $a_states  indexed by assignment id
     * @return	int		calculated mark (or null if mark couldn't be calculated)
     */
    protected function calculateMarkOfUser(array $a_states)
    {
        // lists of marks
        $selected = [];
        $candidates = [];
        
        // pre-selection of results
        foreach ($this->assignments as $ass_id => $assignment) {

            $mark = null;
            if (isset($a_states[$ass_id])) {
                $mark = $a_states[$ass_id]->getEffectiveMark(true);
            }

            // treat not marked results
            if (!isset($mark)) {
                if ($this->mark_force_zero) {
                    $mark = 0;
                }
                elseif ($assignment->getMandatory()) {
                    // mandatory mark not available
                    return null;
                }
                else {
                    // mark can't be used for calculation
                    continue;
                }
            }

            if ($assignment->getMandatory()) {
                // always take the mandatory assignments
                // this includes the case SELECT_MANDATORY
                $selected[] = $mark;

            } elseif ($this->mark_select == self::SELECT_MARKED) {
                $selected[] = $mark;

            } elseif ($this->mark_select == self::SELECT_NUMBER) {
                $candidates[] = $mark;
            }

        }

        // selection of candidates
        if ($this->mark_select == self::SELECT_NUMBER) {
            $needed = $this->mark_select_count;
            if (count($selected) + count($candidates) < $needed) {
                // not enough marks available
                return null;
            } elseif (count($selected) < $needed) {
                sort($candidates, SORT_NUMERIC);
                if ($this->mark_select_order == self::ORDER_HIGHEST) {
                    $candidates = array_reverse($candidates);
                }
                
                for ($i = 0; $i < $needed - count($selected); $i++) {
                    $selected[] = $candidates[$i];
                }
            }
        }

        if (count($selected) == 0) {
            return null;
        }
        
        // calculation of the mark
        $sum = 0;
        foreach ($selected as $mark) {
            $sum += $mark;
        }
        switch ($this->mark_function) {
            case self::FUNCTION_SUM:
                return $sum;

            case self::FUNCTION_AVERAGE:
                return round($sum / count($selected), 2);

            default:
                return null;
        }
    }
    
    
    /**
     * calculate the status depending on the mark
     *
     * @param 	float 	$a_mark
     * @return	string	learning progress status
     */
    protected function calculateStatusByMark($a_mark = null)
    {
        if (!isset($a_mark)) {
            return $this->status_default;
        }
        
        $mark = (float) $a_mark;

        if ($this->status_compare_base == self::BASE_ABSOLUTE) {
            $value = (float) $this->status_compare_value;
        }
        else {
            $max = 0;
            foreach ($this->assignments as $ass) {
                $max += (int) $ass->getMaxPoints();
            }
            $value = (float) $max * $this->status_compare_value;
        }

        switch ($this->status_compare) {
            case self::COMPARE_HIGHER:
                return $mark > $value ? self::STATUS_PASSED : self::STATUS_FAILED;

            case self::COMPARE_LOWER:
                return $mark < $value ? self::STATUS_PASSED : self::STATUS_FAILED;

            case self::COMPARE_HIGHER_EQUAL:
                return $mark >= $value ? self::STATUS_PASSED : self::STATUS_FAILED;

            case self::COMPARE_LOWER_EQUAL:
                return $mark <= $value ? self::STATUS_PASSED : self::STATUS_FAILED;
        }

        return self::STATUS_NOTGRADED;
    }
}
