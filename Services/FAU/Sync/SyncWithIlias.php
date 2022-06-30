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
            $this->increaseItemsAdded($this->createCourses($term));
            $this->increaseItemsUpdated($this->updateCourses($term));
        }
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
    public function createCourses(Term $term) : int
    {
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
                foreach ($this->study->repo()->getCoursesOfEvent($event->getEventId()) as $other) {
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

            // get or create the place for a new course
            if (empty($parent_ref = $parent_ref ?? $this->findOrCreateCourseCategory($course, $term))) {
                continue;
            }

            // create the object(s)
            switch ($action) {
                case 'create_single_course':
                    $ref_id = $this->createIliasCourse($parent_ref, $event, $course, $term);
                    $this->updateIliasCourse($ref_id, $event, $course);
                    break;

                case 'create_course_and_group':
                    $course_ref = $this->createIliasCourse($parent_ref, $event, null, $term);
                    $this->updateIliasCourse($course_ref, $event, null);

                    $ref_id = $this->createIliasGroup($course_ref, $event, $course, $term);
                    $this->updateIliasGroup($ref_id, $course);
                    break;

                case 'create_group_in_course':
                    $ref_id = $this->createIliasGroup($parent_ref, $event, $course, $term);
                    $this->updateIliasGroup($ref_id, $course);
                    break;
            }

            // create or update the membership limitation
            if (!empty($other_refs)) {
                // todo: update membership limitation
            }
        }

        return 0;
    }


    /**
     * Update the courses of a term
     * This should also treat the event related courses
     * @return int number of updated courses
     */
    public function updateCourses(Term $term) : int
    {
        return 0;
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
     * Create the category hat should get new courses of a term
     */
    protected function createCourseCategory(int $parent_ref_id, Term $term): int
    {
        $lng = $this->dic->language();

        $category = new ilObjCategory();
        $category->setTitle($lng->txtlng('fau', 'fau_campo_courses', 'de')
            . ': ' . $this->study->getTermTextForLang($term, 'de'));
        $category->setDescription($lng->txtlng('fau', 'fau_campo_courses_desc', 'de'));
        $category->setImportId(ImportId::fromObjects(null, null, $term)->toString());
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
    protected function createIliasCourse(int $parent_ref_id, Event $event, ?Course $course, ?Term $term): int
    {
        $object = new IlObjCourse();
        if (isset($course)) {
            $object->setTitle($course->getTitle());
            $object->setSyllabus($course->getContents());
            $object->setImportantInformation($course->getCompulsoryRequirement());
        }
        else {
            $object->setTitle($event->getTitle());
            $object->setImportantInformation($event->getComment());
        }
        $object->setDescription($event->getEventtype() . ($event->getShorttext() ? ' (' . $event->getShorttext() . ')' : ''));
        $object->setImportId(ImportId::fromObjects($event, $course, $term)->toString());
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
    protected function createIliasGroup(int $parent_ref_id, Event $event, Course $course, ?Term $term): int
    {
        $object = new ilObjGroup();
        $object->setInformation(
            ilUtil::secureString($course->getContents()) . "\n"
            . ilUtil::secureString($course->getCompulsoryRequirement()));
        $object->setImportId(ImportId::fromObjects($event, $course, $term)->toString());
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
     */
    protected function updateIliasCourse(int $ref_id, Event $event, ?Course $course)
    {
        // todo: update course data if last_update does not differ from create_date
        // todo: update the course admins
    }

    /**
     * Update an ILIAS group for a campo course (parallel group)
     */
    protected function updateIliasGroup(int $ref_id, Course $course)
    {
        // todo: update group data if last_update does not differ from create_date
        // todo: update the group admins
        // todo: update the course tutors in upper course
    }
}