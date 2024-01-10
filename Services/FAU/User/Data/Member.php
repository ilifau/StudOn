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
 * Person assignments in campo should be saved here and the ilias course or group roles of their users should be updated
 * ILIAS users which are directly maintained in the ilias course and group membership administration should not be touched
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
    const CONTEXT_SINGLE_COURSE = 'single_course';
    const CONTEXT_PARENT_COURSE = 'parent_course';
    const CONTEXT_NESTED_GROUP = 'nested_group';
    
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
    protected int $event_responsible = 0;
    protected int $course_responsible = 0;
    protected int $instructor = 0;
    protected int $individual_instructor = 0;

    public function __construct(
        int $obj_id,
        int $user_id,
        ?int $module_id = null,
        bool $event_responsible = false,
        bool $course_responsible = false,
        bool $instructor = false,
        bool $individual_instructor = false
    )
    {
        $this->obj_id = $obj_id;
        $this->user_id = $user_id;
        $this->module_id = $module_id;

        $this->event_responsible = (int) $event_responsible;
        $this->course_responsible = (int) $course_responsible;
        $this->instructor = (int) $instructor;
        $this->individual_instructor = (int) $individual_instructor;
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
        return (bool) $this->event_responsible;
    }

    /**
     * @return bool
     */
    public function isCourseResponsible() : bool
    {
        return (bool) $this->course_responsible;
    }

    /**
     * @return bool
     */
    public function isInstructor() : bool
    {
        return (bool) $this->instructor;
    }

    /**
     * @return bool
     */
    public function isIndividualInstructor() : bool
    {
        return (bool) $this->individual_instructor;
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
        $clone->event_responsible = (int) $event_responsible;
        return $clone;
    }

    /**
     * @param bool $course_responsible
     * @return Member
     */
    public function withCourseResponsible(bool $course_responsible) : Member
    {
        $clone = clone $this;
        $clone->course_responsible = (int) $course_responsible;
        return $clone;
    }

    /**
     * @param bool $instructor
     * @return Member
     */
    public function withInstructor(bool $instructor) : Member
    {
        $clone = clone $this;
        $clone->instructor = (int) $instructor;
        return $clone;
    }

    /**
     * @param bool $individual_instructor
     * @return Member
     */
    public function withIndividualInstructor(bool $individual_instructor) : Member
    {
        $clone = clone $this;
        $clone->individual_instructor = (int) $individual_instructor;
        return $clone;
    }

    /**
     * Check if the member has either a specific role or a selected module
     */
    public function hasData() : bool
    {
        return (
            isset($this->module_id) || $this->hasAnyRole()
        );
    }

    /**
     * Check if the user has any role as resposible or instructor
     * i.e. is no simple member
     */
    public function hasAnyRole()
    {
        return (
            $this->event_responsible
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
        $clone->$role = (int) $flag;
        return $clone;
    }

    /**
     * Remove all specific roles from the member
     */
    public function withoutRoles() : Member
    {
        $clone = clone($this);
        $clone->event_responsible = 0;
        $clone->course_responsible = 0;
        $clone->instructor = 0;
        $clone->individual_instructor = 0;
        return $clone;
    }

    /**
     * Get the constant of an ILIAS role for the campo role in a context
     * Return null for simple members (without campo role)
     * 
     * @param string $context
     * @return int|null
     */
    public function getIliasRole(string $context): ?int
    {
        switch ($context) {
            case self::CONTEXT_SINGLE_COURSE:
                if ($this->hasAnyRole()) {
                    return IL_CRS_ADMIN;
                }
                break;

            case self::CONTEXT_PARENT_COURSE:
                if ($this->isEventResponsible()) {
                    return IL_CRS_ADMIN;
                }
                elseif ($this->hasAnyRole()) {
                    return IL_CRS_TUTOR;
                } 
                break;
                                 
            case self::CONTEXT_NESTED_GROUP:
                if ($this->isCourseResponsible()
                || $this->isInstructor()
                || $this->isIndividualInstructor()) {
                    return IL_GRP_ADMIN;
                }
        }
        return null;
    }
}