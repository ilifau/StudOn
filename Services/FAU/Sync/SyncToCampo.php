<?php

namespace FAU\Sync;

use ILIAS\DI\Container;
use FAU\Study\Data\Term;

/**
 * Synchronisation of course settings and members to campo
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
            $this->syncMembers($term);
        }

    }


    /**
     * Update all members of courses in a term in the staging table
     */
    public function syncMembers(Term $term) : void
    {
        $this->info('syncStudOnMembers...');
        $existing = $this->staging->repo()->getStudOnMembers($term);

        foreach ($this->sync->repo()->getMembersOfCourses($term) as $member) {
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

