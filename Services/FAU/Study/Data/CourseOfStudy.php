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
     * Majors that have been added by withAddedMajor() function
     * This is not saved, but needed for a sync
     * @var string[]
     */
    protected $added_majors = [];

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

    /**
     * Get majors that have been added by withAddedMajor() function
     * @return string[]
     */
    public function getAddedMajors() : array
    {
        return $this->added_majors;
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
    public function getTitle() : string
    {
        return $this->getSubject() . ', ' . $this->getDegree() . ', ' . $this->getSubjectIndicator() . ', ' . implode('|', $this->getMajors()) . ', ' . $this->getVersion();
    }



    /**
     * @param string|null $degree
     * @return CourseOfStudy
     */
    public function withDegree(?string $degree) : self
    {
        $clone = clone($this);
        $clone->degree = $degree;
        return $clone;
    }

    /**
     * @param string|null $subject
     * @return CourseOfStudy
     */
    public function withSubject(?string $subject) : self
    {
        $clone = clone($this);
        $clone->subject = $subject;
        return $clone;
    }

    /**
     * @param string|null $subject_indicator
     * @return CourseOfStudy
     */
    public function withSubjectIndicator(?string $subject_indicator) : self
    {
        $clone = clone($this);
        $clone->subject_indicator = $subject_indicator;
        return $clone;
    }

    /**
     * Add a major to the list of majors
     * @param string[] $majors
     * @return $this
     */
    public function withMajors(array $majors) : self
    {
        $clone = clone($this);
        $clone->majors = serialize($majors);
        return $clone;
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

        $added_majors = $clone->getAddedMajors();
        $added_majors[] = $major;
        sort($added_majors);
        $clone->added_majors = array_unique($added_majors);

        return $clone;
    }


    /**
     * @param string|null $version
     * @return CourseOfStudy
     */
    public function withVersion(?string $version) : self
    {
        $clone = clone($this);
        $clone->version = $version;
        return $clone;
    }
}