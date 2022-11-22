<?php

namespace FAU\Sync;

use ILIAS\DI\Container;
use FAU\Study\Data\Term;

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
        foreach ($this->sync->getTermsToSync() as $term) {
            $this->syncCourses($term);
            $this->syncMembers($term);
        }
    }

    /**
     * Update all courses in a term in the staging table
     */
    public function syncCourses(Term $term) : void
    {
        $this->info('syncStudOnCourses...');
        $existing = $this->staging->repo()->getStudOnCourses($term);

        foreach ($this->sync->repo()->getCoursesToSync($term) as $course) {
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
        $this->info('syncStudOnMembers...');
        $existing = $this->staging->repo()->getStudOnMembers($term);

        foreach ($this->sync->repo()->getMembersOfCoursesToSync($term) as $member) {
            if (!isset($existing[$member->key()])) {
                $this->staging->repo()->save($member);
                $this->increaseItemsAdded();
            }
            elseif ($existing[$member->key()]->hash() != $member->hash()) {
                $this->staging->repo()->save($member);
                $this->increaseItemsUpdated();
            }
            // record is still needed
            unset($existing[$member->key()]);
        }

        // delete existing records that are no longer needed
        foreach ($existing as $member) {
            $this->staging->repo()->delete($member);
            $this->increaseItemsDeleted();
        }
    }

}

