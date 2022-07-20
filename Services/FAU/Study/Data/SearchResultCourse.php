<?php

namespace FAU\Study\Data;

use FAU\RecordData;

class SearchResultCourse extends RecordData
{
    protected const otherTypes = [
        'course_id' => 'integer',
        'course_title' => 'text',
        'course_shorttext' => 'text',
        'group_number' => 'integer',
        'hours_per_week' => 'float',
        'attendee_maximum' => 'integer',
        'cancelled' => 'integer',
        'obj_id' => 'integer',
        'ref_id' => 'integer'
    ];

    // from initial query
    protected int $course_id;
    protected ?string $course_title;
    protected ?string $course_shorttext;
    protected ?int $group_number;
    protected ?float $hours_per_week;
    protected ?int $attendee_maximum;
    protected ?int $cancelled;
    protected ?int $obj_id;
    protected ?int $ref_id;

    public function __construct (
        int $course_id,
        ?string $course_title,
        ?string $course_shorttext,
        ?int $group_number,
        ?float $hours_per_week,
        ?int $attendee_maximum,
        ?int $cancelled,
        ?int $obj_id,
        ?int $ref_id
    )
    {
        $this->course_id = $course_id;
        $this->course_title = $course_title;
        $this->course_shorttext = $course_shorttext;
        $this->group_number = $group_number;
        $this->hours_per_week = $hours_per_week;
        $this->attendee_maximum = $attendee_maximum;
        $this->cancelled = $cancelled;
        $this->obj_id = $obj_id;
        $this->ref_id = $ref_id;
    }

    public static function model() : self
    {
        return new self(0, null, null, null,
            null, null, null, null, null);
    }

    /**
     * @return int
     */
    public function getCourseId() : int
    {
        return $this->course_id;
    }

    /**
     * @return string|null
     */
    public function getCourseTitle() : ?string
    {
        return $this->course_title;
    }

    /**
     * @return string|null
     */
    public function getCourseShorttext() : ?string
    {
        return $this->course_shorttext;
    }

    /**
     * @return int|null
     */
    public function getGroupNumber() : ?int
    {
        return $this->group_number;
    }

    /**
     * @return float|null
     */
    public function getHoursPerWeek() : ?float
    {
        return $this->hours_per_week;
    }

    /**
     * @return int|null
     */
    public function getAttendeeMaximum() : ?int
    {
        return $this->attendee_maximum;
    }

    /**
     * @return int|null
     */
    public function getCancelled() : ?int
    {
        return $this->cancelled;
    }

    /**
     * @return int|null
     */
    public function getObjId() : ?int
    {
        return $this->obj_id;
    }

    /**
     * @return int|null
     */
    public function getRefId() : ?int
    {
        return $this->ref_id;
    }
}