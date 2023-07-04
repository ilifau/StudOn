<?php

namespace FAU\Sync;

use ILIAS\DI\Container;
use FAU\Study\Data\Term;
use FAU\Staging\Data\StudOnMember;

/**
 * Synchronisation of course settings and members from StudOn to campo
 */
class SyncToCampo extends SyncBase
{
    protected Container $dic;

    /**
     * Synchronize data (called by cron job)
     * Counted items are the members
     */
    public function synchronize() : void
    {
        foreach ($this->sync->getTermsToSync(true) as $term) {
            $this->syncCourses($term);
            $this->syncMembers($term);
        }
    }

    /**
     * Update all courses in a term in the staging table
     */
    public function syncCourses(Term $term) : void
    {
        $this->info('sync StudOnCourses for Term ' . $term->toString() . '...');
        $existing = $this->staging->repo()->getStudOnCourses($term);

        foreach ($this->sync->repo()->getCoursesToSyncBack($term) as $course) {
            if (!isset($existing[$course->key()])) {
                $this->staging->repo()->save($course);
            }
            elseif ($existing[$course->key()]->hash() != $course->hash()) {
                $this->staging->repo()->save($course);
            }
            // record is still needed
            unset($existing[$course->key()]);
        }

        // delete existing records that are no longer needed
        foreach ($existing as $course) {
            $this->staging->repo()->delete($course);
        }
    }


    /**
     * Update all members of courses in a term in the staging table
     */
    public function syncMembers(Term $term) : void
    {
        $this->info('sync StudOnMembers for Term ' . $term->toString() . '...');
        // get the members noted in the staging database
        $existing = $this->staging->repo()->getStudOnMembers($term);
        // get the courses in the tern that have an ilias object assigned
        $course_ids = $this->sync->repo()->getCourseIdsToSyncBack($term);
        // get the timestamps of the maximum individual dates of all courses indexed by course ids
        $times = $this->sync->repo()->getCoursesMaxDatesAsTimestamps();
        // earlies maximum date of courses for which a passed status should be sent
        $earliest_passed = $this->dic->fau()->tools()->convert()->dbDateToUnix(
            $this->tools->convert()->unixToDbDate(time() - 86400)
        );
        // fallback end date for courses without a planned or individual end date
        $term_end = $this->study->getTermEndTime($term);
        
        foreach ($this->sync->repo()->getMembersOfCoursesToSyncBack($term) as $member) {
            $end_time = $times[$member->getCourseId()] ?? $term_end;
            if ($end_time > $earliest_passed) {
                $member = $member->withStatus(StudOnMember::STATUS_REGISTERED);
            }
            
            if (!isset($existing[$member->key()])) {
                $this->staging->repo()->save($member);
                $this->increaseItemsAdded();
            }
            elseif ($existing[$member->key()]->hash() != $member->hash()) {
                $this->staging->repo()->save($member);
                $this->increaseItemsUpdated();
            }
            // existing member in campo is still assigned in studon
            unset($existing[$member->key()]);
        }

        // delete remaining existing members in campo that are no longer assigned in studon
        // don't delete those of older courses where the studon object is deleted or connected with another course
        foreach ($existing as $member) {
            if (in_array($member->getCourseId(), $course_ids)) {
                $this->staging->repo()->delete($member);
                $this->increaseItemsDeleted();
            }
        }
    }


    /**
     * Update all members of an ilias object in the staging table
     */
    public function syncMembersOfObject(int $obj_id) : void
    {
        foreach ($this->dic->fau()->study()->repo()->getCoursesByIliasObjId($obj_id) as $course) {
            $course_id = $course->getCourseId();
        }
        if (empty($course_id)) {
            return;
        }

        $this->info('sync StudOnMembers Of Object...');
        // get the members noted in the staging database
        $existing = $this->staging->repo()->getStudOnMembersOfCourse($course_id);

        foreach ($this->sync->repo()->getMembersOfIliasObjectToSyncBack($obj_id) as $member) {
            if (!isset($existing[$member->key()])) {
                $this->staging->repo()->save($member);
                $this->increaseItemsAdded();
            }
            elseif ($existing[$member->key()]->hash() != $member->hash()) {
                $this->staging->repo()->save($member);
                $this->increaseItemsUpdated();
            }
            // existing member in campo is still assigned in studon
            unset($existing[$member->key()]);
        }

        // delete remaining existing members in campo that are no longer assigned in studon
        foreach ($existing as $member) {
            $this->staging->repo()->delete($member);
            $this->increaseItemsDeleted();
        }
    }

}

