<?php  declare(strict_types=1);

namespace FAU\Study\Data;

use FAU\RecordData;

/**
 * Remember a lost connection of a campo course to an ilias object
 * The connection may get lost if a campo course is set to nor releases
 * In this case the last connection is remembered and can be reused if the course is released again
 */
class LostCourse extends RecordData
{
    protected const tableName = 'fau_study_lost_courses';
    protected const hasSequence = false;
    protected const keyTypes = [
        'course_id' => 'integer',
    ];
    protected const otherTypes = [
        'ilias_obj_id' => 'integer',
    ];
    protected int $course_id;
    protected int $ilias_obj_id;

    public function __construct(
        int $course_id,
        int $ilias_obj_id
    )
    {
        $this->course_id = $course_id;
        $this->ilias_obj_id = $ilias_obj_id;
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
    public function getIliasObjId() : int
    {
        return $this->ilias_obj_id;
    }
}