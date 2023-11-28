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
     * Update the ilias roles of responsibilities and instructors for campo course (parallel group)
     * 
     * Compare the actual responsibilities and instructors from campo with the status those remembered from the last sync
     * The status is remembered in the table "fau_user_members"
     * Update the roles in the studon object if the effective role:
     *  - differs between the remembered status and the new status (changed in campo)
     *  - doesn't differ between the remembered status and the actual role (not manually changed in StudOn)
     * 
     * The update is done for the directly connected ilias course or group 
     * It is also done for a parent ilias course if needed
     * 
     * @param int       $ref_id
     * @param int       $course_id
     * @param int|null  $parent_ref_id
     * @param int|null  $event_id
     * @param Term|null $erm
     */
    public function updateIliasRolesOfCourse(int $ref_id, int $course_id, ?int $parent_ref_id = null, ?int $event_id = null, ?Term $term = null)
    {
        $this->updateRolesInIliasObject($ref_id, null, $course_id);
        
        if (isset($parent_ref_id) && isset($event_id) && isset($term)) {
            $this->updateRolesInIliasObject($parent_ref_id, null, null, $event_id, $term);
        }
    }

    /**
     * Update the roles of responsibilities and instructors in a single ilias course or group
     * Called for updating the roles of a campo course or an ilias user
     * 
     * @see updateIliasRolesOfCourse
     */
    protected function updateRolesInIliasObject(int $ref_id, ?int $user_id = null, ?int $course_id = null, ?int $event_id = null, ?Term $term = null)
    {
        $obj_id = ilObject::_lookupObjId($ref_id);
        $participants = ilParticipants::getInstance($ref_id);
        
        if (isset($course_id) &&  $participants instanceof ilGroupParticipants) {
            $context = Member::CONTEXT_NESTED_GROUP;
        }
        elseif (isset($course_id) && $participants instanceof ilCourseParticipants) {
            $context = Member::CONTEXT_SINGLE_COURSE;
        }
        elseif (isset($event_id) && isset($term) && $participants instanceof  ilCourseParticipants) {
            $context = Member::CONTEXT_PARENT_COURSE;
        }
        else {
            // wrong object type for the target function
            return;
        }

        // get the roles defined by campo
        switch ($context) {
            case Member::CONTEXT_PARENT_COURSE:
                $event_resps = $this->sync->repo()->getIdsForCampoRoles(Member::ROLE_EVENT_RESPONSIBLE, 'user_id', $user_id, null, $event_id, $term);
                $course_resps = $this->sync->repo()->getIdsForCampoRoles(Member::ROLE_COURSE_RESPONSIBLE, 'user_id', $user_id, null, $event_id, $term);
                $instructors = $this->sync->repo()->getIdsForCampoRoles(Member::ROLE_INSTRUCTOR, 'user_id', null, $user_id, $event_id, $term);
                $indiv_insts = $this->sync->repo()->getIdsForCampoRoles(Member::ROLE_INDIVIDUAL_INSTRUCTOR, 'user_id', $user_id, null, $event_id, $term);
                break;
                
            case Member::CONTEXT_SINGLE_COURSE:
            case Member::CONTEXT_NESTED_GROUP:
            default:
                $event_resps = $this->sync->repo()->getIdsForCampoRoles(Member::ROLE_EVENT_RESPONSIBLE, 'user_id', $user_id, $course_id);
                $course_resps = $this->sync->repo()->getIdsForCampoRoles(Member::ROLE_COURSE_RESPONSIBLE, 'user_id', $user_id, $course_id);
                $instructors = $this->sync->repo()->getIdsForCampoRoles(Member::ROLE_INSTRUCTOR, 'user_id', $user_id, $course_id);
                $indiv_insts = $this->sync->repo()->getIdsForCampoRoles(Member::ROLE_INDIVIDUAL_INSTRUCTOR, 'user_id', $user_id, $course_id);
                break;
        }

        // get the campo member status objects for the ilias object
        $cur_members = $this->user->repo()->getMembersOfObject($obj_id, $user_id, false);
        
        // get all user ids that have a role defined by campo for this target
        $user_ids = array_unique(array_merge($event_resps, $course_resps, $instructors, $indiv_insts));

        foreach ($user_ids as $user_id) {
            $cur_member = $cur_members[$user_id] ?? new Member(
                $obj_id,
                $user_id
            );
            $new_member = new Member(
                $obj_id,
                $user_id,
                $cur_member->getModuleId(),
                in_array($user_id, $event_resps),
                in_array($user_id, $course_resps),
                in_array($user_id, $instructors),
                in_array($user_id, $indiv_insts)
            );
            $this->updateParticipantInIliasObject($participants, $cur_member, $new_member, $context);
            $this->user->repo()->save($new_member);

            // membership is treated
            unset($cur_members[$user_id]);
        }

        // treat the remaining memberships without a role in campo after the sync
        foreach ($cur_members as $cur_member) {
            $new_member = $cur_member->withoutRoles();
            $this->updateParticipantInIliasObject($participants, $cur_member, $new_member, $context);
            if ($new_member->hasData()) {
                $this->user->repo()->save($new_member);
            }
            else {
                $this->user->repo()->delete($new_member);
            }
        }
    }
    
    
    /**
     * Update the participant in an ilias course or group
     *
     * @param ilCourseParticipants $participants      participants of the object
     * @param Member $old_member    old member status from campo before the synchronisation
     * @param Member $new_member    new member status from campo after the synchronisation
     * @param string $context       the context determines the effective ILIAS role of the member
     */
    protected function updateParticipantInIliasObject(ilParticipants $participants, Member $old_member, Member $new_member, string $context)
    {
        $user_id = $old_member->getUserId();
        
        if ($participants instanceof ilCourseParticipants) {
            $current_role = 
                $participants->isAdmin($user_id) ? IL_CRS_ADMIN 
                : ($participants->isTutor($user_id) ? IL_CRS_TUTOR 
                    : null);                                            // other roles are not derived from by campo
        }
        else {
            $current_role =
                $participants->isAdmin($user_id) ? IL_GRP_ADMIN 
                    : null;                                             // other roles are not derived from by campo

        }

        // don't change if role for campo isn't changed
        if ((int) $old_member->getIliasRole($context) == (int) $new_member->getIliasRole($context)) {
            return;
        }

        // don't change if ilias role is manually changed
        if ((int) $current_role != (int) $old_member->getIliasRole($context)) {
            return;
        }

        // update the role assignment in the ilias object  
        if (!empty($new_role = $new_member->getIliasRole($context))) {
            if ($participants->isAssigned($user_id)) {
                $participants->updateRoleAssignments($user_id, [$participants->getRoleId($new_role)]);
            } else {
                $participants->add($user_id, $new_role);
            }
        }
        elseif ($participants->isAdmin($user_id) || $participants->isTutor($user_id)) {
            // member has no role from campo, so remove it as admin or tutor
            // don't remove users with other roles (e.g. members)
            $participants->delete($user_id);
        }
    }
    
    /**
     * Apply the campo roles in ilias courses or groups for a new ilias user
     * It is assumed the no roles have to be removed for a new user
     * So only the courses for the current roles from campo are treated
     * At the end the fau_user_members table should reflect the status in campo for the new user
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
                if (!empty($ref_id = $this->ilias->objects()->getIliasRefIdForCourse($course))) {
                    $this->updateRolesInIliasObject($ref_id, $user_id, $course_id);
                    if (!empty($parent_ref = $this->ilias->objects()->findParentIliasCourse($ref_id)))  {
                        $this->updateRolesInIliasObject($parent_ref, $user_id, null, $course->getEventId(), $term);
                    }
                }
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
                $idmSet[$role->getType() . $unit->getIliasRefId()] = [
                    $role->getType(),
                    $unit->getIliasRefId(),
                    $unit->getNoManager()
                ];
            }
        }

        // get the roles remembered for the user
        $userSet = [];
        foreach ($this->dic->fau()->user()->repo()->getOrgRolesOfUser($user_id) as $role) {
            $userSet[$role->getType() . $role->getRefId()] = [
                $role->getType(),
                $role->getRefId()
            ];
        }

        // add the roles that are found in idm but not remembered for the user
        foreach (array_diff_key($idmSet, $userSet) as $set) {
            list($type, $ref_id, $no_manager) = $set;

            $role_id = null;
            switch ($type) {
                case OrgRole::TYPE_AUTHOR:
                    $role_id = $this->findAuthorRole($ref_id);
                    break;

                case OrgRole::TYPE_MANAGER:
                    if (empty($no_manager)) {
                        $role_id = $this->findManagerRole($ref_id);
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
     * @param string[] $required_operations     names of the required permissions, all must match
     * @param string[] $forbidden_operations    names of the forbidden permissions, none shound match
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