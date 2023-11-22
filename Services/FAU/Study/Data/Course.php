<?php  declare(strict_types=1);

namespace FAU\Study\Data;

use FAU\RecordData;

/**
 * The Course represents a "parallel group" in campo
 * This is the instance of an event in an actual term
 * An event may have only one multiple courses in a term
 */
class Course extends RecordData
{
    public const SEND_PASSED_NONE = 'none';
    public const SEND_PASSED_LP = 'lp';
    
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
        'deleted' => 'integer',
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
        'target_group_all' => 'clob',
        'ilias_obj_id' => 'integer',
        'ilias_dirty_since' => 'text',
        'ilias_problem' => 'text',
        'send_passed' => 'text',
        'title_dirty' => 'integer',
        'description_dirty' => 'integer',
        'event_title_dirty' => 'integer',
        'event_description_dirty' => 'integer',
        'maximum_dirty' => 'integer',
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
    protected ?int $deleted;
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


    // not in constructor, added later, initialisation needed
    // obj_id is stored because ref_id may change when course is moved
    protected ?int $ilias_obj_id = null;
    protected ?string $ilias_dirty_since = null;
    protected ?string $ilias_problem = null;
    protected ?string $send_passed = self::SEND_PASSED_NONE;
    
    // dirty flags for changeable data in ILIAS course or group
    // will be set true in the campo sync if the underlying data is changed
    // this triggers the field update in the following ilias sync
    // afterwards the dirty flag is reset
    protected int $title_dirty = 0;
    protected int $description_dirty = 0;
    protected int $event_title_dirty = 0;
    protected int $event_description_dirty = 0;
    protected int $maximum_dirty = 0;

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
        ?int $deleted,
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
        $this->deleted = $deleted;
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
            null,null,null, null,
            null,null,null, null, null, null,
            null,null, null, null, null);
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
     * @return bool
     */
    public function isCancelled() : bool
    {
        return (bool) $this->cancelled;
    }

    /**
     * @return bool
     */
    public function isDeleted(): bool
    {
        return (bool) $this->deleted;
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
     * @return int|null
     */
    public function getIliasObjId() : ?int
    {
        return $this->ilias_obj_id;
    }


    /**
     * @return string|null
     */
    public function getIliasDirtySince() : ?string
    {
        return $this->ilias_dirty_since;
    }


    /**
     * @return string|null
     */
    public function getIliasProblem() : ?string
    {
        return $this->ilias_problem;
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

    /**
     * @param string|null $ilias_problem
     * @return Course
     */
    public function withIliasProblem(?string $ilias_problem) : self
    {
        $clone = clone $this;
        $clone->ilias_problem = $ilias_problem;
        return $clone;
    }


    /**
     * @param string|null $ilias_dirty_since
     * @return Course
     */
    public function withIliasDirtySince(?string $ilias_dirty_since) : self
    {
        $clone = clone $this;
        $clone->ilias_dirty_since = $ilias_dirty_since;
        return $clone;
    }


    /**
     * @param int|null $attendee_maximum
     * @return $this
     */
    public function withAttendeeMaximum(?int $attendee_maximum) : self
    {
        $clone = clone $this;
        $clone->attendee_maximum = $attendee_maximum;
        return $clone;
    }

    /**
     * @param bool $deleted
     * @return $this
     */
    public function withDeleted(bool $deleted) : self
    {
        $clone = clone $this;
        $clone->deleted = (int) $deleted;
        return $clone;
    }


    /**
     * Note that course data has changed
     * If there is an ILIAS course or group, this should force an update of the data
     * @param bool $changed
     * @return Course
     */
    public function asChanged(bool $changed) : self
    {
        $clone = clone $this;
        if ($changed) {
            if (isset($clone->ilias_obj_id) && !isset($clone->ilias_dirty_since)) {
                try {
                    $clone->ilias_dirty_since = (new \ilDateTime(time(), IL_CAL_UNIX))->get(IL_CAL_DATETIME);
                }
                catch (\Throwable $throwable) {
                }
            }
        }
        else {
            $clone->ilias_dirty_since = null;
            $clone->title_dirty = 0;
            $clone->description_dirty = 0;
            $clone->event_title_dirty = 0;
            $clone->event_description_dirty = 0;
            $clone->maximum_dirty = 0;
        }

        return $clone;
    }

    /**
     * @return string
     */
    public function getSendPassed(): string
    {
        return $this->send_passed ?? 'none';
    }

    /**
     * @param string $send_passed
     * @return Course
     */
    public function withSendPassed(string $send_passed): Course
    {
        $clone = clone $this;
        if (in_array($send_passed, [self::SEND_PASSED_LP, self::SEND_PASSED_NONE])) {
            $clone->send_passed = $send_passed;
        }
        return $clone;
    }

    /**
     * @return bool
     */
    public function isTitleDirty(): bool
    {
        return (bool) $this->title_dirty;
    }

    /**
     * @param bool $title_dirty
     * @return Course
     */
    public function withTitleDirty(bool $title_dirty): Course
    {
        $clone = clone $this;
        $clone->title_dirty = (int) $title_dirty;
        return $clone;
    }

    /**
     * @return bool
     */
    public function isDescriptionDirty(): bool
    {
        return (bool) $this->description_dirty;
    }

    /**
     * @param bool $description_dirty
     * @return Course
     */
    public function withDescriptionDirty(bool $description_dirty): Course
    {
        $clone = clone $this;
        $clone->description_dirty = (int) $description_dirty;
        return $clone;
    }

    /**
     * @return bool
     */
    public function isEventTitleDirty(): bool
    {
        return (bool) $this->event_title_dirty;
    }

    /**
     * @param bool $event_title_dirty
     * @return Course
     */
    public function withEventTitleDirty(bool $event_title_dirty): Course
    {
        $clone = clone $this;
        $clone->event_title_dirty = (int) $event_title_dirty;
        return $clone;
    }

    /**
     * @return bool
     */
    public function isEventDescriptionDirty(): bool
    {
        return (bool) $this->event_description_dirty;
    }

    /**
     * @param bool $event_description_dirty
     * @return Course
     */
    public function withEventDescriptionDirty(bool $event_description_dirty): Course
    {
        $clone = clone $this;
        $clone->event_description_dirty = (int) $event_description_dirty;
        return $clone;
    }

    /**
     * @return bool
     */
    public function isMaximumDirty(): bool
    {
        return (bool) $this->maximum_dirty;
    }

    /**
     * @param bool $maximum_dirty
     * @return Course
     */
    public function withMaximumDirty(bool $maximum_dirty): Course
    {
        $clone = clone $this;
        $this->maximum_dirty = (int) $maximum_dirty;
        return $clone;
    }
}
