<?php

namespace FAU\Sync;

use ILIAS\DI\Container;
use FAU\Study\Data\Term;
use FAU\Staging\Data\StudOnMember;
use FAU\Study\Data\Course;

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
        // get the sending setting of courses in the term (course_id => send_passed)
        $sending = $this->sync->repo()->getCourseSendPassedToSyncBack($term);
        // get the module ids of modules for which a 'passed' status of members should be sent to campo
        $passing_module_ids = $this->sync->repo()->getModuleIdsToSendPassed();
        
        foreach ($this->sync->repo()->getMembersOfCoursesToSyncBack($term) as $member) {
            
            if ($member->getStatus() == StudOnMember::STATUS_PASSED) {
                
                // don't send a 'passed' status if neither the module nor the course allows it
                if (!in_array($member->getModuleId(), $passing_module_ids)
                    && (!isset($sending[$member->getCourseId()]) || $sending[$member->getCourseId()] != Course::SEND_PASSED_LP)) {
                    
                    $member = $member->withStatus(StudOnMember::STATUS_REGISTERED);
                }
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
            if (isset($sending[$member->getCourseId()])) {
                $this->staging->repo()->delete($member);
                $this->increaseItemsDeleted();
            }
        }
    }
}

