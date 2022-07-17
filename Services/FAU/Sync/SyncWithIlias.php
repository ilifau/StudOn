<?php

namespace FAU\Sync;

use Exception;
use ILIAS\DI\Container;
use FAU\Study\Data\Term;
use FAU\Study\Data\Course;
use FAU\Study\Data\Event;
use ilObject;
use ilObjCourse;
use FAU\Study\Data\ImportId;
use ilObjGroup;
use ilUtil;
use ilDidacticTemplateSetting;
use ilRepUtil;

/**
 * Synchronize the campo courses with the related ILIAS objects
 *
 * The relation of campo courses to ilias objects is given by the property ilias_obj_id
 * Campo courses that need an update of the related object are marked with ilias_dirty_since
 * The dirty flag is deleted when the ilias objects are updated
 */
class SyncWithIlias extends SyncBase
{
    protected Container $dic;
    protected Service $service;

    protected int $owner_id;
    protected int $group_didactic_template_id;
    protected int $course_didactic_template_id;

    /**
     * Initialize the class variables
     */
    protected function init() : bool
    {
        // ensure that new objects are created with a specific owner
        $this->owner_id = $this->settings->getDefaultOwnerId();
        if (empty($this->owner_id || $this->owner_id == 6)) {
            $this->addError('Missing owner id for the creation of objects!');
            return false;
        }

        // ensure that a didactic template exists for the creation of groups
        $this->group_didactic_template_id = $this->settings->getGroupDidacticTemplateId();
        if (empty($this->group_didactic_template_id)) {
            $this->addError('Missing didactic template id for the creation of groups!');
            return false;
        }
        $template = new ilDidacticTemplateSetting( $this->group_didactic_template_id);
        if (!isset($template) || !$template->isEnabled()) {
            $this->addError('Didactic template ' . $this->group_didactic_template_id . " not found or not enabled!");
            return false;
        }

        // ensure that didactic templates exist for the creation of courses and groups
        $this->course_didactic_template_id = $this->settings->getCourseDidacticTemplateId();
        if (empty($this->course_didactic_template_id)) {
            $this->addError('Missing didactic template id for the creation of courses!');
            return false;
        }
        $template = new ilDidacticTemplateSetting( $this->course_didactic_template_id);
        if (!isset($template) || !$template->isEnabled()) {
            $this->addError('Didactic template ' . $this->course_didactic_template_id . " not found or not enabled!");
            return false;
        }

        return true;
    }

    /**
     * Synchronize the campo courses for selected terms
     * @param int|null $orgunit_id optional restriction to an orgunit and their subunits
     * @param bool $negate negate the restriction (find courses not within the orgunit)
     */
    public function synchronize(?int $orgunit_id = null, ?bool $negate = false) : void
    {
        $unit_ids = null;
        if (!empty($orgunit_id)) {
            if (!empty($unit = $this->org->repo()->getOrgunit($orgunit_id))) {
                $unit_ids = $this->org->repo()->getOrgunitIdsByPath($unit->getPath());
            }
            else {
                $unit_ids = [];
            }
        }

        if ($this->init()) {
            foreach ($this->sync->getTermsToSync() as $term) {
                $this->info('SYNC term ' . $term->toString() . '...');

                // restrict to the courses within or not within the units, if given
                $course_ids = null;
                if (isset($unit_ids)) {
                    $course_ids = $this->study->repo()->getCourseIdsOfOrgUnitsInTerm($unit_ids, $term, false, $negate);
                }

                $this->increaseItemsAdded($this->createCourses($term, $course_ids));
                $this->increaseItemsUpdated($this->updateCourses($term, $course_ids));
            }
        }
    }

    /**
     * Create the ilias objects for courses (parallel groups) of a term
     * @return int number of created courses
     */
    public function createCourses(Term $term, ?array $course_ids = null, bool $test_run = false) : int
    {
        if (!$this->init()) {
            return 0;
        }

        $created = 0;
        foreach ($this->study->repo()->getCoursesByTermToCreate($term, $course_ids) as $course) {
            $this->info('CREATE ' . $course->getTitle() . '...');

            // order of checks is important!
            if (!empty($course->getIliasObjId())) {
                $this->info('Already created.');
                continue;
            }
            elseif ($course->isDeleted()) {
                // deleted course without ilias object needs no processing - just remove it
                $this->study->repo()->delete($course);
                continue;
            }
            elseif ($course->isCancelled()) {
                $this->info('Course is cancelled.');
                continue;
            }
            elseif (empty($event = $this->study->repo()->getEvent($course->getEventId()))) {
                $this->info('Failed: Event for course not found.');
                $this->study->repo()->save($course->withIliasProblem('Event not found for this course!'));
                continue;
            }

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
                    if ($other->getCourseId() != $course->getCourseId()
                        && !$other->isDeleted()
                        && !empty($other_ref_id = $this->sync->trees()->getIliasRefIdForCourse($other))) {
                        $other_refs[] = $other_ref_id;
                        switch (ilObject::_lookupType($other_ref_id, true)) {
                            case 'crs':
                                // other parallel groups are already ilias courses, create the same
                                $action = 'create_single_course';
                                break;
                            case 'grp':
                                // other parallel groups are ilias groups, create the new in the same course
                                $action = 'create_group_in_course';
                                $parent_ref = $this->dic->repositoryTree()->getParentId($other_ref_id);
                                break;
                        }
                    }
                }
            }

            // get or create the place for a new course if no parent_ref is set above
            // don't create the object for this course if no parent_ref can be found
            if (empty($parent_ref = $parent_ref ?? $this->sync->trees()->findOrCreateCourseCategory($course, $term))) {
                $this->info('Failed: no suitable parent found.');
                continue;
            }

            if ($test_run) {
                $this->study->repo()->save($course->withIliasProblem(null));
                continue;
            }

            // create the object(s)
            switch ($action) {
                case 'create_single_course':
                    $ref_id = $this->createIliasCourse($parent_ref, $term, $event, $course);
                    $this->updateIliasCourse($ref_id, $term, $event, $course);
                    $this->sync->roles()->updateParticipants($course->getCourseId(), $ref_id, $ref_id);
                    break;

                case 'create_course_and_group':
                    $parent_ref = $this->createIliasCourse($parent_ref, $term, $event, null);
                    $ref_id = $this->createIliasGroup($parent_ref,  $term, $event, $course);
                    // don't use course data for the event - courses are the groups inside
                    $this->updateIliasCourse($parent_ref, $term, $event, null) ;
                    $this->updateIliasGroup($ref_id, $term, $event, $course);
                    $this->sync->roles()->updateParticipants($course->getCourseId(), $parent_ref, $ref_id);
                    break;

                case 'create_group_in_course':
                    // course for the event already exists
                    $ref_id = $this->createIliasGroup($parent_ref, $term, $event, $course);
                    $this->updateIliasGroup($ref_id, $term, $event, $course);
                    $this->sync->roles()->updateParticipants($course->getCourseId(), $parent_ref, $ref_id);
                    break;

                default:
                    $this->info('Failed: unknown action.');
                    continue 2;
            }

            // create or update the membership limitation
            if (!empty($other_refs)) {
               if (!empty($grouping = $this->sync->groupings()->findCommonGrouping($other_refs))) {
                  $this->sync->groupings()->addReferenceToGrouping($ref_id, $grouping);
               }
               else {
                   array_push($other_refs, $ref_id);
                   $this->sync->groupings()->createCommonGrouping($other_refs, $event->getTitle());
               }
            }

            // set the course as proceeded
            $this->study->repo()->save(
                $course->withIliasObjId(ilObject::_lookupObjId($ref_id))
                ->withIliasProblem(null)
                ->asChanged(false)
            );

            $created++;
        }
        return $created;
    }


    /**
     * Update the courses of a term
     * This should also treat the event related courses
     * @return int number of updated courses
     */
    public function updateCourses(Term $term, ?array $course_ids = null, bool $test_run = false) : int
    {
        if (!$this->init()) {
            return 0;
        }

        $updated = 0;
        foreach ($this->study->repo()->getCoursesByTermToUpdate($term, $course_ids) as $course) {
            $this->info('UPDATE ' . $course->getTitle() . '...');

            $event = $this->study->repo()->getEvent($course->getEventId());

            // get the reference to the ilias course or group
            $ref_id = $this->sync->trees()->getIliasRefIdForCourse($course);
            if (empty($ref_id)) {
                if ($course->isDeleted()) {
                    $this->study->repo()->delete($course);
                }
                else {
                    $this->study->repo()->save($course->withIliasObjId(null));
                }
                continue;
            }

            $parent_ref = null;
            switch (ilObject::_lookupType($course->getIliasObjId()))
            {
                case 'crs':
                    $action = 'update_single_course';
                    break;

                case 'grp':
                    $action = 'update_group_in_course';
                    if (empty($parent_ref = $this->sync->trees()->findParentIliasCourse($ref_id))) {
                        $this->study->repo()->save($course->withIliasProblem("Parent ILIAS course of group not found!"));
                        continue 2;
                    }
                    break;

                default:
                    $this->study->repo()->save($course->withIliasProblem("ILIAS object for course is neither a course nor a group!"));
                    continue 2;
            }

            if ($test_run) {
                $this->study->repo()->save($course->withIliasProblem(null));
                continue;
            }

            // delete the ilias object if campo course is marked as deleted
            if ($this->processDeleted($ref_id, $course)) {
                $updated++;
                continue;
            }

            switch ($action)
            {
                case 'update_single_course':
                    // ilias course is used for campo event and course, ref_ids are the same
                    $this->updateIliasCourse($ref_id, $term, $event, $course);
                    $this->sync->roles()->updateParticipants($course->getCourseId(), $ref_id, $ref_id);
                    break;

                case 'update_group_in_course':
                    // ilias course is used for the campo event
                    $this->updateIliasCourse($parent_ref, $term, $event, null);
                    // ilias group is used for the campo course, ref_ids are different
                    $this->updateIliasGroup($ref_id, $term, $event, $course);
                    $this->sync->roles()->updateParticipants($course->getCourseId(), $parent_ref, $ref_id);
                    break;
            }

            // set the course as proceeded
            $this->study->repo()->save(
                $course->withIliasProblem(null)->asChanged(false)
            );
            $updated++;
        }
        return $updated;
    }


    /**
     * Create an ILIAS course for a campo event and/or course (parallel group)
     * The ilias course will always work as a container for the event
     * If a campo course is given then the ilias course should work as container for that parallel group
     * @return int  ref_id of the course
     */
    protected function createIliasCourse(int $parent_ref_id, Term $term, Event $event, ?Course $course): int
    {
        $object = new ilObjCourse();
        $object->setTitle($event->getTitle()); // will be changed updateIliasCourse
        $object->setImportId(ImportId::fromObjects($term, $event, $course)->toString());
        $object->setOwner($this->owner_id);
        $object->create();
        $object->createReference();
        $object->putInTree($parent_ref_id);
        $object->setPermissions($parent_ref_id);
        if ($this->study->repo()->countCoursesOfEventInTerm($event->getEventId(), $term) > 1) {
            $object->applyDidacticTemplate($this->course_didactic_template_id);
        }
        $object->setOfflineStatus(false);
        $object->update();
        return $object->getRefId();
    }

    /**
     * Create an ILIAS group for a campo course (parallel group)
     * @return int  ref_id of the course
     */
    protected function createIliasGroup(int $parent_ref_id, Term $term, Event $event, Course $course): int
    {
        $object = new ilObjGroup();
        $object->setTitle($course->getTitle()); // will be changed updateIliasGroup
        $object->setImportId(ImportId::fromObjects($term, $event, $course)->toString());
        $object->setOwner($this->owner_id);
        $object->create();
        $object->createReference();
        $object->putInTree($parent_ref_id);
        $object->setPermissions($parent_ref_id);
        $object->applyDidacticTemplate($this->group_didactic_template_id);
        return $object->getRefId();
    }


    /**
     * Process a 'deleted' flag in the course
     * Try to delete the ilias object of it is not yet touched
     * At least give a hint in the description and cut the campo connection
     *
     * @return bool     successfully processed (ilias object no longer exists)
     */
    protected function processDeleted(int $ref_id, Course $course) : bool
    {
        if (!$course->isDeleted()) {
            // nothing to do => nothing done
            return false;
        }

        // get the object, correct type is already checked in the caller
        if (ilObject::_lookupType($ref_id, true) == 'crs') {
            $object = new ilObjCourse($ref_id);
        }
        elseif (ilObject::_lookupType($ref_id, true) == 'grp') {
            $object = new ilObjGroup($ref_id);
        }
        else {
            return false;
        }

        // always provide the info and delete the import id
        // do not yet update to allow a check for manual changes
        $object->setDescription($this->lng->txt('fau_campo_course_is_deleted'));
        $object->setImportId(null);

        if ($this->isObjectManuallyChanged($object)
            || $this->dic->fau()->sync()->trees()->hasUndeletedContents($ref_id)
            || $this->dic->fau()->sync()->roles()->hasLocalMemberChanges($ref_id)
        )
        {
            // object is already touched by an admin => just save the info
            $object->update();
        }
        else {
            // object is not yet changed => object can be deleted
            // save the changes, even if object will be moved to trash
            $object->update();
            try {
                // this checks delete permission on all objects
                // so the cron job user needs the global admin role!
                ilRepUtil::deleteObjects($this->dic->repositoryTree()->getParentId($ref_id), [$ref_id]);
            }
            catch (Exception $e) {
                throw $e;
                //ignore the error - the info is already saved
            }
        }

        // always delete the course record, the staging record is already deleted
        $this->study->repo()->delete($course);
        return true;
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

            $object->setTitle($this->buildTitle($term, $event, $course));
            $object->setDescription($this->buildDescription($event, $course));
            $object->setImportantInformation($event->getComment());

            if (isset($course)) {
                $object->setSyllabus($course->getContents());

                if(empty($course->getAttendeeMaximum())) {
                    $object->enableSubscriptionMembershipLimitation(false);
                    $object->setSubscriptionMaxMembers(0);
                }
                else {
                    $object->enableSubscriptionMembershipLimitation(true);
                    $object->setSubscriptionMaxMembers($course->getAttendeeMaximum());
                }
                if ($course->isCancelled()) {
                    $object->setDescription($this->lng->txt('fau_campo_course_is_cancelled'));
                }
            }
            $object->update();
            $this->sync->repo()->resetObjectLastUpdate($object->getId());
        }
        elseif (isset($course) && $course->isCancelled()) {
            $object->setDescription($this->lng->txt('fau_campo_course_is_cancelled'));
            $object->update();
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
            $object->setTitle($this->buildTitle($term, $event, $course));
            $object->setDescription($this->buildDescription($event, $course));
            $object->setInformation(ilUtil::secureString($course->getContents())); // remove html
            $object->setRegistrationType(GRP_REGISTRATION_DEACTIVATED);

            if(empty($course->getAttendeeMaximum())) {
                $object->enableMembershipLimitation(false);
                $object->setMaxMembers(0);
            }
            else {
                $object->enableMembershipLimitation(true);
                $object->setMaxMembers($course->getAttendeeMaximum());
            }
            if ($course->isCancelled()) {
                $object->setDescription($this->lng->txt('fau_campo_course_is_cancelled'));
            }
            $object->update();
            $this->sync->repo()->resetObjectLastUpdate($object->getId());
        }
        elseif ($course->isCancelled()) {
            $object->setDescription($this->lng->txt('fau_campo_course_is_cancelled'));
            $object->update();
        }
    }

    /**
     * Build the object title
     */
    protected function buildTitle(Term $term, Event $event, ?Course $course) : string
    {
        if (isset($course)) {
            $title = $course->getTitle();
            if ($this->study->repo()->countCoursesOfEventInTerm($event->getEventId(), $term) > 1) {
                $title .= $course->getKParallelgroupId() ? ' ( ' . $this->lng->txt('fau_course') . ' ' . $course->getKParallelgroupId() . ')' : '';
            }
        }
        else {
            $title = $event->getTitle();
        }
        return (string) $title;
    }

    /**
     * Build the object description
     */
    protected function buildDescription(Event $event, ?Course $course) : string
    {
        $desc = [];
        if ($event->getEventtype()) {
            $desc[] = $event->getEventtype();
        }
        if ($event->getShorttext()) {
            $desc[] = $event->getShorttext();
        }
        if (isset($course)) {
            if ($course->getHoursPerWeek()) {
                $desc[] = $course->getHoursPerWeek() . ' ' . $this->lng->txt('fau_sws');
            }
            if ($course->getTeachingLanguage()) {
                $desc[] = $course->getTeachingLanguage();
            }
        }

        return implode(', ', $desc);
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