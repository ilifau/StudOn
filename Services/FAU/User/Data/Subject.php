<?php

namespace FAU\User\Data;

class Subject
{
    private ?int $subjectnumber;
    private ?int $studySemester;

    private ?int $subjectDbId;
    private ?string $subjectIndicatorId;
    private ?int $placeOfStudiesDbId;
    private ?int $examinationDbId;
    private ?int $courseOfStudyDbId;
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
    public function getFacultyId() : ?string
    {
        return $this->facultyId;
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