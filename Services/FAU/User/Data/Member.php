<?php declare(strict_types=1);

namespace FAU\User\Data;

use FAU\RecordData;
use FAU\Sync\SyncWithIlias;

/**
 * Member status of an ILIAS user in an ilias course or group concerning campo
 *
 * This record exists only if the ilias object (course or group) and the ilias user exist
 * It is created and updated in the synchronisation of ilias courses and groups
 * It is created when a new user is created in ilias which has related campo data
 *
 * Compared with the course or group participants this table reflects the updates from campo
 * Person assignments in campo should be saved here and the ilias course or group roles if their users should be updated
 * ILIAS users which are directly added in the ilias course and group membership administration should not be touched
 *
 * Admins/Tutors:
 * Event responsibles should become course admins
 * Course responsibles and instructors should become course admins or course tutors and group admins
 *
 * Members:
 * Members get their record once they selected a module for the course
 *
 * @see SyncWithIlias::updateIliasCourse()
 * @see SyncWithIlias::updateIliasGroup()
 */
class Member extends RecordData
{
    const ROLE_EVENT_RESPONSIBLE = 'event_responsible';
    const ROLE_COURSE_RESPONSIBLE = 'course_responsible';
    const ROLE_INSTRUCTOR = 'instructor';
    const ROLE_INDIVIDUAL_INSTRUCTOR = 'individual_instructor';


    protected const tableName = 'fau_user_members';
    protected const hasSequence = false;
    protected const keyTypes = [
        'obj_id' => 'integer',
        'user_id' => 'integer',
    ];
    protected const otherTypes = [
        'module_id' => 'integer',
        'event_responsible' => 'integer',
        'course_responsible' => 'integer',
        'instructor' => 'integer',
        'individual_instructor' => 'integer',
    ];

    protected int $obj_id;
    protected int $user_id;
    protected ?int $module_id = null;
    private bool $event_responsible = false;
    private bool $course_responsible = false;
    private bool $instructor = false;
    private bool $individual_instructor = false;

    public function __construct(
        int $obj_id,
        int $user_id,
        ?int $module_id = null ,
        bool $event_responsible = false,
        bool $course_responsible = false,
        bool $instructor = false,
        bool $individual_instructor = false
    )
    {
        $this->obj_id = $obj_id;
        $this->user_id = $user_id;
        $this->module_id = $module_id;

        $this->event_responsible = $event_responsible;
        $this->course_responsible = $course_responsible;
        $this->instructor = $instructor;
        $this->individual_instructor = $individual_instructor;
    }

    public static function model(): self
    {
        return new self(0,0);
    }

    /**
     * @return int
     */
    public function getObjId() : int
    {
        return $this->obj_id;
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
     * @return bool
     */
    public function isEventResponsible() : bool
    {
        return $this->event_responsible;
    }

    /**
     * @return bool
     */
    public function isCourseResponsible() : bool
    {
        return $this->course_responsible;
    }

    /**
     * @return bool
     */
    public function isInstructor() : bool
    {
        return $this->instructor;
    }

    /**
     * @return bool
     */
    public function isIndividualInstructor() : bool
    {
        return $this->individual_instructor;
    }

    /**
     * @param int|null $module_id
     * @return Member
     */
    public function withModuleId(?int $module_id) : Member
    {
        $clone = clone $this;
        $clone->module_id = $module_id;
        return $clone;
    }

    /**
     * @param bool $event_responsible
     * @return Member
     */
    public function withEventResponsible(bool $event_responsible) : Member
    {
        $clone = clone $this;
        $clone->event_responsible = $event_responsible;
        return $clone;
    }

    /**
     * @param bool $course_responsible
     * @return Member
     */
    public function withCourseResponsible(bool $course_responsible) : Member
    {
        $clone = clone $this;
        $clone->course_responsible = $course_responsible;
        return $clone;
    }

    /**
     * @param bool $instructor
     * @return Member
     */
    public function withInstructor(bool $instructor) : Member
    {
        $clone = clone $this;
        $clone->instructor = $instructor;
        return $clone;
    }

    /**
     * @param bool $individual_instructor
     * @return Member
     */
    public function withIndividualInstructor(bool $individual_instructor) : Member
    {
        $clone = clone $this;
        $clone->individual_instructor = $individual_instructor;
        return $clone;
    }

    /**
     * Check if the member has either a specific role or a selected module
     */
    public function hasData() : bool
    {
        return (
            isset($this->module_id)
            || $this->event_responsible
            || $this->course_responsible
            || $this->instructor
            || $this->individual_instructor
        );
    }

    /**
     * Check if a certain role is set
     */
    public function hasRole(string $role) : bool
    {
        return (bool) $this->$role;
    }

    /**
     * Set a certain role
     */
    public function withRole(string $role, bool $flag) : Member
    {
        $clone = clone($this);
        $clone->$role = $flag;
        return $clone;
    }
}