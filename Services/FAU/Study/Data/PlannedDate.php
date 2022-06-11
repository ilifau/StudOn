<?php  declare(strict_types=1);

namespace FAU\Study\Data;

use FAU\RecordData;

class PlannedDate extends RecordData
{
    protected const tableName = 'fau_study_planned_dates';
    protected const hasSequence = false;
    protected const keyTypes = [
        'planned_dates_id' => 'integer',
    ];
    protected const otherTypes = [
        'course_id' => 'integer',
        'term_year' => 'integer',
        'term_type_id' => 'integer',
        'rhythm' => 'text',
        'starttime' => 'time',
        'endtime' => 'time',
        'academic_time' => 'text',
        'startdate' => 'date',
        'enddate' => 'date',
        'famos_code' => 'text',
        'expected_attendees' => 'integer',
        'comment' => 'text',
    ];
    
    protected int $planned_dates_id;
    protected ?int $course_id;
    protected ?int $term_year;
    protected ?int $term_type_id;
    protected ?string $rhythm;
    protected ?string $starttime;
    protected ?string $endtime;
    protected ?string $academic_time;
    protected ?string $startdate;
    protected ?string $enddate;
    protected ?string $famos_code;
    protected ?int $expected_attendees;
    protected ?string $comment;

    public function __construct(
        int $planned_dates_id,
        ?int $course_id,
        ?int $term_year,
        ?int $term_type_id,
        ?string $rhythm,
        ?string $starttime,
        ?string $endtime,
        ?string $academic_time,
        ?string $startdate,
        ?string $enddate,
        ?string $famos_code,
        ?int $expected_attendees,
        ?string $comment
    )
    {
        $this->planned_dates_id = $planned_dates_id;
        $this->course_id = $course_id;
        $this->term_year = $term_year;
        $this->term_type_id = $term_type_id;
        $this->rhythm = $rhythm;
        $this->starttime = $starttime;
        $this->endtime = $endtime;
        $this->academic_time = $academic_time;
        $this->startdate = $startdate;
        $this->enddate = $enddate;
        $this->famos_code = $famos_code;
        $this->expected_attendees = $expected_attendees;
        $this->comment = $comment;
    }

    public static function model(): self
    {
        return new self(0, null, null, null, null, null, null,
            null, null, null, null, null, null);
    }

    /**
     * @return int
     */
    public function getPlannedDatesId() : int
    {
        return $this->planned_dates_id;
    }

    /**
     * @return int|null
     */
    public function getCourseId() : ?int
    {
        return $this->course_id;
    }

    /**
     * @return int|null
     */
    public function getTermYear() : ?int
    {
        return $this->term_year;
    }

    /**
     * @return int|null
     */
    public function getTermTypeId() : ?int
    {
        return $this->term_type_id;
    }

    /**
     * @return string|null
     */
    public function getRhythm() : ?string
    {
        return $this->rhythm;
    }

    /**
     * @return string|null
     */
    public function getStarttime() : ?string
    {
        return $this->starttime;
    }

    /**
     * @return string|null
     */
    public function getEndtime() : ?string
    {
        return $this->endtime;
    }

    /**
     * @return string|null
     */
    public function getAcademicTime() : ?string
    {
        return $this->academic_time;
    }

    /**
     * @return string|null
     */
    public function getStartdate() : ?string
    {
        return $this->startdate;
    }

    /**
     * @return string|null
     */
    public function getEnddate() : ?string
    {
        return $this->enddate;
    }

    /**
     * @return string|null
     */
    public function getFamosCode() : ?string
    {
        return $this->famos_code;
    }

    /**
     * @return int|null
     */
    public function getExpectedAttendees() : ?int
    {
        return $this->expected_attendees;
    }

    /**
     * @return string|null
     */
    public function getComment() : ?string
    {
        return $this->comment;
    }
}