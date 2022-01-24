<?php
/* fau: studyData - new class ilStudyCourseData. */

require_once(__DIR__ . '/abstract/class.ilStudyData.php');
require_once(__DIR__ . '/class.ilStudyCourseSubject.php');

/**
* Data of a course of studies
*/
class ilStudyCourseData extends ilStudyData
{
    const TYPE_FULL = "V";
    const TYPE_PART = "T";
    const TYPE_NONE = "";

    /** @inheritdoc */
    protected static $cache;

    /** @var integer */
    public $study_no;

    /** @var integer */
    public $school_id;

    /** @var integer */
    public $degree_id;

    /** @var string */
    public $ref_semester;

    /** @var string */
    public $study_type;

    /** @var ilStudyCourseSubject[] */
    public $subjects = [];


    /**
     * @inheritDoc
     */
    public static function _read($user_id) : array
    {
        global $DIC;
        $ilDB = $DIC->database();

        $query = 'SELECT usr_id, study_no, school_id, degree_id, ref_semester, study_type'
            . ' FROM usr_study'
            . ' WHERE usr_id=' . $ilDB->quote($user_id, 'integer')
            . ' ORDER BY study_no ASC';
        $result = $ilDB->query($query);

        $courses = [];
        while ($row = $ilDB->fetchAssoc($result)) {
            $course = new static;
            $course->user_id = $row['usr_id'];
            $course->study_no = $row['study_no'];
            $course->school_id = $row['school_id'];
            $course->degree_id = $row['degree_id'];
            $course->ref_semester = $row['ref_semester'];
            $course->study_type = $row['study_type'];
            $course->subjects = ilStudyCourseSubject::_read($user_id, $course->study_no);
            $courses[] = $course;
        }
        return $courses;
    }

    /**
     * @inheritDoc
     */
    public static function _count($user_id) : int
    {
        global $DIC;
        $ilDB = $DIC->database();

        $query = "SELECT count(*) num FROM usr_study WHERE usr_id = " . $ilDB->quote($user_id, 'integer');
        $result = $ilDB->query($query);

        if ($row = $ilDB->fetchAssoc($result)) {
            return $row['num'];
        }
        return 0;
    }

    /**
     * @inheritDoc
     */
    public static function _delete($user_id)
    {
        global $DIC;
        $ilDB = $DIC->database();

        $query = "DELETE FROM usr_study WHERE usr_id = " . $ilDB->quote($user_id, 'integer');
        $ilDB->manipulate($query);

        ilStudyCourseSubject::_delete($user_id);
    }

    /**
     * @inheritDoc
     */
    public function getText() : string
    {
        require_once "Services/StudyData/classes/class.ilStudyOptionDegree.php";
        require_once "Services/StudyData/classes/class.ilStudyOptionSchool.php";
        require_once "Services/StudyData/classes/class.ilStudyOptionDocProgram.php";

        $text = static::_getRefSemesterText($this->ref_semester);
        if ($type_text = self::_getStudyTypeText($this->study_type)) {
            $text .= ' (' . $type_text . ')';
        }
        $text .= ':';

        $subject_texts = [];
        foreach ($this->subjects as $subject) {
            $subject_texts[] = $subject->getText();
        }
        if (!empty($subject_texts)) {
            $text .= " \n" . implode(', ', $subject_texts);
        }
        if ($degree_text = ilStudyOptionDegree::_lookupText($this->degree_id)) {
            $text .= " \n" . $degree_text;
        }
        if ($school_text = ilStudyOptionSchool::_lookupText($this->school_id)) {
            $text .= " \n" . $school_text;
        }
        return $text;
    }


    /**
     * @inheritDoc
     */
    public function write()
    {
        global $DIC;
        $ilDB = $DIC->database();

        $ilDB->replace(
            'usr_study',
            [
                'usr_id' => ['integer', $this->user_id],
                'study_no' => ['integer', $this->study_no]
            ],
            [
                'school_id' => ['integer', $this->school_id],
                'degree_id' => ['integer', $this->degree_id],
                'ref_semester' => ['text', $this->ref_semester],
                'study_type' => ['text', $this->study_type]
            ]
        );

        foreach ($this->subjects as $subject) {
            $subject->write();
        }
    }


    /**
     * Get an array of option for a study type
     *
     * @return array	type_code => type title
     */
    public static function _getStudyTypeSelectOptions()
    {
        global $lng;
        return [
            self::TYPE_NONE => $lng->txt("please_select"),
            self::TYPE_FULL => $lng->txt('studydata_type_full'),
            self::TYPE_PART => $lng->txt('studydata_type_part')
        ];
    }

    /**
     * get an array of option for a semester
     *
     * @return array	semester code (e.g. 20112) => semester title (2011 SS)
     */
    public static function _getSemesterSelectOptions()
    {
        global $lng;
    
        $options[''] = $lng->txt("please_select");
        for ($year = 2010; $year < date('Y') + 10; $year++) {
            $options[(string) $year . '1'] = sprintf('%s SS', $year);
            $options[(string) $year . '2'] = sprintf('%s / %s WS', $year, $year + 1);
        }
        return $options;
    }



    /**
     * get the text for a reference semester
     *
     * @param 	string	semester specification (e.g. 20112)
     * @return 	string	textual description
     */
    public static function _getRefSemesterText($a_ref_semester)
    {
        global $DIC;
        $lng = $DIC->language();
        
        if (substr($a_ref_semester, 4) == '1') {
            $reftext = sprintf($lng->txt('studydata_ref_semester_summer'), substr($a_ref_semester, 0, 4));
        } elseif (substr($a_ref_semester, 4) == '2') {
            $sem = (int) substr($a_ref_semester, 0, 4);
            $reftext = sprintf($lng->txt('studydata_ref_semester_winter'), $sem, $sem + 1);
        } else {
            $reftext = sprintf($lng->txt('studydata_ref_semester_any'));
        }

        return $reftext;
    }

    /**
     * get the text for a study type
     * @param  string $a_study_type
     * @return string
     */
    public static function _getStudyTypeText($a_study_type)
    {
        global $DIC;
        $lng = $DIC->language();

        switch ($a_study_type) {
            case self::TYPE_PART:
                return $lng->txt('studydata_type_part');

            case ilStudyCourseData::TYPE_FULL:
                return $lng->txt('studydata_type_full');

        }
        return '';
    }
}
