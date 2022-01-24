<?php
/* fau: studyData - new class ilStudyCourseSubject. */
/**
 * Subjects of a course of studies
 */
class ilStudyCourseSubject
{
    /** @var integer */
    public $user_id;

    /** @var integer */
    public $study_no;

    /** @var integer */
    public $subject_no;

    /** @var integer */
    public $subject_id;

    /** @var integer */
    public $semester;

    /**
     * Get the subjects for a user and a course of studies
     *
     * @param int $user_id
     * @param int $study_no
     * @return static[]
     */
    public static function _read($user_id, $study_no) : array
    {
        global $DIC;
        $ilDB = $DIC->database();

        $query = 'SELECT usr_id, study_no, subject_no, subject_id, semester'
            . ' FROM usr_subject'
            . ' WHERE usr_id=' . $ilDB->quote($user_id, 'integer')
            . ' AND study_no=' . $ilDB->quote($study_no, 'integer')
            . ' ORDER BY subject_no ASC';
        $result = $ilDB->query($query);

        $subjects = [];
        while ($row = $ilDB->fetchAssoc($result)) {
            $subject = new static;
            $subject->user_id = $row['usr_id'];
            $subject->study_no = $row['study_no'];
            $subject->subject_no = $row['subject_no'];
            $subject->subject_id = $row['subject_id'];
            $subject->semester = $row['semester'];
            $subjects[] = $subject;
        }
        return $subjects;
    }


    /**
     * Delete study course subjects
     * @param integer $user_id
     * @param integer $study_no
     */
    public static function _delete($user_id, $study_no = null)
    {
        global $DIC;
        $ilDB = $DIC->database();

        $query = "DELETE FROM usr_subject WHERE usr_id = " . $ilDB->quote($user_id, 'integer');

        if (isset($study_no)) {
            $query .= " AND study_no = " . $ilDB->quote($study_no, 'integer');
        }
        $ilDB->manipulate($query);
    }

    /**
     * Get the text for the subject
     * @return string
     */
    public function getText()
    {
        global $DIC;
        $lng = $DIC->language();

        require_once "Services/StudyData/classes/class.ilStudyOptionSubject.php";

        $text = ilStudyOptionSubject::_lookupText($this->subject_id);
        $text .= sprintf($lng->txt('studydata_semester_text'), $this->semester);

        return $text;
    }

    /**
     * Write the subject data
     */
    public function write()
    {
        global $DIC;
        $ilDB = $DIC->database();

        $ilDB->replace(
            'usr_subject',
            [
                'usr_id' => ['integer', $this->user_id],
                'study_no' => ['integer', $this->study_no],
                'subject_id' => ['integer', $this->subject_id]
            ],
            [
                'subject_no' => ['integer', $this->subject_no],
                'semester' => ['integer', $this->semester]

            ]
        );
    }
}
