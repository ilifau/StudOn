<?php
/* fau: studyData - new class ilStudyCourseCond. */

require_once(__DIR__ . '/abstract/class.ilStudyCond.php');

/**
 * Conditions related to a course of studies
 */
class ilStudyCourseCond extends ilStudyCond
{
    /** @inheritdoc  */
    protected static $cache;


    /** @var integer */
    public $school_id;

    /** @var integer */
    public $subject_id;

    /** @var integer */
    public $degree_id;

    /** @var integer */
    public $min_semester;

    /** @var integer */
    public $max_semester;

    /** @var integer */
    public $ref_semester;

    /** @var integer */
    public $study_type;


    /**
     * @inheritDoc
     */
    public static function _read($obj_id) : array
    {
        global $DIC;
        $ilDB = $DIC->database();

        $query = "SELECT * FROM il_study_course_cond ".
            "WHERE obj_id = ".$ilDB->quote($obj_id, 'integer');
        $result = $ilDB->query($query);

        $conditions = [];
        while ($row = $ilDB->fetchAssoc($result))
        {
            $cond = new static;
            $cond->setRowData($row);
            $conditions[] = $cond;
        }
        return $conditions;
    }

    /**
     * @inheritDoc
     */
    public static function _count($obj_id) : int
    {
        global $DIC;
        $ilDB = $DIC->database();

        $query = "SELECT count(*) num FROM il_study_course_cond ".
            "WHERE obj_id = ".$ilDB->quote($obj_id, 'integer');
        $result = $ilDB->query($query);

        if ($row = $ilDB->fetchAssoc($result)) {
            return $row['num'];
        }
        return 0;
    }

    /**
     * @inheritDoc
     */
    public static function _delete($obj_id)
    {
        global $DIC;
        $ilDB = $DIC->database();

        $query = "DELETE FROM il_study_course_cond WHERE obj_id = ".$ilDB->quote($obj_id, 'integer');
        $ilDB->manipulate($query);
    }

    /**
     * @inheritDoc
     */
    public function getText() : string
    {
        global $DIC;
        $lng = $DIC->language();

        require_once('Services/StudyData/classes/class.ilStudyCourseData.php');
        require_once('Services/StudyData/classes/class.ilStudyOptionDegree.php');
        require_once('Services/StudyData/classes/class.ilStudyOptionSubject.php');

        $reftext = ilStudyCourseData::_getRefSemesterText($this->ref_semester);
        $ctext = [];
        if ($this->subject_id)
        {
            $ctext[] = ilStudyOptionSubject::_lookupText($this->subject_id);
        }
        if ($this->degree_id)
        {
            $ctext[] = ilStudyOptionDegree::_lookupText($this->degree_id);
        }
        if ($this->min_semester and $this->max_semester)
        {
            $ctext[] = sprintf($lng->txt('studycond_min_max_semester'), $this->min_semester, $this->max_semester, $reftext);
        }
        elseif ($this->min_semester)
        {
            $ctext[] = sprintf($lng->txt('studycond_min_semester'), $this->min_semester, $reftext);
        }
        elseif ($this->max_semester)
        {
            $ctext[] = sprintf($lng->txt('studycond_max_semester'), $this->max_semester, $reftext);
        }
        if ($type_text = ilStudyCourseData::_getStudyTypeText($this->study_type))
        {
            $ctext[] = $type_text;
        }

        return implode($lng->txt('studycond_criteria_delimiter') .' ', $ctext);
    }


    /**
     * @inheritDoc
     */
    public function read()
    {
        global $DIC;
        $ilDB = $DIC->database();

        $query = "SELECT * FROM il_study_course_cond ".
            "WHERE cond_id = ".$ilDB->quote($this->cond_id, 'integer');

        $result = $ilDB->query($query);
        if ($row = $ilDB->fetchAssoc($result))
        {
            $this->setRowData($row);
        }
    }


    /**
     * @inheritDoc
     */
    public function write()
    {
        global $DIC;
        $ilDB = $DIC->database();

        if (empty($this->cond_id)) {
           $this->cond_id = $ilDB->nextId('il_study_course_cond');
        }

        $query = "REPLACE INTO il_study_course_cond ("
            ."cond_id, obj_id, school_id, subject_id, degree_id, max_semester, min_semester, ref_semester, study_type) "
            ."VALUES("
            .$ilDB->quote($this->cond_id, 'integer').", "
            .$ilDB->quote($this->obj_id, 'integer') .", "
            .$ilDB->quote($this->school_id, 'integer') .", "
            .$ilDB->quote($this->subject_id, 'integer') .", "
            .$ilDB->quote($this->degree_id, 'integer') .", "
            .$ilDB->quote($this->max_semester, 'integer') .", "
            .$ilDB->quote($this->min_semester, 'integer') .", "
            .$ilDB->quote($this->ref_semester, 'text') .", "
            .$ilDB->quote($this->study_type, 'text') .")";
        $ilDB->manipulate($query);
    }


    /**
     * Set the data from an assoc array
     *
     * @param   array   $a_row assoc array of data
     */
    private function setRowData($a_row = [])
    {
        $this->cond_id = $a_row['cond_id'];
        $this->obj_id = $a_row['obj_id'];
        $this->school_id = $a_row['subject_id'];
        $this->subject_id = $a_row['subject_id'];
        $this->degree_id = $a_row['degree_id'];
        $this->min_semester = $a_row['min_semester'];
        $this->max_semester = $a_row['max_semester'];
        $this->ref_semester = $a_row['ref_semester'];
        $this->study_type = $a_row['study_type'];
    }

    /**
     * Check the study course data for the condition
     * Only one study course needs to be satisfied
     * @param ilStudyCourseData[] $data
     * @return bool
     */
    public function check(array $data) : bool
    {
        $cond_offset = ilStudyAccess::_getRunningSemesterOffset($this->ref_semester);

        foreach ($data as $study) {
            // check the criteria for each study
            // all defined criteria must be satisfied
            // continue with next study on failure

            // check validity of the ref_semester in the study data
            // too old or new ref_semester will fail all conditions
            $stud_offset = ilStudyAccess::_getRunningSemesterOffset($study->ref_semester);
            if ($stud_offset < -1 or $stud_offset > 1) {
                continue; // failed
            }

            // check school
            // use modulus 10 because e.g. PhilFak has the coding 1, 11, 21 etc.
            if ($this->school_id and ($this->school_id % 10 != $study->school_id % 10)) {
                continue; // failed
            }

            // check degree
            if ($this->degree_id and ($this->degree_id != $study->degree_id)) {
                continue; // failed
            }

            // check type
            if ($this->study_type and ($this->study_type != $study->study_type)) {
                continue; // failed
            }

            // check subjects and semester
            // only one subject/semester combination must fit
            $subject_semester_passed = false;
            foreach ($study->subjects as $subject) {
                if ($this->subject_id and ($this->subject_id != $subject->subject_id))
                {
                    continue; // failed
                }

                if ($this->min_semester) {
                    if (empty($subject->semester) or ($this->min_semester + $cond_offset > $subject->semester + $stud_offset))
                    {
                        continue; // failed
                    }
                }

                if ($this->max_semester) {
                    if (empty($subject->semester) or ($this->max_semester + $cond_offset < $subject->semester + $stud_offset)) {
                        continue; // failed
                    }
                }

                // this subject/semester combination fits
                $subject_semester_passed = true;
                break;
            }

            // this study fits
            if ($subject_semester_passed) {
                return true;
            }
        }

        // none of the studies fits
        return false;
    }
}