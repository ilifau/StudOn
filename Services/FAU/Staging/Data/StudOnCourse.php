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
    ];

    protected int $course_id;
    protected ?int $attendee_maximum;
    protected int $term_year;
    protected int $term_type_id;

    public function __construct(
        int $course_id,
        ?int $attendee_maximum,
        int $term_year,
        int $term_type_id
    )
    {
        $this->course_id = $course_id;
        $this->attendee_maximum = $attendee_maximum;
        $this->term_year = $term_year;
        $this->term_type_id = $term_type_id;

    }

    public static function model(): self
    {
        return new self(0,0,0,0);
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

}