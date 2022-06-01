<?php declare(strict_types=1);

namespace FAU\Study\Data;

use FAU\RecordData;

/**
 * Record of the fau_campo_cos table
 */
class CourseOfStudy extends RecordData
{
    protected const tableName = 'fau_study_cos';
    protected const hasSequence = false;
    protected const keyTypes = [
        'cos_id' => 'integer'
    ];
    protected const otherTypes = [
        'degree' => 'text',
        'subject' => 'text',
        'major' => 'text',
        'subject_indicator' => 'text',
        'version' => 'text'
    ];

    protected int $cos_id;
    protected ?string $degree;
    protected ?string $subject;
    protected ?string $major;
    protected ?string $subject_indicator;
    protected ?string $version;

    public function __construct(
        int $cos_id,
        ?string $degree,
        ?string $subject,
        ?string $major,
        ?string $subject_indicator,
        ?string $version
    )
    {
        $this->cos_id = $cos_id;
        $this->degree = $degree;
        $this->subject = $subject;
        $this->major = $major;
        $this->subject_indicator = $subject_indicator;
        $this->version = $version;
    }

    public static function model(): self
    {
        return new self(0,null,null,null,null,null);
    }

    public function getCosId() : int
    {
        return $this->cos_id;
    }

    public function getDegree() : ?string
    {
        return $this->degree;
    }

    public function getSubject() : ?string
    {
        return $this->subject;
    }

    public function getMajor() : ?string
    {
        return $this->major;
    }

    public function getSubjectIndicator() : ?string
    {
        return $this->subject_indicator;
    }

    public function getVersion() : ?string
    {
        return $this->version;
    }
}