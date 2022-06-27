<?php

namespace FAU\Sync;

use ILIAS\DI\Container;
use FAU\Study\Data\Term;
use FAU\Org\Data\Orgunit;
use FAU\Study\Data\Course;

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
     * Get the terms for which the courses should be synced
     *  @todo: configure terms
     * @return Term[]
     */
    protected function getTermsToSync() : array
    {
        return [
            new Term(2022, 2)
        ];
    }


    /**
     * Create the courses of a term
     * @return int number of created courses
     */
    public function createCourses(Term $term) : int
    {
        foreach ($this->study->repo()->getCoursesByTermToCreate($term) as $course) {
            $this->info('CREATE' . $course->getTitle() . '...');

            if ($this->study->repo()->countCoursesOfEventInTerm($course->getEventId(), $term) > 0) {
                // todo: treat multiple parallel groups
                // check if existing objects are ILIAS courses or groups
                // if groups: create another group in the same course
                // if courses: create another course
                // in both cases: create a membership limitation or add the object
            }

            $creationUnit = null;
            foreach ($this->study->repo()->getEventOrgunitsByEventId($course->getEventId()) as $event_orgunit) {
                if (empty($responsibleUnit = $this->org->repo()->getOrgunitByNumber($event_orgunit->getFauorgNr()))) {
                    $this->study->repo()->save($course->withIliasProblem(
                        'Responsible Org Unit ' . $event_orgunit->getFauorgNr() . ' not fond!'));
                    continue;
                }

                if (empty($creationUnit = $this->findOrgUnitForCourseCreation($responsibleUnit))) {
                    $this->org->repo()->save($responsibleUnit->withProblem('No ILIAS category found for course creation'));
                }
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
}