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
use FAU\Study\Data\Term;

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
     * Update the roles of responsibilities and instructors in an ilias course or group for a parallel group
     * This is done by comparing the actual responsibilities and instructors tables with the members table
     * At the end the members table should reflect the status in campo for existing users
     *
     * @param int       $ref_id
     * @param int       $course_id
     * @param int|null  $parent_ref_id
     * @param int|null  $event_id
     * @param Term|null $erm
     */
    public function updateIliasRolesOfCourse(int $ref_id, int $course_id, ?int $parent_ref_id = null, ?int $event_id = null, ?Term $term = null)
    {
        // IMPORTANT:
        // The roles in the parent course have to be updated first!
        // The updateRoles functions compare the remembered role settings in Member objects with the roles defined by campo
        // Ilias course or group roles will only be updated when differences between campo and a Member object are detected
        // updateRolesInDirectObject() saves an updated the Member object with current roles from campo at the end
        // updateRolesInParentObject() uses virtual Member objects with OR-Combined roles of the Member objects of all child groups
        //                             These virtual Member objects are not saved
        // To detect changes the virtual parent Member has to be built before the real Member is updated

        if (isset($parent_ref_id) && isset($event_id) && isset($term)) {
            $this->updateRolesInParentObject($parent_ref_id, $event_id, $term);
        }
        $this->updateRolesInDirectObject($ref_id, $course_id);
    }


    /**
     * Update the roles of responsibilities and instructors in an ilias course or group for a parallel group
     * @see updateIliasRolesOfCourse
     */
    protected function updateRolesInDirectObject(int $ref_id, int $course_id, ?int $user_id = null)
    {
        $obj_id = ilObject::_lookupObjId($ref_id);
        $participants = ilParticipants::getInstance($ref_id);

        // get the member data of the object
        $cur_members = $this->user->repo()->getMembersOfObject($obj_id, $user_id, false);

        // get the current roles defined by campo
        $event_resps = $this->sync->repo()->getIdsForCampoRoles(Member::ROLE_EVENT_RESPONSIBLE, 'user_id', $user_id, $course_id);
        $course_resps = $this->sync->repo()->getIdsForCampoRoles(Member::ROLE_COURSE_RESPONSIBLE, 'user_id', $user_id, $course_id);
        $instructors = $this->sync->repo()->getIdsForCampoRoles(Member::ROLE_INSTRUCTOR, 'user_id', $user_id, $course_id);
        $indiv_insts = $this->sync->repo()->getIdsForCampoRoles(Member::ROLE_INDIVIDUAL_INSTRUCTOR, 'user_id', $user_id, $course_id);

        $user_ids = array_unique(array_merge($event_resps, $course_resps, $instructors, $indiv_insts));
        foreach ($user_ids as $user_id) {
            $cur_member = $cur_members[$user_id] ?? null;
            $new_member = new Member(
                    $obj_id,
                    $user_id,
                    isset($cur_member) ? $cur_member->getModuleId() : null,
                    in_array($user_id, $event_resps),
                    in_array($user_id, $course_resps),
                    in_array($user_id, $instructors),
                    in_array($user_id, $indiv_insts)
            );

            // member has new or changed roles => update the participant
            if (!isset($cur_member) || $cur_member->hash() != $new_member->hash()) {
                $this->updateParticipantInDirectObject($participants, $new_member);
                $this->user->repo()->save($new_member);
            }
            // membership is treated
            unset($cur_members[$user_id]);
        }

        // treat the remaining member records that have no new role
        foreach ($cur_members as $cur_member) {
            $cur_member = $cur_member->withoutRoles();
            $this->updateParticipantInDirectObject($participants, $cur_member);
            if ($cur_member->hasData()) {
                $this->user->repo()->save($cur_member);
            }
            else {
                $this->user->repo()->delete($cur_member);
            }
        }
    }

    /**
     * Update the roles of responsibilities and instructors in an ilias course with nested parallel groups
     * @see updateIliasRolesOfCourse
     */
    protected function updateRolesInParentObject(int $ref_id, int $event_id, Term $term, ?int $user_id = null)
    {
        $obj_id = ilObject::_lookupObjId($ref_id);
        $participants = ilParticipants::getInstance($ref_id);

        // get the merged virtual member data of the child objects
        $cur_members = $this->getMergedMembers($this->user->repo()->getMembersOfEventInTerm($event_id, $term, $user_id, false));

        // get the current roles defined by campo
        $event_resps = $this->sync->repo()->getIdsForCampoRoles(Member::ROLE_EVENT_RESPONSIBLE, 'user_id', $user_id, null, $event_id, $term);
        $course_resps = $this->sync->repo()->getIdsForCampoRoles(Member::ROLE_COURSE_RESPONSIBLE, 'user_id', $user_id, null, $event_id, $term);
        $instructors = $this->sync->repo()->getIdsForCampoRoles(Member::ROLE_INSTRUCTOR, 'user_id', null, $user_id, $event_id, $term);
        $indiv_insts = $this->sync->repo()->getIdsForCampoRoles(Member::ROLE_INDIVIDUAL_INSTRUCTOR, 'user_id', $user_id, null, $event_id, $term);

        $user_ids = array_unique(array_merge($event_resps, $course_resps, $instructors, $indiv_insts));
        foreach ($user_ids as $user_id) {
            $cur_member = $cur_members[$user_id] ?? null;
            $new_member = new Member(
                $obj_id,
                $user_id,
                isset($cur_member) ? $cur_member->getModuleId() : null,
                in_array($user_id, $event_resps),
                in_array($user_id, $course_resps),
                in_array($user_id, $instructors),
                in_array($user_id, $indiv_insts)
            );

            // member has new or changed roles => update the participant
            // virtual member record is not saved here!
            // member records will be updated with the roles in the direct object later
            if (!isset($cur_member) || $cur_member->hash() != $new_member->hash()) {
                $this->updateParticipantInParentObject($participants, $new_member);
            }
            // membership is treated
            unset($cur_members[$user_id]);
        }

        // treat the remaining memberships without a role
        foreach ($cur_members as $cur_member) {
            $cur_member = $cur_member->withoutRoles();
            $this->updateParticipantInParentObject($participants, $cur_member);
        }
    }

    /**
     * Get virtual member objects for an event from the member objects of the nested courses
     * The return array has a virtual member object (without obj_id) for each user
     * The roles in this object are OR-combined roles of the user's member objects
     *
     * @param Member[] $members
     * @return Member[] (indexed by user id)
     */
    protected function getMergedMembers(array $members)
    {
        $merged = [];
        foreach($members as $member) {
            $member2 = $merged[$member->getUserId()] ?? Member::model();

            $merged[$member->getUserId()] = new Member(
                0,
                $member->getUserId(),
                null,
                $member->isEventResponsible() || $member2->isEventResponsible(),
                $member->isCourseResponsible() || $member2->isCourseResponsible(),
                $member->isInstructor() || $member2->isInstructor(),
                $member->isIndividualInstructor() || $member2->isIndividualInstructor()
            );
        }
        return $merged;
    }

    /**
     * Update the participant in the ILIAS object for a parallel group (group or course)
     *
     * @param ilCourseParticipants|ilGroupParticipants $participants      Participants of the ilias object
     * @param Member  $member            Member record of the user with new role settings
     */
    protected function updateParticipantInDirectObject(ilParticipants $participants, Member $member)
    {
        $admin_role = $participants instanceof ilCourseParticipants ? IL_CRS_ADMIN : IL_GRP_ADMIN;

        if ($member->hasAnyRole()) {
            // member has responsibility, so add it or set it as admin
            if ($participants->isAdmin($member->getUserId())) {
                return;
            } elseif ($participants->isAssigned($member->getUserId())) {
                $participants->updateRoleAssignments($member->getUserId(), [$participants->getRoleId($admin_role)]);
            } else {
                $participants->add($member->getUserId(), $admin_role);
            }
        }
        else {
            // member has no responsibility, so remove it as admin
            // don't remove users with other roles
            if ($participants->isAdmin($member->getUserId())) {
                $participants->delete($member->getUserId());
            }
        }
    }


    /**
     * Update the participant in the parent course of nested parallel groups
     * The parent course represents the campo event
     * The event responsibles should be admins, other roles should be tutors
     *
     * @param ilCourseParticipants $participants      participants of the course
     * @param Member  $member   virtual member object with OR-combined roles of nested groups
     */
    protected function updateParticipantInParentObject(ilParticipants $participants, Member $member)
    {
        if ($member->isEventResponsible()) {
            // member has responsibility for the whole event, so add or set it as admin
            if ($participants->isAdmin($member->getUserId())) {
                return;
            } elseif ($participants->isAssigned($member->getUserId())) {
                $participants->updateRoleAssignments($member->getUserId(), [$participants->getRoleId(IL_CRS_ADMIN)]);
            } else {
                $participants->add($member->getUserId(), IL_CRS_ADMIN);
            }
        }
        elseif ($member->hasAnyRole()) {
            // member has responsibility in at least one nested group, so add or set it as tutor
            if ($participants->isTutor($member->getUserId())) {
                return;
            } elseif ($participants->isAssigned($member->getUserId())) {
                $participants->updateRoleAssignments($member->getUserId(), [$participants->getRoleId(IL_CRS_TUTOR)]);
            } else {
                $participants->add($member->getUserId(), IL_CRS_TUTOR);
            }
        }
        else {
            // member has no responsibility, so remove it as admin or tutor
            // don't remove users with other roles
            if ($participants->isAdmin($member->getUserId()) || $participants->isTutor($member->getUserId())) {
                $participants->delete($member->getUserId());
            }
        }
    }

    /**
     * Apply roles of responsibilities and instructors in ilias courses or groups for a new ilias user
     * It is assumed the no roles have to be removed for a new user
     * So only the courses for the current roles from campo are treated
     * At the end the members table should reflect the status in campo for the new user
     * @see Member
     */
    public function applyNewUserCourseRoles($user_id)
    {
       foreach ($this->sync->getTermsToSync() as $term) {

            // get the course ids for roles defined by campo
            $event_resps = $this->sync->repo()->getIdsForCampoRoles(Member::ROLE_EVENT_RESPONSIBLE, 'course_id', $user_id, null, null, $term);
            $course_resps = $this->sync->repo()->getIdsForCampoRoles(Member::ROLE_COURSE_RESPONSIBLE, 'course_id', $user_id, null, null, $term);
            $instructors = $this->sync->repo()->getIdsForCampoRoles(Member::ROLE_INSTRUCTOR, 'course_id', $user_id, null, null, $term);
            $indiv_insts = $this->sync->repo()->getIdsForCampoRoles(Member::ROLE_INDIVIDUAL_INSTRUCTOR, 'course_id', $user_id, null, null, $term);

            // look at all courses where the user has a role
            $course_ids = array_unique(array_merge(
                $event_resps, $course_resps, $instructors, $indiv_insts
            ));

            foreach ($this->study->repo()->getCoursesByIds($course_ids) as $course_id => $course) {
                if (empty($ref_id = $this->ilias->objects()->getIliasRefIdForCourse($course))) {
                    continue;
                }
                elseif (!empty($parent_ref = $this->ilias->objects()->findParentIliasCourse($ref_id)))  {
                    $this->updateRolesInParentObject($parent_ref, $course->getEventId(), $term, $user_id);
                }
                $this->updateRolesInDirectObject($ref_id, $course_id, $user_id);
             }
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
                null, 1, 1, 1);
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
                        // takes too long - create the roles in a batch
                        //$role_id = $this->createOrgRole($orgunit, $ref_id, $this->settings->getAuthorRoleTemplateId(), true);
                    }
                    break;
                case OrgRole::TYPE_MANAGER:
                    $role_id = $this->findManagerRole($ref_id);
                    if (empty($role_id)) {
                        // takes too long - create the roles in a batch
                        // $role_id = $this->createOrgRole($orgunit, $ref_id, $this->settings->getManagerRoleTemplateId(), true);
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
    public function findAuthorRole(int $ref_id) : ?int
    {
       return $this->findRoleByPermissions($ref_id, ['create_cat', 'create_crs', 'create_grp'], ['delete']);
    }

    /**
     * Find a manager role
     * @return ?int $role_id
     */
    public function findManagerRole(int $ref_id) : ?int
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
    public function createOrgRole(string $orgunit, int $ref_id, int $template_id, bool $recommend) : ?int
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