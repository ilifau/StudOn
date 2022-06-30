<?php declare(strict_types=1);

namespace FAU\User\Data;

use FAU\RecordData;

class Member extends RecordData
{
    protected const tableName = 'fau_user_members';
    protected const hasSequence = false;
    protected const keyTypes = [
        'course_id' => 'integer',
        'user_id' => 'integer',
    ];
    protected const otherTypes = [
        'module_id' => 'integer',
        'event_responsible' => 'integer',
        'course_responsible' => 'integer',
        'instructor' => 'integer',
        'individual_instructor' => 'integer',
    ];

    protected int $course_id;
    protected int $user_id;
    protected ?int $module_id;
    protected int $event_responsible;
    protected int $course_responsible;
    protected int $instructor;
    protected int $individual_instructor;

    public function __construct(
        int $course_id,
        int $user_id,
        ?int $module_id,
        int $event_responsible,
        int $course_responsible,
        int $instructor,
        int $individual_instructor
    )
    {
        $this->course_id = $course_id;
        $this->user_id = $user_id;
        $this->module_id = $module_id;
        $this->event_responsible = $event_responsible;
        $this->course_responsible = $course_responsible;
        $this->instructor = $instructor;
        $this->individual_instructor = $individual_instructor;
    }

    public static function model(): self
    {
        return new self(0,0,null,0,0,0,0);
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
    public function getUserId() : int
    {
        return $this->user_id;
    }

    /**
     * @return int|null
     */
    public function getModuleId() : ?int
    {
        return $this->module_id;
    }

    /**
     * @return int
     */
    public function getEventResponsible() : int
    {
        return $this->event_responsible;
    }

    /**
     * @return int
     */
    public function getCourseResponsible() : int
    {
        return $this->course_responsible;
    }

    /**
     * @return int
     */
    public function getInstructor() : int
    {
        return $this->instructor;
    }

    /**
     * @return int
     */
    public function getIndividualInstructor() : int
    {
        return $this->individual_instructor;
    }

}