<?php

namespace FAU\User\Data;

use FAU\Study\Data\Term;

class Study
{
    protected ?int $studynumber;
    protected ?string $period;

    // integer database ids, corresponding to the his_ids in the value tables
    // these ids are not shown, but used for conditions
    protected ?int $degreeDbId;
    protected ?int $enrollmentDbId;
    protected ?int $formOfStudiesDbId;
    protected ?int $studentstatusDbId;

    // string ids, corresponding to the uniquenames in the value tables
    // these ids are shown in the textual study data
    protected ?string $degreeId;
    protected ?string $typeOfStudyId;
    protected ?string $enrollmentId;
    protected ?string $formOfStudiesId;
    protected ?string $studentstatusId;

    protected string $degreeName;
    protected string $degreeShort;
    protected string $typeOfStudyName;
    protected string $enrollmentName;
    protected string $formOfStudiesName;
    protected string $studentstatusName;

    protected array $subjects = [];

    public function __construct(array $data)
    {
        $this->studynumber = isset($data['studynumber']) ? (int) $data['studynumber'] : null;
        $this->period = isset($data['period']) ? (string) $data['period'] : null;

        $this->degreeDbId = isset($data['degreeDbId']) ? (int) $data['degreeDbId'] : null;
        $this->enrollmentDbId = isset($data['enrollmentDbId']) ? (int) $data['enrollmentDbId'] : null;
        $this->formOfStudiesDbId = isset($data['formOfStudiesDbId']) ? (int) $data['formOfStudiesDbId'] : null;
        $this->studentstatusDbId = isset($data['studentstatusDbId']) ? (int) $data['studentstatusDbId'] : null;

        $this->degreeId = isset($data['degreeId']) ? (string) $data['degreeId'] : null;
        $this->typeOfStudyId = isset($data['typeOfStudyId']) ? (string) $data['typeOfStudyId'] : null;
        $this->enrollmentId = isset($data['enrollmentId']) ? (string) $data['enrollmentId'] : null;
        $this->formOfStudiesId = isset($data['formOfStudiesId']) ? (string) $data['formOfStudiesId'] : null;
        $this->studentstatusId = isset($data['studentstatusId']) ? (string) $data['studentstatusId'] : null;

        $this->degreeName = isset($data['degreeName']) ? (string) $data['degreeName'] : '';
        $this->degreeShort = isset($data['degreeShort']) ? (string) $data['degreeShort'] : '';
        $this->typeOfStudyName = isset($data['typeOfStudyName']) ? (string) $data['typeOfStudyName'] : '';
        $this->enrollmentName = isset($data['enrollmentName']) ? (string) $data['enrollmentName'] : '';
        $this->formOfStudiesName = isset($data['formOfStudiesName']) ? (string) $data['formOfStudiesName'] : '';
        $this->studentstatusName = isset($data['studentstatusName']) ? (string) $data['studentstatusName'] : '';

        if (isset($data['subjects'])) {
            foreach ((array) $data['subjects'] as $index => $subdata) {
                $subject = new Subject($subdata);
                // get subjects in order of their number but avoid overwriting if number is empty
                $this->subjects[sprintf('%02d.%02d', (int) $subject->getSubjectnumber(), $index)] = $subject;
            }
        }
    }

    /**
     * @return string|null
     */
    public function getPeriod() : ?string
    {
        return $this->period;
    }

    public function getTerm() : ?Term
    {
        return Term::fromString($this->period);
    }

    /**
     * @return int|null
     */
    public function getStudynumber() : ?int
    {
        return $this->studynumber;
    }

    /**
     * @return int|null
     */
    public function getDegreeDbId() : ?int
    {
        return $this->degreeDbId;
    }


    /**
     * @return int|null
     */
    public function getEnrollmentDbId() : ?int
    {
        return $this->enrollmentDbId;
    }

    /**
     * @return int|null
     */
    public function getFormOfStudiesDbId() : ?int
    {
        return $this->formOfStudiesDbId;
    }

    /**
     * @return int|null
     */
    public function getStudentstatusDbId() : ?int
    {
        return $this->studentstatusDbId;
    }

    /**
     * @return string|null
     */
    public function getDegreeId() : ?string
    {
        return $this->degreeId;
    }

    /**
     * @return string|null
     */
    public function getTypeOfStudyId() : ?string
    {
        return $this->typeOfStudyId;
    }

    /**
     * @return string|null
     */
    public function getEnrollmentId() : ?string
    {
        return $this->enrollmentId;
    }

    /**
     * @return string|null
     */
    public function getFormOfStudiesId() : ?string
    {
        return $this->formOfStudiesId;
    }

    /**
     * @return string|null
     */
    public function getStudentstatusId() : ?string
    {
        return $this->studentstatusId;
    }

    /**
     * @return string
     */
    public function getDegreeName() : string
    {
        return $this->degreeName;
    }

    /**
     * @return string
     */
    public function getDegreeShort() : string
    {
        return $this->degreeShort;
    }

    /**
     * @return string
     */
    public function getTypeOfStudyName() : string
    {
        return $this->typeOfStudyName;
    }

    /**
     * @return string
     */
    public function getEnrollmentName() : string
    {
        return $this->enrollmentName;
    }

    /**
     * @return string
     */
    public function getFormOfStudiesName() : string
    {
        return $this->formOfStudiesName;
    }

    /**
     * @return string
     */
    public function getStudentstatusName() : string
    {
        return $this->studentstatusName;
    }

    /**
     * @return Subject[]
     */
    public function getSubjects() : array
    {
        return $this->subjects;
    }
}