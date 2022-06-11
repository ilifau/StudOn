<?php  declare(strict_types=1);

namespace FAU\Study\Data;

use FAU\RecordData;

class StudyEnrolment extends RecordData
{
    protected const tableName = 'fau_study_enrolments';
    protected const hasSequence = false;
    protected const keyTypes = [
        'enrolment_id' => 'integer',
    ];
    protected const otherTypes = [
        'enrolment_uniquename' => 'text',
        'enrolment_title' => 'text',
        'enrolment_title_en' => 'text',
    ];

    protected int $enrolment_id;
    protected string $enrolment_uniquename;
    protected string $enrolment_title;
    protected ?string $enrolment_title_en;

    public function __construct(
        int $enrolment_id,
        string $enrolment_uniquename,
        string $enrolment_title,
        ?string $enrolment_title_en
    )
    {
        $this->enrolment_id = $enrolment_id;
        $this->enrolment_uniquename = $enrolment_uniquename;
        $this->enrolment_title = $enrolment_title;
        $this->enrolment_title_en = $enrolment_title_en;
    }

    public static function model(): self
    {
        return new self(0,'','',null);
    }

    /**
     * @return int
     */
    public function getEnrolmentId() : int
    {
        return $this->enrolment_id;
    }

    /**
     * @return string
     */
    public function getEnrolmentUniquename() : string
    {
        return $this->enrolment_uniquename;
    }

    /**
     * @return string
     */
    public function getEnrolmentTitle() : string
    {
        return $this->enrolment_title;
    }

    /**
     * @return string|null
     */
    public function getEnrolmentTitleEn() : ?string
    {
        return $this->enrolment_title_en;
    }
}