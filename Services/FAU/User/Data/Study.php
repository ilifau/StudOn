<?php

namespace FAU\User\Data;

class Study
{
    private ?int $studynumber;
    private ?string $period;

    private ?int $degreeDbId;
    private ?string $typeOfStudyId;
    private ?int $enrollmentDbId;
    private ?int $formOfStudiesDbId;
    private ?int $studentstatusDbId;

    private string $degreeName;
    private string $degreeShort;
    private string $typeOfStudyName;
    private string $enrollmentName;
    private string $formOfStudiesName;
    private string $studentstatusName;

    private array $subjects = [];

    public function __construct(array $data)
    {
        $this->studynumber = isset($data['studynumber']) ? (int) $data['studynumber'] : null;
        $this->period = isset($data['$period']) ? (string) $data['$period'] : null;

        $this->degreeDbId = isset($data['degreeDbId']) ? (int) $data['degreeDbId'] : null;
        $this->typeOfStudyId = isset($data['typeOfStudyId']) ? (string) $data['typeOfStudyId'] : null;
        $this->enrollmentDbId = isset($data['enrollmentDbId']) ? (int) $data['enrollmentDbId'] : null;
        $this->formOfStudiesDbId = isset($data['formOfStudiesDbId']) ? (int) $data['formOfStudiesDbId'] : null;
        $this->studentstatusDbId = isset($data['studentstatusDbId']) ? (int) $data['studentstatusDbId'] : null;

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
     * @return string|null
     */
    public function getTypeOfStudyId() : ?string
    {
        return $this->typeOfStudyId;
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