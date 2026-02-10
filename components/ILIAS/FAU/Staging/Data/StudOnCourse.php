<?php  declare(strict_types=1);

namespace FAU\Staging\Data;

use FAU\RecordData;

class StudOnCourse extends RecordData
{
    protected const tableName = 'studon_courses';
    protected const hasSequence = false;
    protected const keyTypes = [
        'course_id' => 'integer',
    ];
    protected const otherTypes = [
        'attendee_maximum' => 'integer',
        'term_year' => 'integer',
        'term_type_id' => 'integer',
        'studon_ref_id' => 'integer',
        'reg_period' => 'text',
        'tutors' => 'text'
    ];

    protected int $course_id;
    protected ?int $attendee_maximum;
    protected int $term_year;
    protected int $term_type_id;
    protected ?int $studon_ref_id; 
    protected ?string $reg_period;
    protected ?string $tutors;

    public function __construct(
        int $course_id,
        ?int $attendee_maximum,
        int $term_year,
        int $term_type_id,
        ?int $studon_ref_id,
        ?string $reg_period,
        ?string $tutors
    )
    {
        $this->course_id = $course_id;
        $this->attendee_maximum = $attendee_maximum;
        $this->term_year = $term_year;
        $this->term_type_id = $term_type_id;
        $this->studon_ref_id = $studon_ref_id;
        $this->reg_period = $reg_period;
        $this->tutors = $tutors;
    }

    public static function model(): self
    {
        return new self(0,0,0,0, NULL, '', '');
    }

    /**
     * @return int
     */
    public function getCourseId() : int
    {
        return $this->course_id;
    }

    /**
     * @return int|null
     */
    public function getAttendeeMaximum() : ?int
    {
        return $this->attendee_maximum;
    }

    /**
     * @return int
     */
    public function getTermYear() : int
    {
        return $this->term_year;
    }

    /**
     * @return int
     */
    public function getTermTypeId() : int
    {
        return $this->term_type_id;
    }

    /**
     * @param int|null $attendee_maximum
     * @return StudOnCourse
     */
    public function withAttendeeMaximum(?int $attendee_maximum): StudOnCourse
    {
        $clone = clone $this;
        $clone->attendee_maximum = $attendee_maximum;
        return $clone;
    }

    /**
     * @return ?int
     */
    public function getStudOnRefId() : ?int
    {
        return $this->studon_ref_id;
    }    

    /**
     * @param int|null $ref_id
     * @return StudOnCourse
     */
    public function withStudOnRefId(?int $ref_id): StudOnCourse
    {
        $clone = clone $this;
        $clone->studon_ref_id = $ref_id;
        return $clone;
    }

    /**
     * @return string
     */
    public function getRegPeriod() : string
    {
        return $this->reg_period ?? "";
    }    

    /**
     * @param string $reg_period
     * @return StudOnCourse
     */
    public function withRegPeriod(string $reg_period): StudOnCourse
    {
        $clone = clone $this;
        $clone->reg_period = $reg_period;
        return $clone;
    }    

    /**
     * @return string
     */
    public function getTutors() : string
    {
        return $this->tutors ?? "";
    }    

    /**
     * @param string $tutors
     * @return StudOnCourse
     */
    public function withTutors(string $tutors): StudOnCourse
    {
        $clone = clone $this;
        $clone->tutors = $tutors;
        return $clone;
    }      
}