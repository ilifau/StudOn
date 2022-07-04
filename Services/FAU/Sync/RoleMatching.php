<?php

namespace FAU\Sync;

use ILIAS\DI\Container;
use ilLanguage;
use FAU\User\Data\Member;
use ilParticipants;
use ilCourseParticipants;
use ilGroupParticipants;
use ilObject;

/**
 * Functions for matching the persons related to campo events and courses with ilias course and group roles
 * Used in the ilias synchronisation
 * Used when a new user is created
 *
 * @see SyncWithIlias
 */
class RoleMatching
{
    protected Container $dic;
    protected ilLanguage $lng;
    protected \FAU\Org\Service $org;
    protected \FAU\Study\Service $study;
    protected \FAU\Sync\Service $sync;
    protected \FAU\User\Service $user;
    protected \FAU\Settings $settings;


    /**
     * Constructor
     */
    public function __construct(Container $dic)
    {
        $this->dic = $dic;
        $this->lng = $dic->language();
        $this->org = $dic->fau()->org();
        $this->study = $dic->fau()->study();
        $this->sync = $dic->fau()->sync();
        $this->user = $dic->fau()->user();
        $this->settings = $dic->fau()->settings();
    }

    /**
     * Update the roles of responsibilities and instructors in an ilias course or group
     * This is done by comparing the actual responsibilities and instructors tables with the members table
     * At the end the members table should reflect the status in campo for existing users
     *
     * @see Member
     */
    public function updateParticipants(int $course_id, int $ref_id_for_event, int $ref_id_for_course)
    {
        $obj_id_for_event = ilObject::_lookupObjId($ref_id_for_event);
        $obj_id_for_course = ilObject::_lookupObjId($ref_id_for_course);

        if ($obj_id_for_course == $obj_id_for_event) {
            // one ilias course for campo event and course
            $crs_participants = new ilCourseParticipants($obj_id_for_event);
            $grp_participants = null;
        }
        else {
            // ilias course for campo event with ilias group for campo course
            $crs_participants = new ilCourseParticipants($obj_id_for_event);
            $grp_participants = new ilGroupParticipants($obj_id_for_course);
        }

        // don't use cache because members table is updated during sync
        $members = $this->user->repo()->getMembersOfObjects($obj_id_for_course, false);
        $touched = [];

        $this->updateRole(
            Member::ROLE_EVENT_RESPONSIBLE,
            $this->sync->repo()->getUserIdsOfEventResponsibles($course_id),
            $obj_id_for_course,
            $crs_participants,
            $grp_participants,
            $members,
            $touched
        );

        $this->updateRole(
            Member::ROLE_COURSE_RESPONSIBLE,
            $this->sync->repo()->getUserIdsOfCourseResponsibles($course_id),
            $obj_id_for_course,
            $crs_participants,
            $grp_participants,
            $members,
            $touched
        );

        $this->updateRole(
            Member::ROLE_INSTRUCTOR,
            $this->sync->repo()->getUserIdsOfInstructors($course_id),
            $obj_id_for_course,
            $crs_participants,
            $grp_participants,
            $members,
            $touched
        );

        $this->updateRole(
            Member::ROLE_INDIVIDUAL_INSTRUCTOR,
            $this->sync->repo()->getUserIdsOfIndividualInstructors($course_id),
            $obj_id_for_course,
            $crs_participants,
            $grp_participants,
            $members,
            $touched
        );

        // save or delete the modified member records
        foreach ($touched as $member) {
            if ($member->hasData()) {
                $this->user->repo()->save($member);
            }
            else {
                $this->user->repo()->delete($member);
            }
        }

        // remove the default owner from the participants
        $crs_participants->delete($this->settings->getDefaultOwnerId());
        if (isset($grp_participants)) {
            $grp_participants->delete($this->settings->getDefaultOwnerId());
        }
    }



    /**
     * Update the roles of responsibilities and instructors in ilias courses or groups for a user
     * This is done by comparing the actual responsibilities and instructors tables with the members table for this user
     * At the end the members table should reflect the status in campo for the user
     *
     * @see Member
     */
    public function updateUserParticipation($user_id)
    {
        $all_memberships = $this->user->repo()->getMembersOfUser($user_id);

        foreach ($this->sync->getTermsToSync() as $term) {

            $event_resp_course_ids = $this->sync->repo()->getCourseIdsOfEventResponsible($user_id, $term);
            $course_resp_course_ids = $this->sync->repo()->getCourseIdsOfCourseResponsible($user_id, $term);
            $instructor_course_ids = $this->sync->repo()->getCourseIdsOfInstructor($user_id, $term);
            $indiv_inst_course_ids = $this->sync->repo()->getCourseIdsOfIndividualInstructor($user_id, $term);

            // look at all courses where the user has a sole
            $course_ids = array_unique(array_merge(
                $event_resp_course_ids, $course_resp_course_ids, $instructor_course_ids, $indiv_inst_course_ids
            ));
            foreach ($this->study->repo()->getCoursesByIds($course_ids) as $course_id => $course) {

                $crs_participants = null;
                $grp_participants = null;

                if (empty($ref_id = $this->sync->trees()->getIliasRefIdForCourse($course))) {
                    continue;
                }
                else {
                    switch (ilObject::_lookupType($ref_id, true)) {
                        case 'crs':
                            $crs_participants = new ilCourseParticipants($course->getIliasObjId());
                            break;

                        case 'grp':
                            $parent_ref = $this->dic->repositoryTree()->getParentId($ref_id);
                            $crs_participants = new ilCourseParticipants(ilObject::_lookupObjId($parent_ref));
                            $grp_participants = new ilGroupParticipants($course->getIliasObjId());
                            break;
                    }
                }

                // Use the same function for roles update as updateParticipants() of a course
                // The arrays for both user_ids and members have at maximum the updated user as key
                // so other members and course/group users are not affected
                $members = isset($all_memberships[$course->getIliasObjId()]) ?
                    [$user_id => $all_memberships[$course->getIliasObjId()]] : [];
                $touched = [];

                $this->updateRole(
                    Member::ROLE_EVENT_RESPONSIBLE,
                    in_array($course_id, $event_resp_course_ids) ? [$user_id] : [],
                    $course->getIliasObjId(),
                    $crs_participants,
                    $grp_participants,
                    $members,
                    $touched
                );

                $this->updateRole(
                    Member::ROLE_COURSE_RESPONSIBLE,
                    in_array($course_id, $course_resp_course_ids) ? [$user_id] : [],
                    $course->getIliasObjId(),
                    $crs_participants,
                    $grp_participants,
                    $members,
                    $touched
                );

                $this->updateRole(
                    Member::ROLE_INSTRUCTOR,
                    in_array($course_id, $instructor_course_ids) ? [$user_id] : [],
                    $course->getIliasObjId(),
                    $crs_participants,
                    $grp_participants,
                    $members,
                    $touched
                );

                $this->updateRole(
                    Member::ROLE_INDIVIDUAL_INSTRUCTOR,
                    in_array($course_id, $indiv_inst_course_ids) ? [$user_id] : [],
                    $course->getIliasObjId(),
                    $crs_participants,
                    $grp_participants,
                    $members,
                    $touched
                );

                // save or delete the modified member record (should be only one)
                foreach ($touched as $member) {
                    if ($member->hasData()) {
                        $this->user->repo()->save($member);
                    }
                    else {
                        $this->user->repo()->delete($member);
                    }
                }
            }
        }
    }


    /**
     * Update the users of a certain ilias course or group role
     * Update the role setting in the members table of these users
     *
     * @param string $mem_role                          role that should be checked in a member record
     * @param int[] $user_ids                           list of users that should get the role
     * @param int $mem_obj_id                           object id for new member records
     * @param ?ilCourseParticipants $crs_participants   participants of ilias course
     * @param ?ilGroupParticipants $grp_participants    participants of ilias group
     * @param Member[] &$members                        array of member data (user_id => Member) to be checked
     * @param Member[] &$touched                        array of member data (user_id => Member) to be saved later
     */
    protected function updateRole (
        string $mem_role,
        array $user_ids,
        int $mem_obj_id,
        ?ilCourseParticipants $crs_participants,
        ?ilGroupParticipants $grp_participants,
        array &$members,
        array &$touched
    )
    {
        // determine which course/group roles should be set
        switch ($mem_role)
        {
            case Member::ROLE_EVENT_RESPONSIBLE:
                $crs_role = IL_CRS_ADMIN;
                $grp_role = null;
                break;

            case Member::ROLE_COURSE_RESPONSIBLE:
            case Member::ROLE_INSTRUCTOR:
            case Member::ROLE_INDIVIDUAL_INSTRUCTOR:
                $crs_role = isset($grp_participants) ? IL_CRS_TUTOR : IL_CRS_ADMIN;
                $grp_role = isset($grp_participants) ? IL_GRP_ADMIN : null;
                break;

            default:
                $crs_role = null;
                $grp_role = null;
        }

        $mem_ids = [];
        foreach ($members as $user_id => $member) {
            if ($member->hasRole($mem_role)) {
                $mem_ids[] = $user_id;
            }
        }

//        echo "\nmem_role: " . $mem_role;
//        echo "\ncrs_role: " . $crs_role;
//        echo "\ngrp_role: " . $grp_role;
//        echo "\nuser_ids: " . implode(',', $user_ids);
//        echo "\nmem_obj_id: " . $mem_obj_id;
//        echo "\nmem_ids: " . implode(',', $user_ids);
//
//        echo "\nMembers before: ";
//        var_dump($members);

        // added users
        foreach (array_diff($user_ids, $mem_ids) as $user_id) {

            $member = $members[$user_id] ?? new Member($mem_obj_id, $user_id);
            $member = $member->withRole($mem_role, true);
            $members[$user_id] = $member;
            $touched[$user_id] = $member;

            if (isset($crs_role) && isset($crs_participants)) {
                $this->addRole($crs_participants, $user_id, $crs_role);
            }
            if (isset($grp_role) && isset($grp_participants) ) {
                $this->addRole($grp_participants, $user_id, $grp_role);
            }
        }

        // removed users
        foreach (array_diff($mem_ids, $user_ids) as $user_id) {

            $member = $members[$user_id] ?? new Member($mem_obj_id, $user_id);
            $member = $member->withRole($mem_role, false);
            $members[$user_id] = $member;
            $touched[$user_id] = $member;

            if (isset($crs_role) && isset($crs_participants)) {
                $this->removeRole($crs_participants, $user_id, $crs_role);
            }
            if (isset($grp_role) && isset($grp_participants) ) {
                $this->removeRole($grp_participants, $user_id, $grp_role);
            }
        }

//        echo "\nMembers after: ";
//        var_dump($members);
    }

    /**
     * Add a user with a certain role to the ilias object
     */
    protected function addRole(ilParticipants $participants, int $user_id, int $role_type)
    {
        switch($role_type)
        {
            case IL_CRS_ADMIN;
            case IL_GRP_ADMIN:
                if ($participants->isAdmin($user_id)) {
                    return;
                }
                elseif ($participants->isAssigned($user_id)) {
                    $participants->updateRoleAssignments($user_id, [$participants->getRoleId($role_type)]);

                }
                else {
                    $participants->add($user_id, $role_type);
                }
                break;

            case IL_CRS_TUTOR:
                if ($participants->isTutor($user_id)) {
                    return;
                }
                elseif ($participants->isAssigned($user_id)) {
                    // Don't decrease a course role from admin to tutor
                    if (!$participants->isAdmin($user_id)) {
                        $participants->updateRoleAssignments($user_id, [$participants->getRoleId($role_type)]);
                    }
                }
                else {
                    $participants->add($user_id, $role_type);
                }
                break;
        }
    }

    /**
     * Remove a user with a certain role from the ilias object
     */
    protected function removeRole(ilParticipants $participants, int $user_id, int $role_type)
    {
        if (!$participants->isAssigned($user_id)) {
            return; //already no participant
        }

        switch ($role_type)
        {
            case IL_CRS_ADMIN:
            case IL_GRP_ADMIN:
                if ($participants->isAdmin($user_id)) {
                    $participants->delete($user_id);
                }
                break;

            case IL_CRS_TUTOR:
                if ($participants->isTutor($user_id)) {
                    $participants->delete($user_id);
                }
                break;
        }
    }

}