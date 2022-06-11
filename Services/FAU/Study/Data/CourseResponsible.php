<?php  declare(strict_types=1);

namespace FAU\Study\Data;

use FAU\RecordData;

class CourseResponsible extends RecordData
{
    protected const tableName = 'fau_study_course_responsibles';
    protected const hasSequence = false;
    protected const keyTypes = [
        'course_id' => 'integer',
        'person_id' => 'integer',
    ];
    protected const otherTypes = [
    ];

    protected int $course_id;
    protected int $person_id;

    public function __construct(
        int $course_id,
        int $person_id
    )
    {
        $this->course_id = $course_id;
        $this->person_id = $person_id;
    }

    public static function model(): self
    {
        return new self(0,0);
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
}