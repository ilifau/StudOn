<?php

namespace FAU\Sync;

use Exception;
use FAU\Study\Data\LostCourse;
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
use ilConditionHandler;
use ilChangeEvent;

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
     */
    public function synchronize(?int $orgunit_id = null) : void
    {
        $create_unit_ids = null;
        $update_unit_ids = null;

        if (!empty($orgunit_id)) {
            $create_unit_ids =  $this->sync->trees()->getOrgUnitIdsWithDescendants([$orgunit_id]);
            $update_unit_ids = $create_unit_ids;
        }
        elseif (!empty($this->settings->getRestrictCreateOrgIds())) {
            $create_unit_ids = $this->sync->trees()->getOrgUnitIdsWithDescendants($this->settings->getRestrictCreateOrgIds());
        }

        if ($this->init()) {
            foreach ($this->sync->getTermsToSync() as $term) {
                $this->info('SYNC term ' . $term->toString() . '...');

                // restrict to the courses within the selected units, if given
                $create_course_ids = null;
                $update_course_ids = null;
                // respect a restriction only if given by parameter or for the next semester
                if (isset($create_unit_ids) && (isset($orgunit_id) || $term->toString() == $this->dic->fau()->study()->getNextTerm()->toString())) {
                    $create_course_ids = $this->study->repo()->getCourseIdsOfOrgUnitsInTerm($create_unit_ids, $term, false);
                }
                if (isset($update_unit_ids)) {
                    $update_course_ids = $this->study->repo()->getCourseIdsOfOrgUnitsInTerm($update_unit_ids, $term, false);
                }

                $this->increaseItemsAdded($this->createCourses($term, $create_course_ids));
                $this->increaseItemsUpdated($this->updateCourses($term, $update_course_ids));
                $this->increaseItemsUpdated($this->moveLostCourses($term));
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

            // clear old problem
            $this->study->repo()->save($course->withIliasProblem(null));

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

            if (!empty($reuse_ref = $this->getReusableRefId($course))) {
                // check what to restore
                if (ilObject::_lookupType($reuse_ref, true) == 'crs') {
                    $action = 'reuse_single_course';
                    $this->info('REUSE single course');
                }
                else {
                    $action = 'reuse_group_in_course';
                    $parent_ref = $this->ilias->objects()->findParentIliasCourse($reuse_ref);
                    $this->info('REUSE group in course');
                }
            }
            else {
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
                            && !empty($other_ref_id = $this->ilias->objects()->getIliasRefIdForCourse($other))) {
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
                    // problem is already saved in the function
                    $this->info('Failed: no suitable parent found.');
                    continue;
                }
            }


            if ($test_run) {
                continue;
            }

            // create the object(s)
            switch ($action) {
                case 'reuse_single_course':
                    $ref_id = $reuse_ref;
                    $obj_id = ilObject::_lookupObjId($ref_id);

                    // restore the connection and force an update of title and description
                    $this->sync->repo()->updateObjectFauImportId($obj_id, new ImportId($term->toString(), $course->getEventId(), $course->getCourseId()));
                    $this->study->repo()->save($course->withTitleDirty(true)->withDescriptionDirty(true));

                    $this->updateIliasCourse($ref_id, $term, $event, $course, true);
                    $this->sync->roles()->updateIliasRolesOfCourse($ref_id, $course->getCourseId());
                    break;

                case 'reuse_group_in_course':
                    $ref_id = $reuse_ref;
                    $obj_id = ilObject::_lookupObjId($ref_id);
                    $parent_obj_id = ilObject::_lookupObjId($parent_ref);

                    // restore the connection and force an update of title and description
                    $this->sync->repo()->updateObjectFauImportId($obj_id, new ImportId($term->toString(), $course->getEventId(), $course->getCourseId()));
                    $this->sync->repo()->updateObjectFauImportId($parent_obj_id, new ImportId($term->toString(), $course->getEventId()));
                    $this->study->repo()->save($course->withTitleDirty(true)->withDescriptionDirty(true)
                         ->withEventTitleDirty(true)->withEventDescriptionDirty(true));

                    $this->updateIliasGroup($ref_id, $term, $event, $course);
                    $this->updateIliasCourse($parent_ref, $term, $event, $course, false);
                    $this->sync->roles()->updateIliasRolesOfCourse($ref_id, $course->getCourseId(), $parent_ref, $event->getEventId(), $term);
                    break;


                case 'create_single_course':
                    $ref_id = $this->dic->fau()->ilias()->objects()->createIliasCourse($parent_ref, $term, $event, $course)->getRefId();
                    $this->updateIliasCourse($ref_id, $term, $event, $course, true);
                    $this->sync->roles()->updateIliasRolesOfCourse($ref_id, $course->getCourseId());
                    break;

                case 'create_course_and_group':
                    $parent_ref = $this->dic->fau()->ilias()->objects()->createIliasCourse($parent_ref, $term, $event, null)->getRefId();
                    $ref_id = $this->dic->fau()->ilias()->objects()->createIliasGroup($parent_ref,  $term, $event, $course)->getRefId();
                    // don't use course data for the event - courses are the groups inside
                    $this->updateIliasCourse($parent_ref, $term, $event, $course, false);
                    $this->updateIliasGroup($ref_id, $term, $event, $course);
                    $this->sync->roles()->updateIliasRolesOfCourse($ref_id, $course->getCourseId(), $parent_ref, $event->getEventId(), $term);
                    break;

                case 'create_group_in_course':
                    // course for the event already exists
                    $ref_id = $this->dic->fau()->ilias()->objects()->createIliasGroup($parent_ref, $term, $event, $course)->getRefId();
                    $this->updateIliasGroup($ref_id, $term, $event, $course);
                    $this->sync->roles()->updateIliasRolesOfCourse($ref_id, $course->getCourseId(), $parent_ref, $event->getEventId(), $term);
                    break;

                default:
                    $this->info('Failed: unknown action.');
                    continue 2;
            }

            // create or update the membership limitation
            if (!empty($other_refs)) {
               if (!empty($grouping = $this->ilias->groupings()->findCommonGrouping($other_refs))) {
                  $this->ilias->groupings()->addReferenceToGrouping($ref_id, $grouping);
               }
               else {
                   array_push($other_refs, $ref_id);
                   $this->ilias->groupings()->createCommonGrouping($other_refs, $event->getTitle());
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
            $ref_id = $this->ilias->objects()->getIliasRefIdForCourse($course);
            if (empty($ref_id)) {
                if ($course->isDeleted()) {
                    $this->study->repo()->delete($course);
                }
                else {
                    $this->study->repo()->save($course->withIliasObjId(null)->asChanged(false));
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
                    if (empty($parent_ref = $this->ilias->objects()->findParentIliasCourse($ref_id))) {
                        $this->study->repo()->save($course->withIliasProblem("Parent ILIAS course of group not found!"));
                        continue 2;
                    }
                    break;

                default:
                    $this->study->repo()->save($course->withIliasProblem("ILIAS object for course is neither a course nor a group!"));
                    continue 2;
            }

            if ($test_run) {
                $this->study->repo()->save($course->withIliasProblem(null)->asChanged(false));
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
                    $this->updateIliasCourse($ref_id, $term, $event, $course, true);
                    $this->sync->roles()->updateIliasRolesOfCourse($ref_id, $course->getCourseId());
                    break;

                case 'update_group_in_course':
                    // ilias course is used for the campo event
                    $this->updateIliasCourse($parent_ref, $term, $event, $course, false);
                    // ilias group is used for the campo course, ref_ids are different
                    $this->updateIliasGroup($ref_id, $term, $event, $course);
                    $this->sync->roles()->updateIliasRolesOfCourse($ref_id, $course->getCourseId(), $parent_ref, $event->getEventId(), $term);
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
     * Move courses from fallback categories to their correct destination, if possible
     * @return int number of moved courses
     */
    public function moveLostCourses(Term $term): int
    {
        $moved = 0;
        $treeMatching = $this->dic->fau()->sync()->trees();
        foreach ($this->settings->getMoveParentCatIds() as $parent_id) {
            if (!empty($source_cat_id = (int) $treeMatching->findCourseCategoryForParent($parent_id, $term))) {
                foreach ($treeMatching->findCoursesInCategory($source_cat_id) as $ref_id => $import_id) {
                    if (!empty($event_id = ImportId::fromString($import_id)->getEventId())) {
                        if (!empty($dest_cat_id = $treeMatching->findOrCreateCourseCategoryForEvent($event_id, $term))) {
                            if ($dest_cat_id != $source_cat_id) {
                                $this->info("MOVE $ref_id from $source_cat_id to $dest_cat_id");
                                $this->moveObject($ref_id, $source_cat_id, $dest_cat_id);
                                $moved++;
                            }
                        }
                    }
                }
            }
        }
        return $moved;
    }

    /**
     * Move an object in the repository tree
     */
    protected function moveObject(int $ref_id, int $source_parent_ref_id, int $dest_parent_ref_id)
    {
        $tree = $this->dic->repositoryTree();
        $obj_id = ilObject::_lookupObjId($ref_id);

        $tree->moveTree($ref_id, $dest_parent_ref_id);
        $this->dic->rbac()->admin()->adjustMovedObjectPermissions($ref_id, $source_parent_ref_id);
        ilConditionHandler::_adjustMovedObjectConditions($ref_id);

        ilChangeEvent::_recordWriteEvent(
            $obj_id,
            $this->dic->user()->getId(),
            'remove',
            ilObject::_lookupObjId($source_parent_ref_id)
        );
        ilChangeEvent::_recordWriteEvent(
            $obj_id,
            $this->dic->user()->getId(),
            'add',
            ilObject::_lookupObjId($dest_parent_ref_id)
        );
        ilChangeEvent::_catchupWriteEvents($obj_id, $this->dic->user()->getId());
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

        // get the reference of a parent course
        if (!empty($parent_ref = $this->ilias->objects()->findParentIliasCourse($ref_id))) {
            $parent_course = new ilObjCourse($parent_ref);
        }

        // get the object, correct type is already checked in the caller
        if (ilObject::_lookupType($ref_id, true) == 'crs') {
            $object = new ilObjCourse($ref_id);
            $object->setDescription($this->lng->txt('fau_campo_course_is_missing_for_ilias_course'));
        }
        elseif (ilObject::_lookupType($ref_id, true) == 'grp') {
            $object = new ilObjGroup($ref_id);
            $object->setDescription($this->lng->txt('fau_campo_course_is_missing_for_ilias_group'));
        }
        else {
            return false;
        }

        // always provide the info and delete the import id
        // do not yet update to allow a check for manual changes
        $object->setTitle($this->lng->txt('fau_campo_course_is_missing_prefix') . ' ' . $object->getTitle());
        $object->setImportId(null);

//        echo "Manually changed: " . $this->isObjectManuallyChanged($object) . "\n";
//        echo "UndeletedContents: " . $this->ilias->objects()->hasUndeletedContents($ref_id) . "\n";
//        echo "LocalMemberChanges:" . $this->sync->roles()->hasLocalMemberChanges($ref_id) . "\n";

        if ($this->isObjectManuallyChanged($object)
            || $this->ilias->objects()->hasUndeletedContents($ref_id)
            || $this->sync->roles()->hasLocalMemberChanges($ref_id)
        ) {
            // object is already touched by an admin => just save the info
            // has to be done here because it changes the update time which affects isObjectManuallyChanged
            $object->update();

            // save the lost connection
            $this->study->repo()->save(new LostCourse($course->getCourseId(), $object->getId()));
        }
        else {
            // object is not yet changed => object can be deleted
            // save the changes, even if object will be moved to trash
            $object->update();
            try {
                // this checks delete permission on all objects
                // so the cron job user needs the global admin role!
                ilRepUtil::deleteObjects($this->dic->repositoryTree()->getParentId($ref_id), [$ref_id]);

                // delete the parent course of a group if it is empty and not yet touched
                // member changes can't be detected for the parent course
                if (!empty($parent_course)
                    && !$this->isObjectManuallyChanged($parent_course)
                    && !$this->ilias->objects()->hasUndeletedContents($parent_ref)
                ) {
                    ilRepUtil::deleteObjects($this->dic->repositoryTree()->getParentId($parent_ref), [$parent_ref]);
                    $parent_course = null;
                }
            }
            catch (Exception $e) {
                throw $e;
            }

            // delete any remembered lost connection
            $this->study->repo()->delete(new LostCourse($course->getCourseId(), 0));
        }

        // always delete the course record, the staging record is already deleted
        // has to be done before calling findChildParallelGroups() of the parent
        $this->study->repo()->delete($course);


        // check if parent course should loose the campo connection
        if (!empty($parent_course)) {
            if (empty($this->ilias->objects()->findChildParallelGroups($parent_ref, false))) {
                // no other parallel groups are connected in the parent
                // delete the campo connection of the parent course
                $parent_course->setTitle($this->lng->txt('fau_campo_course_is_missing_prefix') . ' ' . $parent_course->getTitle());
                $parent_course->setDescription($this->lng->txt('fau_campo_course_is_missing_for_ilias_course'));
                $parent_course->setImportId(null);
                $parent_course->update();
            }
        }

        return true;
    }

    /**
     * Update the ILIAS course for a campo event and/or course (parallel group)
     * The ilias course will always work as a container for the event
     * A campo course is always provided to indicate the need for changes by dirty flags
     * The last parameter indicates that the ilias course is a campo course directly (not the parent of groups)
     */
    protected function updateIliasCourse(int $ref_id, Term $term, Event $event, Course $course, bool $is_parallel_group = true)
    {
        $object = new ilObjCourse($ref_id);
        $manually_changed = $this->isObjectManuallyChanged($object);
        
        if ($is_parallel_group) {
            if ($course->isTitleDirty() || !$manually_changed) {
                $object->setTitle($this->buildTitle($term, $event, $course));
            }
            if ($course->isDescriptionDirty() || $course->isEventDescriptionDirty() || !$manually_changed) {
                $object->setDescription($this->buildDescription($event, $course));
            }
            if ($course->isMaximumDirty() || !$manually_changed) {
                if(empty($course->getAttendeeMaximum())) {
                    $object->enableSubscriptionMembershipLimitation(false);
                    $object->setSubscriptionMaxMembers(0);
                }
                else {
                    $object->enableSubscriptionMembershipLimitation(true);
                    $object->setSubscriptionMaxMembers($course->getAttendeeMaximum());
                }
            }
            if ($course->isCancelled()) {
                $object->setDescription($this->lng->txt('fau_campo_course_is_cancelled'));
            }
        }
        else {
            if ($course->isEventTitleDirty() || !$manually_changed) {
                $object->setTitle($this->buildTitle($term, $event, null));
            }
            if ($course->isEventDescriptionDirty() || !$manually_changed) {
                $object->setDescription($this->buildDescription($event, null));
            }
        }
        
        $object->update();
        if (!$manually_changed) {
            $this->sync->repo()->resetObjectLastUpdate($object->getId());
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
        $manually_changed = $this->isObjectManuallyChanged($object);
        
        if ($course->isTitleDirty() || !$manually_changed) {
            $object->setTitle($this->buildTitle($term, $event, $course));
        }
        if ($course->isDescriptionDirty() || $course->isEventDescriptionDirty() || !$manually_changed) {
            $object->setDescription($this->buildDescription($event, $course));
        }
        if ($course->isMaximumDirty() || !$manually_changed) {
            if(empty($course->getAttendeeMaximum())) {
                $object->enableMembershipLimitation(false);
                $object->setMaxMembers(0);
            }
            else {
                $object->enableMembershipLimitation(true);
                $object->setMaxMembers($course->getAttendeeMaximum());
            }
        }
        if ($course->isCancelled()) {
            $object->setDescription($this->lng->txt('fau_campo_course_is_cancelled'));
        }

        $object->update();
        if (!$manually_changed) {
            $this->sync->repo()->resetObjectLastUpdate($object->getId());
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
                $title .= $course->getKParallelgroupId() ? ' ( ' . $this->lng->txt('fau_campo_course') . ' ' . $course->getKParallelgroupId() . ')' : '';
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
    public function isObjectManuallyChanged(ilObject $object) : bool
    {
        $created = (int) $this->tools->convert()->dbTimestampToUnix($object->getCreateDate());
        $updated = (int) $this->tools->convert()->dbTimestampToUnix($object->getLastUpdateDate());

        // give 5 min tolerance
        return $updated > $created + 300;
    }

    /**
     * Create the missing manager and author roles in a category
     * @param int[] $exclude list of excluded ref_ids
     */
    public function createMissingOrgRoles(array $exclude = [])
    {
        $roles = $this->dic->fau()->sync()->roles();
        foreach ($this->org->repo()->getAssignableOrgunitsWithRefId() as $orgunit) {
            if (in_array($orgunit->getIliasRefId(), $exclude)) {
                $this->info("EXCLUDE " . $orgunit->getIliasRefId());
                continue;
            }

            if (empty($orgunit->getNoManager()) && empty($roles->findManagerRole($orgunit->getIliasRefId()))) {
                $this->info("CREATE Manager in " . $orgunit->getIliasRefId());
                $roles->createOrgRole($orgunit->getFauorgNr(), $orgunit->getIliasRefId(), $this->settings->getManagerRoleTemplateId(), true);
            }
            if (empty($roles->findAuthorRole($orgunit->getIliasRefId()))) {
                $this->info("CREATE Author in " . $orgunit->getIliasRefId());
                $roles->createOrgRole($orgunit->getFauorgNr(), $orgunit->getIliasRefId(), $this->settings->getAuthorRoleTemplateId(), true);
            }
        }
    }

    /**
     * Check if an ilias object with lost connection can be reused instead of creating a new object
     */
    protected function getReusableRefId(Course $course) : ?int
    {
        $term = new Term($course->getTermYear(), $course->getTermTypeId());

        if (empty($lost = $this->study->repo()->getLostCourse($course->getCourseId()))) {
            // $this->info('no lost connection found');
            return null;
        }

        foreach (ilObject::_getAllReferences($lost->getIliasObjId()) as $ref_id) {
            if (ilObject::_isInTrash($ref_id)) {
                // $this->info('dont restore connections for objects in trash');
                return null;
            }
        }
        if (empty($ref_id)) {
            // $this->info('object not found at all');
            return null;
        }

        $obj_import_id = $this->study->repo()->getImportId($lost->getIliasObjId());
        if ($obj_import_id->isForCampo()
            && $obj_import_id->getTermId() != $term->getTypeId()
            && $obj_import_id->getEventId() != $course->getEventId()
            && $obj_import_id->getCourseId() != $course->getCourseId()
           ) {
            // $this->info('object has now a different campo connection');
            return null;
        }

        if (ilObject::_lookupType($lost->getIliasObjId()) == 'crs') {
            // $this->info('all checks passed for direct ilias courses');
            return $ref_id;
        }

        if (ilObject::_lookupType($lost->getIliasObjId()) != 'grp') {
            // $this->info('must be ilias course or group');
            return null;
        }

        if (empty($parent_ref_id = $this->ilias->objects()->findParentIliasCourse($ref_id))) {
            // $this->info('group must be within a course');
            return null;
        }

        $parent_obj_id = ilObject::_lookupObjId($parent_ref_id);
        $parent_import_id = $this->study->repo()->getImportId($parent_obj_id);
        if ($parent_import_id->isForCampo()
            && $parent_import_id->getTermId() != $term->getTypeId()
            && $parent_import_id->getEventId() != $course->getEventId()
        ) {
            // $this->info('parent has now a different campo connection');
            return null;
        }

        // $this->info('All checks passed');
        return $ref_id;
    }
}