<?php declare(strict_types=1);

namespace FAU\Study\Data;

use FAU\RecordData;

/**
 * Record of the fau_study_cos table
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
        'majors' => 'text',
        'subject_indicator' => 'text',
        'version' => 'text'
    ];

    protected int $cos_id;
    protected ?string $degree;
    protected ?string $subject;
    protected ?string $majors;
    protected ?string $subject_indicator;
    protected ?string $version;


    /**
     * @param int         $cos_id
     * @param string|null $degree
     * @param string|null $subject
     * @param string[]|null  $majors
     * @param string|null $subject_indicator
     * @param string|null $version
     */
    public function __construct(
        int $cos_id,
        ?string $degree,
        ?string $subject,
        ?array $majors,
        ?string $subject_indicator,
        ?string $version
    )
    {
        $this->cos_id = $cos_id;
        $this->degree = $degree;
        $this->subject = $subject;
        $this->majors = is_array($majors) ? serialize($majors) : serialize([]);
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

    /**
     * @return string[]
     */
    public function getMajors() : array
    {
        return isset($this->majors) ? unserialize($this->majors) : [];
    }


    public function getSubjectIndicator() : ?string
    {
        return $this->subject_indicator;
    }

    public function getVersion() : ?string
    {
        return $this->version;
    }

    /**
     * Get a textual title
     */
    public function getTitle($show_multiple_majors = false) : string
    {
        if (count($this->getMajors()) < 2 || $show_multiple_majors) {
            return $this->getSubject() . ', ' . $this->getDegree() . ', ' . $this->getSubjectIndicator() . ', ' . implode('|', $this->getMajors()) . ', ' . $this->getVersion();
        }
        else {
            return $this->getSubject() . ', ' . $this->getDegree() . ', ' . $this->getSubjectIndicator() .  ', ' . $this->getVersion();
        }
    }


    /**
     * Add a major to the list of majors
     * @param string $major
     * @return $this
     */
    public function withAddedMajor(string $major) : self
    {
        $clone = clone($this);

        $majors = $clone->getMajors();
        $majors[] = $major;
        sort($majors);
        $clone->majors = serialize(array_unique($majors));

        return $clone;
    }
}