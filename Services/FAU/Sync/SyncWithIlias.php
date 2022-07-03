<?php

namespace FAU\Sync;

use ILIAS\DI\Container;
use FAU\Study\Data\Term;
use FAU\Org\Data\Orgunit;
use FAU\Study\Data\Course;
use FAU\Study\Data\Event;
use ilObject;
use ilObjCategory;
use IlObjCourse;
use FAU\Study\Data\ImportId;
use ilObjGroup;
use ilUtil;
use ilParticipants;
use FAU\User\Data\Member;
use ilCourseParticipants;
use ilGroupParticipants;

/**
 * Synchronize the campo courses with the related ILIAS objects
 *
 * The relation of campo courses to ilias objects is given by the property ilias_ref_id
 * Campo courses that need an update of the related object are marked with ilias_dirty_since
 * The dirty flag is deleted when the ilias objects are updated
 */
class SyncWithIlias extends SyncBase
{
    protected Container $dic;
    protected Service $service;

    /**
     * Synchronize the campo courses for selected terms
     */
    public function synchronize() : void
    {

        foreach ($this->getTermsToSync() as $term) {
            $this->info('SYNC term ' . $term->toString() . '...');
            $this->increaseItemsAdded($this->createCourses($term));
            $this->increaseItemsUpdated($this->updateCourses($term));
        }
    }

    /**
     * Sync the memberships of a new user
     * @param $user_id
     */
    public function syncNewUser($user_id) : void
    {
        // todo
    }

    /**
     * Get the terms for which the courses should be created or updated
     * End synchronisation with the end of the semester
     * Start synchronisation for next semester at 1st of June and 1st of December
     * @return Term[]
     */
    protected function getTermsToSync() : array
    {
        $year = (int) date('Y');
        $month = (int) date('m');

        if ($year == 2022 && $month < 12) {
               return [
                   new Term($year, 2)           // start with winter term 2022
               ];
        }
        elseif ($month < 4) {
            return [
                new Term($year - 1, 2),     // current winter term
                new Term($year, 1),              // next summer term
            ];
        }
        elseif ($month < 6) {
            return [
                new Term($year, 1),              // current summer term
            ];
        }
        elseif ($month < 10) {
            return [
                new Term($year, 1),              // current summer term
                new Term($year, 2),              // next winter term
            ];
        }
        elseif ($month < 12) {
            return [
                new Term($year, 2),             // current winter term
            ];
        }
        else {
            return [
                new Term($year, 2),              // current winter term
                new Term($year + 1, 1)      // next summer term
            ];
        }
    }


    /**
     * Create the ilias objects for courses (parallel groups) of a term
     * @return int number of created courses
     */
    protected function createCourses(Term $term) : int
    {
        $created = 0;
        foreach ($this->study->repo()->getCoursesByTermToCreate($term) as $course) {
            $this->info('CREATE' . $course->getTitle() . '...');

            $event = $this->study->repo()->getEvent($course->getEventId());
            $parent_ref = null;
            $other_refs = [];

            // check what to create
            if ($this->study->repo()->countCoursesOfEventInTerm($event->getEventId(), $term) == 1) {
                // single parallel groups are created as courses
                $action = 'create_single_course';
            }
            else {
                // multiple parallel groups are created as groups in a course by default
                $action = "create_course_and_group";

                // check if other parallel groups already have ilias objects
                // don't use cache because we are in an update loop
                foreach ($this->study->repo()->getCoursesOfEventInTerm($event->getEventId(), $term, false) as $other) {
                    foreach (ilObject::_getAllReferences($other->getIliasObjId()) as $ref_id) {
                        if (!ilObject::_isInTrash($ref_id)) {
                            $other_refs[] = $ref_id;
                            switch (ilObject::_lookupType($ref_id, true)) {
                                case 'crs':
                                    // other parallel groups are already ilias courses, create the same
                                    $action = 'create_single_course';
                                    break;
                                case 'grp':
                                    // other parallel groups are ilias groups, create the new in the same course
                                    $action = 'create_group_in_course';
                                    $parent_ref = $this->dic->repositoryTree()->getParentId($ref_id);
                                    break;
                            }
                        }
                    }
                }
            }

            // get or create the place for a new course if no parent_ref is set above
            // don't create the object for this course if no parent_ref can be found
            if (empty($parent_ref = $parent_ref ?? $this->findOrCreateCourseCategory($course, $term))) {
                continue;
            }

            // create the object(s)
            switch ($action) {
                case 'create_single_course':
                    $ref_id = $this->createIliasCourse($parent_ref, $term, $event, $course);
                    $this->updateIliasCourse($ref_id, $term, $event, $course);
                    $this->updateIliasParticipants($course->getCourseId(), $ref_id, $ref_id);
                    break;

                case 'create_course_and_group':
                    $parent_ref = $this->createIliasCourse($parent_ref, $term, $event, null);
                    $ref_id = $this->createIliasGroup($parent_ref,  $term, $event, $course);

                    // don't use course data for the event - courses are the groups inside
                    $this->updateIliasCourse($parent_ref, $term, $event, null) ;
                    $this->updateIliasGroup($ref_id, $term, $event, $course);
                    $this->updateIliasParticipants($course->getCourseId(), $parent_ref, $ref_id);
                    break;

                case 'create_group_in_course':
                    // course for the event already exists
                    $ref_id = $this->createIliasGroup($parent_ref, $term, $event, $course);
                    $this->updateIliasGroup($ref_id, $term, $event, $course);
                    $this->updateIliasParticipants($course->getCourseId(), $parent_ref, $ref_id);
                    break;
            }

            // create or update the membership limitation
            if (!empty($other_refs)) {
                // todo: update membership limitation
            }

            $created++;
        }
        return $created;
    }


    /**
     * Update the courses of a term
     * This should also treat the event related courses
     * @return int number of updated courses
     */
    protected function updateCourses(Term $term) : int
    {
        $updated = 0;
        foreach ($this->study->repo()->getCoursesByTermToUpdate($term) as $course) {
            $this->info('UPDATE' . $course->getTitle() . '...');

            $event = $this->study->repo()->getEvent($course->getEventId());

            // get the reference to the ilias course or group
            $ref_id = null;
            if (!empty($course->getIliasObjId())) {
                foreach (ilObject::_getAllReferences($course->getIliasObjId()) as $check_id) {
                    if (!ilObject::_isInTrash($check_id)) {
                        $ref_id = $check_id;
                        break;
                    }
                }
            }
            if (empty($ref_id)) {
                $this->study->repo()->save($course->withIliasProblem("ILIAS object does not exist or is deleted!"));
                continue;
            }

            switch (ilObject::_lookupType($course->getIliasObjId())) {
                case 'crs':
                    // ilias course is used for campo event and course
                    $this->updateIliasCourse($ref_id, $term, $event, $course);
                    $this->updateIliasParticipants($course->getCourseId(), $ref_id, $ref_id);
                    break;

                case 'grp':
                    // ilias course is used for the campo event
                    if ($parent_ref = $this->findParentCourse($ref_id)) {
                        $this->updateIliasCourse($parent_ref, $term, $event, null);
                    }
                    else {
                        $this->study->repo()->save($course->withIliasProblem("Parent ILIAS course of group not found!"));
                        continue 2;
                    }

                    // ilias group is used for the campo course
                    $this->updateIliasGroup($ref_id, $term, $event, $course);
                    $this->updateIliasParticipants($course->getCourseId(), $parent_ref, $ref_id);
                    break;

                default:
                    $this->study->repo()->save($course->withIliasProblem("ILIAS object for course is neither a course nor a group!"));
                    continue 2;
            }
            $updated++;
        }
        return $updated;

    }

    /**
     * Find or create the ILIAS category where the course for an event and term should be created
     * @return ?int ref_id of the created category of null if not possible
     */
    protected function findOrCreateCourseCategory(Course $course, Term $term) : ?int
    {
        // search for an org unit that allows course creation and has an ilias category assigned
        foreach ($this->study->repo()->getEventOrgunitsByEventId($course->getEventId()) as $unit) {
            if (empty($responsibleUnit = $this->org->repo()->getOrgunitByNumber($unit->getFauorgNr()))) {
                $this->study->repo()->save($course->withIliasProblem(
                    'Responsible org unit ' . $unit->getFauorgNr() . ' not found!'));
                continue; // next unit
            }
            if (empty($creationUnit = $this->findOrgUnitForCourseCreation($responsibleUnit))) {
                $this->org->repo()->save($responsibleUnit->withProblem(
                    "No org unit with ilias category found for course creation!\n    "
                    . implode("\n    ", $this->org->getOrgPathLog($responsibleUnit,true))
                ));
                continue;   // next unit
            }
            break;  // creationUnit found
        }
        if (empty($creationUnit)) {
            $this->study->repo()->save($course->withIliasProblem("No org unit found for course creation!"));
            return null;
        }

        // check if the assigned ilias reference is a category and not deleted
        if (ilObject::_lookupType($creationUnit->getIliasRefId(), true) != 'cat'
            || ilObject::_isInTrash($creationUnit->getIliasRefId())) {
            $this->study->repo()->save($creationUnit->withProblem('No ILIAS category found for the ref_id'));
            $this->study->repo()->save($course->withIliasProblem("No org unit found for course creation!"));
            return null;
        }

        // find the sub category for course creation in the term
        foreach($this->dic->repositoryTree()->getChildsByType($creationUnit->getIliasRefId(), 'cat') as $node) {
            if (ImportId::fromString(ilObject::_lookupImportId($node['obj_id']))->getTermId() == $term->toString()) {
                return (int) $node['child'];
            }
        }
        return  $this->createCourseCategory($creationUnit->getIliasRefId(), $term);
    }

    /**
     * Find an org unit in the path of a unit that should be used for course creation
     * If there is a parent with "collect courses" and an ILIAS ref_id assigned, take this one
     * Otherwise take the nearest ancestor with ref_id assigned and not "no_manager"
     * @param Orgunit $unit
     * @return Orgunit|null
     */
    protected function findOrgUnitForCourseCreation(Orgunit $unit) : ?Orgunit
    {
        $found = null;
        foreach (array_reverse($this->org->getPathUnits($unit)) as $pathUnit) {

            // always take the highest collector if ilias object is assigned
            if (!empty($pathUnit->getIliasRefId()) && $pathUnit->getCollectCourses()) {
                $found = $pathUnit;
            }
            // take the nearest parent if ilias object is assigned
            elseif (!empty($pathUnit->getIliasRefId()) && !$pathUnit->getNoManager() && empty($found)) {
                $found = $pathUnit;
            }
        }
        return $found;
    }

    /**
     * Find the parent course of a group
     */
    protected function findParentCourse(int $ref_id) : ?int
    {
        foreach ($this->dic->repositoryTree()->getPathId($ref_id) as $path_id) {
            if (ilObject::_lookupType($path_id, true) == 'crs') {
                return $path_id;
            }
        }
        return null;
    }

    /**
     * Create the category hat should get new courses of a term
     */
    protected function createCourseCategory(int $parent_ref_id, Term $term): int
    {
        $lng = $this->dic->language();

        $category = new ilObjCategory();
        $category->setTitle($lng->txtlng('fau', 'fau_campo_courses', 'de')
            . ': ' . $this->study->getTermTextForLang($term, 'de'));
        $category->setDescription($lng->txtlng('fau', 'fau_campo_courses_desc', 'de'));
        $category->setImportId(ImportId::fromObjects($term)->toString());
        $category->setOwner($this->settings->getDefaultOwnerId());
        $category->create();

        $trans = $category->getObjectTranslation();
        $trans->addLanguage('en',
            $lng->txtlng('fau', 'fau_campo_courses', 'en')
                    . ': ' . $this->study->getTermTextForLang($term, 'en'),
            $lng->txtlng('fau', 'fau_campo_courses_desc', 'en'),
            false);
        $trans->save();

        $category->createReference();
        $category->putInTree($parent_ref_id);
        $category->setPermissions($parent_ref_id);
        return $category->getRefId();
    }

    /**
     * Create an ILIAS course for a campo event and/or course (parallel group)
     * The ilias course will always work as a container for the event
     * If a campo course is given then the ilias course should work as container for that parallel group
     * @return int  ref_id of the course
     */
    protected function createIliasCourse(int $parent_ref_id, Term $term, Event $event, ?Course $course): int
    {
        $object = new IlObjCourse();
        $object->setTitle($event->getTitle()); // will be changed updateIliasCourse
        $object->setImportId(ImportId::fromObjects($term, $event, $course)->toString());
        $object->setOwner($this->settings->getDefaultOwnerId());
        $object->create();
        $object->putInTree($parent_ref_id);
        $object->setPermissions($parent_ref_id);

        if (isset($course)) {
            $this->study->repo()->save($course->withIliasObjId($object->getId())->withIliasProblem(null));
        }
        return $object->getRefId();
    }

    /**
     * Create an ILIAS group for a campo course (parallel group)
     * @return int  ref_id of the course
     */
    protected function createIliasGroup(int $parent_ref_id, Term $term, Event $event, Course $course): int
    {
        $object = new ilObjGroup();
        $object->setTitle($course->getTitle());
        $object->setImportId(ImportId::fromObjects($term, $event, $course)->toString());
        $object->setOwner($this->settings->getDefaultOwnerId());
        $object->create();
        $object->putInTree($parent_ref_id);
        $object->setPermissions($parent_ref_id);

        // todo: set didactic template for parallel group

        $this->study->repo()->save($course->withIliasObjId($object->getId())->withIliasProblem(null));
        return $object->getRefId();
    }


    /**
     * Update the ILIAS course for a campo event and/or course (parallel group)
     * The ilias course will always work as a container for the event
     * If a campo course is provided then the ilias course should work as container for that parallel group
     * The Course is only updated if it is not yed manually changed
     */
    protected function updateIliasCourse(int $ref_id, Term $term, Event $event, ?Course $course)
    {
        $object = new ilObjCourse($ref_id);

        if (!$this->isObjectManuallyChanged($object)) {
            if (isset($course)) {
                $object->setTitle($course->getTitle());
                $object->setSyllabus($course->getContents());
                $object->setImportantInformation($course->getCompulsoryRequirement());
                if(empty($course->getAttendeeMaximum())) {
                   $object->enableSubscriptionMembershipLimitation(false);
                   $object->setSubscriptionMaxMembers(0);
                }
                else {
                    $object->enableSubscriptionMembershipLimitation(true);
                    $object->setSubscriptionMaxMembers($course->getAttendeeMaximum());
                }
            }
            else {
                $object->setTitle($event->getTitle());
                $object->setImportantInformation($event->getComment());
            }
            $object->setDescription($event->getEventtype() . ($event->getShorttext() ? ' (' . $event->getShorttext() . ')' : ''));
            $object->update();
            $this->sync->repo()->resetObjectLastUpdate($object->getId());
        }

        if (isset($course)) {
            $this->study->repo()->save($course->asChanged(null));
        }
    }


    /**
     * Update the ILIAS group for a campo course (parallel group)
     * The ilias group will always work as a container for the course
     * The group is only updated if it is not yed manually changed
     */
    protected function updateIliasGroup(int $ref_id, Term $term, Event $event, Course $course)
    {
        $object = new ilObjGroup($ref_id);

        if(!$this->isObjectManuallyChanged($object)) {
            $object->setTitle($course->getTitle());
            $object->setInformation(
                ilUtil::secureString($course->getContents()) . "\n"
                . ilUtil::secureString($course->getCompulsoryRequirement()));

            if(empty($course->getAttendeeMaximum())) {
                $object->enableMembershipLimitation(false);
                $object->setMaxMembers(0);
            }
            else {
                $object->enableMembershipLimitation(true);
                $object->setMaxMembers($course->getAttendeeMaximum());
            }
            $object->update();
            $this->sync->repo()->resetObjectLastUpdate($object->getId());
        }

        $this->study->repo()->save($course->asChanged(null));
    }


    /**
     * Update the roles of responsibles and instructors in an ilias course or group
     * This is done by comparing the actual responsibles and instructors tables with the members table
     * At the end the members table should reflect the status in campo for existing users
     *
     *
     * @see Member
     */
    protected function updateIliasParticipants(int $course_id, int $ref_id_for_event, int $ref_id_for_course)
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
     * Update the users of a certain ilias course or group role
     * Update the role setting in the members table of these users
     *
     *
     * @param string $mem_role                          role that should be checked in a member record
     * @param int[] $user_ids                           list of users that should get the role
     * @param int $mem_obj_id                           object id for new member records
     * @param ilCourseParticipants $crs_participants    participants of ilias course
     * @param ?ilGroupParticipants $grp_participants    participants of ilias group
     * @param Member[] &$members                        array of member data (user_id => Member) to be checked
     * @param Member[] &$touched                        array of member data (user_id => Member) to be saved later
     */
    protected function updateRole (
        string $mem_role,
        array $user_ids,
        int $mem_obj_id,
        ilCourseParticipants $crs_participants,
        ?ilGroupParticipants $grp_participants,
        array &$members,
        array &$touched
    )
    {
        switch ($mem_role)
        {
            case Member::ROLE_EVENT_RESPONSIBLE:
                $crs_role = IL_CRS_ADMIN;
                $grp_role = null;
                break;

            case Member::ROLE_COURSE_RESPONSIBLE:
            case Member::ROLE_INSTRUCTOR:
            case Member::ROLE_INDIVIDUAL_INSTRUCTOR:
                $crs_role = isset($grp_participants) ?  IL_CRS_TUTOR : IL_CRS_ADMIN;
                $grp_role = isset($grp_participants) ? IL_GRP_ADMIN : null;
                break;
        }

        $mem_ids = [];
        foreach ($members as $user_id => $member) {
            if ($member->hasRole($mem_role)) {
                $mem_ids[] = $user_id;
            }
        }

        // added users
        foreach (array_diff($user_ids, $mem_ids) as $user_id) {

            $member = $members[$user_id] ?? new Member($mem_obj_id, $user_id);
            $member = $member->withRole($mem_role, true);
            $members[$user_id] = $member;
            $touched[$user_id] = $member;

            $this->addRole($crs_participants, $user_id, $crs_role);
            if (isset($grp_participants) && isset($grp_role)) {
                $this->addRole($grp_participants, $user_id, $grp_role);
            }
        }

        // removed users
        foreach (array_diff($mem_ids, $user_ids) as $user_id) {

            $member = $members[$user_id] ?? new Member($mem_obj_id, $user_id);
            $member = $member->withRole($mem_role, false);
            $members[$user_id] = $member;
            $touched[$user_id] = $member;

            $this->removeRole($crs_participants, $user_id, $crs_role);
            if (isset($grp_participants) && isset($grp_role)) {
                $this->removeRole($grp_participants, $user_id, $grp_role);
            }
        }
    }


    protected function createIliasCourseRolesForNewUser(int $user_id)
    {
        // todo
    }

    protected function createIliasGroupRolesForNewUser(int $user_id)
    {
        // todo
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

    /**
     * Check if an object has been manually changed
     */
    protected function isObjectManuallyChanged(ilObject $object) : bool
    {
        $created = (int) $this->tools->dbTimestampToUnix($object->getCreateDate());
        $updated = (int) $this->tools->dbTimestampToUnix($object->getLastUpdateDate());

        // give 5 min tolerance
        return $updated > $created + 300;
    }
}