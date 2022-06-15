<?php  declare(strict_types=1);

namespace FAU\Study\Data;

use FAU\RecordData;

class Course extends RecordData
{
    protected const tableName = 'fau_study_courses';
    protected const hasSequence = false;
    protected const keyTypes = [
        'course_id' => 'integer',
    ];
    protected const otherTypes = [
        'event_id' => 'integer',
        'term_year' => 'integer',
        'term_type_id' => 'integer',
        'k_parallelgroup_id' => 'integer',
        'title' => 'text',
        'shorttext' => 'text',
        'hours_per_week' => 'float',
        'attendee_maximum' => 'integer',
        'cancelled' => 'integer',
        'teaching_language' => 'text',
        'compulsory_requirement' => 'text',
        'contents' => 'clob',
        'literature' => 'text',
        'ilias_obj_id' => 'integer'
    ];
    protected int $course_id;
    protected ?int $event_id;
    protected ?int $term_year;
    protected ?int $term_type_id;
    protected ?int $k_parallelgroup_id;
    protected ?string $title;
    protected ?string $shorttext;
    protected ?float $hours_per_week;
    protected ?int $attendee_maximum;
    protected ?int $cancelled;
    protected ?string $teaching_language;
    protected ?string $compulsory_requirement;
    protected ?string $contents;
    protected ?string $literature;
    protected ?int $ilias_obj_id;

    public function __construct(
        int $course_id,
        ?int $event_id,
        ?int $term_year,
        ?int $term_type_id,
        ?int $k_parallelgroup_id,
        ?string $title,
        ?string $shorttext,
        ?float $hours_per_week,
        ?int $attendee_maximum,
        ?int $cancelled,
        ?string $teaching_language,
        ?string $compulsory_requirement,
        ?string $contents,
        ?string $literature,
        ?int $ilias_obj_id
    )
    {
        $this->course_id = $course_id;
        $this->event_id = $event_id;
        $this->term_year = $term_year;
        $this->term_type_id = $term_type_id;
        $this->k_parallelgroup_id = $k_parallelgroup_id;
        $this->title = $title;
        $this->shorttext = $shorttext;
        $this->hours_per_week = $hours_per_week;
        $this->attendee_maximum = $attendee_maximum;
        $this->cancelled = $cancelled;
        $this->teaching_language = $teaching_language;
        $this->compulsory_requirement = $compulsory_requirement;
        $this->contents = $contents;
        $this->literature = $literature;
        $this->ilias_obj_id = $ilias_obj_id;
    }

    public static function model(): self
    {
        return new self(0,null,null,
            null,null,null,null,
            null,null,null,
            null,null,null,null);
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
    public function getEventId() : ?int
    {
        return $this->event_id;
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
     * @return int|null
     */
    public function getKParallelgroupId() : ?int
    {
        return $this->k_parallelgroup_id;
    }

    /**
     * @return string|null
     */
    public function getTitle() : ?string
    {
        return $this->title;
    }

    /**
     * @return string|null
     */
    public function getShorttext() : ?string
    {
        return $this->shorttext;
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
     * @return string|null
     */
    public function getTeachingLanguage() : ?string
    {
        return $this->teaching_language;
    }

    /**
     * @return string|null
     */
    public function getCompulsoryRequirement() : ?string
    {
        return $this->compulsory_requirement;
    }

    /**
     * @return string|null
     */
    public function getContents() : ?string
    {
        return $this->contents;
    }

    /**
     * @return string|null
     */
    public function getLiterature() : ?string
    {
        return $this->literature;
    }

    /**
     * @return int|null
     */
    public function getIliasObjId() : ?int
    {
        return $this->ilias_obj_id;
    }

    /**
     * @param int|null $ilias_obj_id
     * @return Course
     */
    public function withIliasObjId(?int $ilias_obj_id) : self
    {
        $clone = clone $this;
        $clone->ilias_obj_id = $ilias_obj_id;
        return $clone;
    }
}