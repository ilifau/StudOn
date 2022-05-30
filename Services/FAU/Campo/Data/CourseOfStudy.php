<?php declare(strict_types=1);

namespace FAU\Campo\Data;

use FAU\RecordData;

/**
 * Record of the fau_campo_cos table
 */
class CourseOfStudy extends RecordData
{
    protected int $cos_id;
    protected ?string $degree;
    protected ?string $subject;
    protected ?string $major;
    protected ?string $subject_indicator;
    protected ?string $version;


    public static function getTableName() : string
    {
        return 'fau_campo_cos';
    }

    public static function getTableKeyTypes() : array
    {
        return [
            'cos_id' => 'integer'
        ];
    }

    public static function getTableOtherTypes() : array
    {
        return [
            'degree' => 'text',
            'subject' => 'text',
            'major' => 'text',
            'subject_indicator' => 'text',
            'version' => 'text'
        ];
    }

    public function getTableRow() : array {
        return [
            'cos_id' => $this->cos_id,
            'degree' => $this->degree,
            'subject' => $this->subject,
            'major' => $this->major,
            'subject_indicator' => $this->subject_indicator,
            'version' => $this->version
        ];
    }

    public function withTableRow(array $row) : self
    {
        $clone = clone $this;
        $clone->cos_id = (int) $row['cos_id'];
        $clone->degree =  $row['degree'] ?? null;
        $clone->subject = $row['subject'] ?? null;
        $clone->major = $row['major'] ?? null;
        $clone->subject_indicator = $row['subject_indicator'] ?? null;
        $clone->version = $row['version'] ?? null;
        return $clone;
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


    public function withCosId(int $cos_id) : CourseOfStudy
    {
        $clone = clone $this;
        $clone->cos_id = $cos_id;
        return $clone;
    }

    public function withDegree(?string $degree) : CourseOfStudy
    {
        $clone = clone $this;
        $clone->degree = $degree;
        return $clone;
    }

    public function withSubject(?string $subject) : CourseOfStudy
    {
        $clone = clone $this;
        $clone->subject = $subject;
        return $clone;
    }

    public function withMajor(?string $major) : CourseOfStudy
    {
        $clone = clone $this;
        $clone->major = $major;
        return $clone;
    }

    public function withSubjectIndicator(?string $subject_indicator) : CourseOfStudy
    {
        $clone = clone $this;
        $clone->subject_indicator = $subject_indicator;
        return $clone;
    }

    public function withVersion(?string $version) : CourseOfStudy
    {
        $clone = clone $this;
        $clone->version = $version;
        return $clone;
    }
}