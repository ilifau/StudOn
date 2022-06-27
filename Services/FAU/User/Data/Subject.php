<?php

namespace FAU\User\Data;

class Subject
{
    private ?int $subjectnumber;
    private ?int $studySemester;

    // integer database ids, corresponding to the his_ids in the value tables
    // these ids are not shown, but used for conditions
    private ?int $subjectDbId;
    private ?string $subjectIndicatorId;
    private ?int $placeOfStudiesDbId;
    private ?int $examinationDbId;
    private ?int $courseOfStudyDbId;

    // string ids, corresponding to the uniquenames in the value tables
    // these ids are shown in the textual study data and in value lists
    private ?string $subjectId;
    private ?string $facultyId;

    private string $subjectName;
    private string $subjectIndicatorName;
    private string $examinationName;
    private string $placeOfStudiesName;
    private string $courseOfStudyName;
    private string $facultyName;


    public function __construct (array $data) {
        $this->subjectnumber = isset($data['subjectnumber']) ? (int) $data['subjectnumber'] : null;
        $this->studySemester = isset($data['studySemester']) ? (int) $data['studySemester'] : null;

        $this->subjectDbId = isset($data['subjectDbId']) ? (int) $data['subjectDbId'] : null;
        $this->subjectIndicatorId = isset($data['subjectIndicatorId']) ? (string) $data['subjectIndicatorId'] : null;
        $this->examinationDbId = isset($data['examinationDbId']) ? (int) $data['examinationDbId'] : null;
        $this->placeOfStudiesDbId = isset($data['placeOfStudiesDbId']) ? (int) $data['placeOfStudiesDbId'] : null;
        $this->courseOfStudyDbId = isset($data['courseOfStudyDbId']) ? (int) $data['courseOfStudyDbId'] : null;

        $this->subjectId = isset($data['subjectId']) ? (string) $data['subjectId'] : null;
        $this->facultyId = isset($data['facultyId']) ? (string) $data['facultyId'] : null;

        $this->subjectName = isset($data['subjectName']) ? (string) $data['subjectName'] : '';
        $this->subjectIndicatorName = isset($data['subjectIndicatorName']) ? (string) $data['subjectIndicatorName'] : '';
        $this->examinationName = isset($data['examinationName']) ? (string) $data['examinationName'] : '';
        $this->placeOfStudiesName = isset($data['placeOfStudiesName']) ? (string) $data['placeOfStudiesName'] : '';
        $this->courseOfStudyName = isset($data['courseOfStudyName']) ? (string) $data['courseOfStudyName'] : '';
        $this->facultyName = isset($data['facultyName']) ? (string) $data['facultyName'] : '';
    }

    /**
     * @return int|null
     */
    public function getSubjectnumber() : ?int
    {
        return $this->subjectnumber;
    }

    /**
     * @return int|null
     */
    public function getStudySemester() : ?int
    {
        return $this->studySemester;
    }

    /**
     * @return int|null
     */
    public function getSubjectDbId() : ?int
    {
        return $this->subjectDbId;
    }

    /**
     * @return string|null
     */
    public function getSubjectIndicatorId() : ?string
    {
        return $this->subjectIndicatorId;
    }

    /**
     * @return int|null
     */
    public function getPlaceOfStudiesDbId() : ?int
    {
        return $this->placeOfStudiesDbId;
    }

    /**
     * @return int|null
     */
    public function getExaminationDbId() : ?int
    {
        return $this->examinationDbId;
    }

    /**
     * @return int|null
     */
    public function getCourseOfStudyDbId() : ?int
    {
        return $this->courseOfStudyDbId;
    }


    /**
     * @return string|null
     */
    public function getSubjectId() : ?string
    {
        return $this->subjectId;
    }

    /**
     * @return string|null
     */
    public function getFacultyId() : ?string
    {
        return $this->facultyId;
    }

    /**
     * Calculate the school id from the faculty id
     *
     * @return string|null
     */
    public function getCalculatedSchoolId() : ?string
    {
        if (!isset($this->facultyId)) {
            return null;
        }

        // remove trailing zeroes and convert to integer
        $number = (int) str_replace('0', '', $this->facultyId);

        // use modulus 10 because e.g. PhilFak has the coding 1, 11, 21 etc.
        $number = $number % 10;

        //re-convert to string to be comparable with a uniquename
        return (string) $number;
    }

    /**
     * @return string
     */
    public function getSubjectName() : string
    {
        return $this->subjectName;
    }

    /**
     * @return string
     */
    public function getSubjectIndicatorName() : string
    {
        return $this->subjectIndicatorName;
    }

    /**
     * @return string
     */
    public function getExaminationName() : string
    {
        return $this->examinationName;
    }

    /**
     * @return string
     */
    public function getPlaceOfStudiesName() : string
    {
        return $this->placeOfStudiesName;
    }

    /**
     * @return string
     */
    public function getCourseOfStudyName() : string
    {
        return $this->courseOfStudyName;
    }

    /**
     * @return string
     */
    public function getFacultyName() : string
    {
        return $this->facultyName;
    }
}