<?php  declare(strict_types=1);

namespace FAU\Staging\Data;

use FAU\RecordData;

class Course extends RecordData
{
    protected const tableName = 'campo_course';
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
        'contents' => 'clob',
        'literature' => 'clob',
        'recommended_requirement' => 'clob',
        'learning_target' => 'clob',
        'target_group' => 'clob',
        'contents_all' => 'clob',
        'literature_all' => 'clob',
        'recommended_requirement_all' => 'clob',
        'learning_target_all' => 'clob',
        'target_group_all' => 'clob'

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
    protected ?string $contents;
    protected ?string $literature;
    protected ?string $recommended_requirement;
    protected ?string $learning_target;
    protected ?string $target_group;
    protected ?string $contents_all;
    protected ?string $literature_all;
    protected ?string $recommended_requirement_all;
    protected ?string $learning_target_all;
    protected ?string $target_group_all;


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
        ?string $contents,
        ?string $literature,
        ?string $recommended_requirement,
        ?string $learning_target,
        ?string $target_group,
        ?string $contents_all,
        ?string $literature_all,
        ?string $recommended_requirement_all,
        ?string $learning_target_all,
        ?string $target_group_all
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
        $this->contents = $contents;
        $this->literature = $literature;
        $this->recommended_requirement = $recommended_requirement;
        $this->learning_target = $learning_target;
        $this->target_group = $target_group;
        $this->contents_all = $contents_all;
        $this->literature_all = $literature_all;
        $this->recommended_requirement_all = $recommended_requirement_all;
        $this->learning_target_all = $learning_target_all;
        $this->target_group_all = $target_group_all;
    }

    public static function model(): self
    {
        return new self(0,null,null,
            null,null,null,null,
            null,null,null,
            null,null,null,null, null, null,
            null,null,null, null, null);
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
     * @return string|null
     */
    public function getRecommendedRequirement(): ?string
    {
        return $this->recommended_requirement;
    }

    /**
     * @return string|null
     */
    public function getLearningTarget(): ?string
    {
        return $this->learning_target;
    }

    /**
     * @return string|null
     */
    public function getTargetGroup(): ?string
    {
        return $this->target_group;
    }
    
    /**
     * @return string|null
     */
    public function getContentsAll() : ?string
    {
        return $this->contents_all;
    }

    /**
     * @return string|null
     */
    public function getLiteratureAll() : ?string
    {
        return $this->literature_all;
    }

    /**
     * @return string|null
     */
    public function getRecommendedRequirementAll(): ?string
    {
        return $this->recommended_requirement_all;
    }

    /**
     * @return string|null
     */
    public function getLearningTargetAll(): ?string
    {
        return $this->learning_target_all;
    }

    /**
     * @return string|null
     */
    public function getTargetGroupAll(): ?string
    {
        return $this->target_group_all;
    }
    
    /**
     * @return bool
     */
    public function getDeleted() : bool
    {
        return false;
    }
}
