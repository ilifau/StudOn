<?php  declare(strict_types=1);

namespace FAU\Study\Data;

use FAU\RecordData;

class CourseResponsible extends RecordData
{
    protected const tableName = 'fau_study_course_resps';
    protected const hasSequence = false;
    protected const keyTypes = [
        'course_id' => 'integer',
        'person_id' => 'integer',
    ];
    protected const otherTypes = [
        'sort_order' => 'integer'
    ];

    protected int $course_id;
    protected int $person_id;
    protected ?int $sort_order;

    public function __construct(
        int $course_id,
        int $person_id,
        ?int $sort_order = null
    )
    {
        $this->course_id = $course_id;
        $this->person_id = $person_id;
        $this->sort_order = $sort_order;

    }

    public static function model(): self
    {
        return new self(0,0, null);
    }

    /**
     * @return int
     */
    public function getCourseId() : int
    {
        return $this->course_id;
    }

    /**
     * @return int
     */
    public function getPersonId() : int
    {
        return $this->person_id;
    }


    /**
     * @return int|null
     */
    public function getSortOrder() : ?int
    {
        return $this->sort_order;
    }
}