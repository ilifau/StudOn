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
        'compulsory_requirement' => 'text',
        'contents' => 'clob',
        'literature' => 'text',
        'ilias_obj_id' => 'integer',
        'ilias_dirty_since' => 'text',
        'ilias_problem' => 'text'
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
    protected ?string $compulsory_requirement;
    protected ?string $contents;
    protected ?string $literature;

    // not in constructor, added later, initialisation needed
    // obj_id is stored because ref_id may change when course is moved
    protected ?int $ilias_obj_id = null;
    protected ?string $ilias_dirty_since = null;
    protected ?string $ilias_problem = null;

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
        ?string $compulsory_requirement,
        ?string $contents,
        ?string $literature
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
        $this->compulsory_requirement = $compulsory_requirement;
        $this->contents = $contents;
        $this->literature = $literature;
    }

    public static function model(): self
    {
        return new self(0,null,null,
            null,null,null,null,
            null,null,null, null,
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
        }

        return $clone;
    }
}