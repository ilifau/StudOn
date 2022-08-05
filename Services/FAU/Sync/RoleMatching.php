<?php

namespace FAU\Sync;

use ILIAS\DI\Container;
use ilLanguage;
use FAU\User\Data\Member;
use ilParticipants;
use ilCourseParticipants;
use ilGroupParticipants;
use ilObject;
use FAU\User\Data\Person;
use ilObjRole;
use ilRecommendedContentManager;
use ilObjRoleTemplate;
use FAU\User\Data\OrgRole;
use FAU\User\Data\UserOrgRole;

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
    protected \FAU\Ilias\Service $ilias;
    protected \FAU\Org\Service $org;
    protected \FAU\Study\Service $study;
    protected \FAU\Sync\Service $sync;
    protected \FAU\Tools\Service $tools;
    protected \FAU\User\Service $user;
    protected \FAU\Tools\Settings $settings;

    /**
     * Constructor
     */
    public function __construct(Container $dic)
    {
        $this->dic = $dic;
        $this->lng = $dic->language();
        $this->ilias = $dic->fau()->ilias();
        $this->org = $dic->fau()->org();
        $this->study = $dic->fau()->study();
        $this->sync = $dic->fau()->sync();
        $this->tools = $dic->fau()->tools();
        $this->user = $dic->fau()->user();
        $this->settings = $dic->fau()->tools()->settings();
    }

    /**
     * Update the roles of responsibilities and instructors in an ilias course or group
     * This is done by comparing the actual responsibilities and instructors tables with the members table
     * At the end the members table should reflect the status in campo for existing users
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
        } else {
            // ilias course for campo event with ilias group for campo course
            $crs_participants = new ilCourseParticipants($obj_id_for_event);
            $grp_participants = new ilGroupParticipants($obj_id_for_course);
        }

        // don't use cache because members table is updated during sync
        $members = $this->user->repo()->getMembersOfObject($obj_id_for_course, false);
        $touched = [];

        $this->updateParticipatRole(
            Member::ROLE_EVENT_RESPONSIBLE,
            $this->sync->repo()->getUserIdsOfEventResponsibles($course_id),
            $obj_id_for_course,
            $crs_participants,
            $grp_participants,
            $members,
            $touched
        );

        $this->updateParticipatRole(
            Member::ROLE_COURSE_RESPONSIBLE,
            $this->sync->repo()->getUserIdsOfCourseResponsibles($course_id),
            $obj_id_for_course,
            $crs_participants,
            $grp_participants,
            $members,
            $touched
        );

        $this->updateParticipatRole(
            Member::ROLE_INSTRUCTOR,
            $this->sync->repo()->getUserIdsOfInstructors($course_id),
            $obj_id_for_course,
            $crs_participants,
            $grp_participants,
            $members,
            $touched
        );

        $this->updateParticipatRole(
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
            } else {
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

                if (empty($ref_id = $this->ilias->objects()->getIliasRefIdForCourse($course))) {
                    continue;
                } else {
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

                $this->updateParticipatRole(
                    Member::ROLE_EVENT_RESPONSIBLE,
                    in_array($course_id, $event_resp_course_ids) ? [$user_id] : [],
                    $course->getIliasObjId(),
                    $crs_participants,
                    $grp_participants,
                    $members,
                    $touched
                );

                $this->updateParticipatRole(
                    Member::ROLE_COURSE_RESPONSIBLE,
                    in_array($course_id, $course_resp_course_ids) ? [$user_id] : [],
                    $course->getIliasObjId(),
                    $crs_participants,
                    $grp_participants,
                    $members,
                    $touched
                );

                $this->updateParticipatRole(
                    Member::ROLE_INSTRUCTOR,
                    in_array($course_id, $instructor_course_ids) ? [$user_id] : [],
                    $course->getIliasObjId(),
                    $crs_participants,
                    $grp_participants,
                    $members,
                    $touched
                );

                $this->updateParticipatRole(
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
                    } else {
                        $this->user->repo()->delete($member);
                    }
                }
            }
        }
    }

    /**
     * Update the users of a certain ilias course or group role
     * Update the role setting in the members table of these users
     * @param string                $mem_role         role that should be checked in a member record
     * @param int[]                 $user_ids         list of users that should get the role
     * @param int                   $mem_obj_id       object id for new member records
     * @param ?ilCourseParticipants $crs_participants participants of ilias course
     * @param ?ilGroupParticipants  $grp_participants participants of ilias group
     * @param Member[] &            $members          array of member data (user_id => Member) to be checked
     * @param Member[] &            $touched          array of member data (user_id => Member) to be saved later
     */
    protected function updateParticipatRole(
        string $mem_role,
        array $user_ids,
        int $mem_obj_id,
        ?ilCourseParticipants $crs_participants,
        ?ilGroupParticipants $grp_participants,
        array &$members,
        array &$touched
    ) {
        // determine which course/group roles should be set
        switch ($mem_role) {
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
                $this->addToParticipantRole($crs_participants, $user_id, $crs_role);
            }
            if (isset($grp_role) && isset($grp_participants)) {
                $this->addToParticipantRole($grp_participants, $user_id, $grp_role);
            }
        }

        // removed users
        foreach (array_diff($mem_ids, $user_ids) as $user_id) {

            $member = $members[$user_id] ?? new Member($mem_obj_id, $user_id);
            $member = $member->withRole($mem_role, false);
            $members[$user_id] = $member;
            $touched[$user_id] = $member;

            if (isset($crs_role) && isset($crs_participants)) {
                $this->removeFromParticipantRole($crs_participants, $user_id, $crs_role);
            }
            if (isset($grp_role) && isset($grp_participants)) {
                $this->removeFromParticipantRole($grp_participants, $user_id, $grp_role);
            }
        }

//        echo "\nMembers after: ";
//        var_dump($members);
    }

    /**
     * Add a user with a certain role to the ilias object
     */
    protected function addToParticipantRole(ilParticipants $participants, int $user_id, int $role_type)
    {
        switch ($role_type) {
            case IL_CRS_ADMIN;
            case IL_GRP_ADMIN:
                if ($participants->isAdmin($user_id)) {
                    return;
                } elseif ($participants->isAssigned($user_id)) {
                    $participants->updateRoleAssignments($user_id, [$participants->getRoleId($role_type)]);

                } else {
                    $participants->add($user_id, $role_type);
                }
                break;

            case IL_CRS_TUTOR:
                if ($participants->isTutor($user_id)) {
                    return;
                } elseif ($participants->isAssigned($user_id)) {
                    // Don't decrease a course role from admin to tutor
                    if (!$participants->isAdmin($user_id)) {
                        $participants->updateRoleAssignments($user_id, [$participants->getRoleId($role_type)]);
                    }
                } else {
                    $participants->add($user_id, $role_type);
                }
                break;
        }
    }

    /**
     * Remove a user with a certain role from the ilias object
     */
    protected function removeFromParticipantRole(ilParticipants $participants, int $user_id, int $role_type)
    {
        if (!$participants->isAssigned($user_id)) {
            return; //already no participant
        }

        switch ($role_type) {
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

    /**
     * Check if the participant list in the object differs from the remembered list of changes by campo
     */
    public function hasLocalMemberChanges($ref_id) : bool
    {
        $obj_id = ilObject::_lookupObjId($ref_id);
        if (ilObject::_lookupType($obj_id) == 'crs') {
            // in courses the event responsibles are admins, the others are tutors
            $part_ids = (new ilCourseParticipants($obj_id))->getParticipants();
            $mem_ids = $this->user->repo()->getUserIdsOfObjectMembers($obj_id, false,
                1, 1, 1, 1);
        } elseif (ilObject::_lookupType($obj_id) == 'grp') {
            // in groups the event responsibles are no participants, the others are admins
            $part_ids = (new ilGroupParticipants($obj_id))->getParticipants();
            $mem_ids = $this->user->repo()->getUserIdsOfObjectMembers($obj_id, false,
                null, 1, 1, 1,);
        } else {
            return true;
        }

        sort($part_ids);
        sort($mem_ids);

//        echo "Participants:\n";
//        foreach ($part_ids as $id) {
//            echo $id . ": " .  \ilObjUser::_lookupLogin($id) . ' '. \ilObjUser::_lookupFullname($id) . "\n";
//        }
//        echo "Members:\n";
//        foreach ($mem_ids as $id) {
//            echo $id . ": " .  \ilObjUser::_lookupLogin($id) . ' '. \ilObjUser::_lookupFullname($id) . "\n";
//        }

        if (!empty(array_diff($part_ids, $mem_ids))) {
            return true;
        }
        if (!empty(array_diff($mem_ids, $part_ids))) {
            return true;
        }
        return false;
    }

    /**
     * Update the organisational roles (author, manager) of a person
     * This is done by comparing a remembered list of role assignments with the actual list provided by idm
     */
    public function updateUserOrgRoles(Person $person)
    {
        $user_id = $person->getUserId();

        // get the roles found in idm
        $idmSet = [];
        foreach ($person->getOrgRoles() as $role) {
            $unit = $this->dic->fau()->org()->repo()->getOrgunitByNumber($role->getOrgunit());
            if (isset($unit)
                && ilObject::_exists($unit->getIliasRefId(), true, 'cat')
                && !ilObject::_isInTrash($unit->getIliasRefId())
            ) {
                $idmSet[$role->getType() . $unit->getIliasRefId()] = [$role->getType(), $unit->getIliasRefId(), $unit->getFauorgNr()];
            }
        }

        // get the roles remembered for the user
        $userSet = [];
        foreach ($this->dic->fau()->user()->repo()->getOrgRolesOfUser($user_id) as $role) {
            $userSet[$role->getType() . $role->getRefId()] = [$role->getType(), $role->getRefId()];
        }

        // add the roles that are found in idm but not remembered for the user
        foreach (array_diff_key($idmSet, $userSet) as $set) {
            list($type, $ref_id, $orgunit) = $set;
            $role_id = null;
            switch ($type) {
                case OrgRole::TYPE_AUTHOR:
                    $role_id = $this->findAuthorRole($ref_id);
                    if (empty($role_id)) {
                        $this->createOrgRole($orgunit, $ref_id, $this->settings->getAuthorRoleTemplateId(), true);
                    }
                    break;
                case OrgRole::TYPE_MANAGER:
                    $role_id = $this->findManagerRole($ref_id);
                    if (empty($role_id)) {
                        $this->createOrgRole($orgunit, $ref_id, $this->settings->getManagerRoleTemplateId(), true);
                    }
                    break;

            }
            if (!empty($role_id)) {
                $this->dic->rbac()->admin()->assignUser($role_id, $person->getUserId());
                // remember the role assignment
                $this->dic->fau()->user()->repo()->save(new UserOrgRole($user_id, $ref_id, $type));
            }
        }

        // remove the roles that are remembered for the user but don't exist in idm anymore
        foreach (array_diff_key($userSet, $idmSet) as $set) {
            list($type, $ref_id) = $set;
            $role_id = null;
            switch ($type) {
                case OrgRole::TYPE_AUTHOR:
                    $role_id = $this->findAuthorRole($ref_id);
                    break;
                case OrgRole::TYPE_MANAGER:
                    $role_id = $this->findManagerRole($ref_id);
                    break;

            }
            if (!empty($role_id)) {
                $this->dic->rbac()->admin()->deassignUser($role_id, $person->getUserId());
                // delete a remembered role assignment
                $this->dic->fau()->user()->repo()->delete(new UserOrgRole($user_id, $ref_id, $type));
            }
        }
    }


    /**
     * Find an author role
     * @return ?int $role_id
     */
    protected function findAuthorRole(int $ref_id) : ?int
    {
       return $this->findRoleByPermissions($ref_id, ['create_cat', 'create_crs', 'create_grp'], ['delete']);
    }

    /**
     * Find a manager role
     * @return ?int $role_id
     */
    protected function findManagerRole(int $ref_id) : ?int
    {
        return $this->findRoleByPermissions($ref_id, ['create_cat', 'delete'], ['edit_permission']);
    }

    /**
     * Find a role in a container with specific permissions
     * This checks the actual set permissions on the container object, not templates
     *
     * @param $ref_id
     * @param string[] $required_operations     names of the required permissions
     * @param string[] $forbidden_operations    names of the forbidden permissions
     * @return int|null     found role id
     */
    protected function findRoleByPermissions($ref_id, array $required_operations, array $forbidden_operations) : ?int
    {
        $required_ids = $this->dic->fau()->sync()->repo()->getRbacOperationIds($required_operations);
        $forbidden_ids = $this->dic->fau()->sync()->repo()->getRbacOperationIds($forbidden_operations);

        foreach ($this->dic->rbac()->review()->getRolesOfObject($ref_id, true) as $role_id) {
            // get the actual permissions on the category (not the template)
            $given_ids = $this->dic->rbac()->review()->getActiveOperationsOfRole($ref_id, $role_id);

            // check if required permissions are missing
            if (!empty(array_diff($required_ids, $given_ids))) {
                continue;
            }

            // check if forbidden permission is set
            if (!empty(array_intersect($forbidden_ids, $given_ids))) {
                continue;
            }

            // role matches
            return $role_id;
        }
        return null;
    }

    /**
     * Create a new role in a container and return their id
     * @see \ilPermissionGUI::addRole()
     */
    protected function createOrgRole(string $orgunit, int $ref_id, int $template_id, bool $recommend) : ?int
    {
        try {
            $template = new ilObjRoleTemplate($template_id);
        }
        catch (\Exception $e) {
            return null;
        }

        $role = new ilObjRole();
        $role->setTitle($template->getTitle() . '-' . $orgunit);
        $role->setDescription($template->getDescription());
        $role->create();

        $this->dic->rbac()->admin()->assignRoleToFolder($role->getId(), $ref_id);
        $this->dic->rbac()->admin()->copyRoleTemplatePermissions(
            $template_id, ROLE_FOLDER_ID, $ref_id, $role->getId(), true);

        $protected = $this->dic->rbac()->review()->isProtected($ref_id, $role->getId());
        $role->changeExistingObjects(
            $ref_id,
            $protected ? ilObjRole::MODE_PROTECTED_KEEP_LOCAL_POLICIES : ilObjRole::MODE_UNPROTECTED_KEEP_LOCAL_POLICIES,
            ['all']
        );

        if ($recommend) {
            (new ilRecommendedContentManager())->addRoleRecommendation($role->getId(), $ref_id);
        }

        return $role->getId();
    }
}