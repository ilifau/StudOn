<?php

namespace FAU\Sync;

use ILIAS\DI\Container;
use FAU\Study\Data\Term;
use FAU\Study\Data\Course;
use FAU\Study\Data\Event;
use ilObject;
use ilObjCourse;
use FAU\Study\Data\ImportId;
use ilObjGroup;
use ilUtil;

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

    protected int $owner_id;
    protected int $didactic_template_id;

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
        $this->didactic_template_id = $this->settings->getGroupDidacticTemplateId();
        if (empty($this->didactic_template_id)) {
            $this->addError('Missing didactic template id for the creation of groups!');
            return false;
        }
        $template = new \ilDidacticTemplateSetting( $this->didactic_template_id);
        if (!isset($template) || !$template->isEnabled()) {
            $this->addError('Didactic template ' . $this->didactic_template_id . " not found or not enabled!");
            return false;
        }
        return true;
    }


    /**
     * Synchronize the campo courses for selected terms
     */
    public function synchronize() : void
    {
        if ($this->init()) {
            foreach ($this->sync->getTermsToSync() as $term) {
                $this->info('SYNC term ' . $term->toString() . '...');
                $this->increaseItemsAdded($this->createCourses($term));
                $this->increaseItemsUpdated($this->updateCourses($term));
            }
        }
    }

    /**
     * Create the ilias objects for courses (parallel groups) of a term
     * @return int number of created courses
     */
    public function createCourses(Term $term, ?array $course_ids = null) : int
    {
        if (!$this->init()) {
            return 0;
        }
        elseif (isset($course_ids)) {
            $courses = $this->study->repo()->getCoursesByIds($course_ids);
        }
        else {
            $courses = $this->study->repo()->getCoursesByTermToCreate($term);
        }

        $created = 0;
        foreach ($courses as $course) {
            $this->info('CREATE ' . $course->getTitle() . '...');

            if (!empty($course->getIliasObjId())) {
                $this->info('Already created.');
                continue;
            }
            if (empty($event = $this->study->repo()->getEvent($course->getEventId()))) {
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
                    if (!empty($other_ref_id = $this->sync->trees()->getIliasRefIdForCourse($other))) {
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
    public function updateCourses(Term $term, ?array $course_ids = null) : int
    {
        if (!$this->init()) {
            return 0;
        }
        elseif (isset($course_ids)) {
            $courses = $this->study->repo()->getCoursesByIds($course_ids);
        }
        else {
            $courses = $this->study->repo()->getCoursesByTermToUpdate($term);
        }

        $updated = 0;
        foreach ($courses as $course) {
            $this->info('UPDATE ' . $course->getTitle() . '...');

            $event = $this->study->repo()->getEvent($course->getEventId());

            // get the reference to the ilias course or group
            if (empty($ref_id = $this->sync->trees()->getIliasRefIdForCourse($course))) {
                $this->study->repo()->save($course->withIliasProblem("ILIAS object does not exist or is deleted!"));
                continue;
            }

            switch (ilObject::_lookupType($course->getIliasObjId())) {
                case 'crs':
                    // ilias course is used for campo event and course
                    $this->updateIliasCourse($ref_id, $term, $event, $course);
                    $this->sync->roles()->updateParticipants($course->getCourseId(), $ref_id, $ref_id);
                    break;

                case 'grp':
                    // ilias course is used for the campo event
                    if ($parent_ref = $this->sync->trees()->findParentIliasCourse($ref_id)) {
                        $this->updateIliasCourse($parent_ref, $term, $event, null);
                    }
                    else {
                        $this->study->repo()->save($course->withIliasProblem("Parent ILIAS course of group not found!"));
                        continue 2;
                    }

                    // ilias group is used for the campo course
                    $this->updateIliasGroup($ref_id, $term, $event, $course);
                    $this->sync->roles()->updateParticipants($course->getCourseId(), $parent_ref, $ref_id);
                    break;

                default:
                    $this->study->repo()->save($course->withIliasProblem("ILIAS object for course is neither a course nor a group!"));
                    continue 2;
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
        $object->setOwner($this->owner_id);
        $object->create();
        $object->createReference();
        $object->putInTree($parent_ref_id);
        $object->setPermissions($parent_ref_id);
        $object->applyDidacticTemplate($this->didactic_template_id);
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
            $object->setRegistrationType(GRP_REGISTRATION_DEACTIVATED);
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