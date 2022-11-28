<?php declare(strict_types=1);

namespace FAU\Sync;

use FAU\SubService;
use FAU\Study\Data\Term;

/**
 * Service for synchronizing data between staging database and studon
 */
class Service extends SubService
{
    protected Repository $repository;
    protected TreeMatching $trees;
    protected RoleMatching $roles;


    // Synchronisation Workers

    public function campo() : SyncWithCampo
    {
        return new SyncWithCampo($this->dic);
    }

    public function org() : SyncWithOrg
    {
        return new SyncWithOrg($this->dic);
    }

    public function idm() : SyncWithIdm
    {
        return new SyncWithIdm($this->dic);
    }

    public function ilias() : SyncWithIlias
    {
        return new SyncWithIlias($this->dic);
    }

    public function toCampo() : SyncToCampo
    {
        return new SyncToCampo($this->dic);
    }

    // Service and helper classes

    public function repo() : Repository
    {
        if(!isset($this->repository)) {
            $this->repository = new Repository($this->dic->database(), $this->dic->logger()->fau());
        }
        return $this->repository;
    }

    public function roles() : RoleMatching
    {
        if (!isset($this->roles)) {
            $this->roles = new RoleMatching($this->dic);
        }
        return $this->roles;
    }

    public function trees() : TreeMatching
    {
        if (!isset($this->trees)) {
            $this->trees = new TreeMatching($this->dic);
        }
        return $this->trees;
    }



    /**
     * Get the terms for which the courses should be created or updated
     *
     * Always sync the current semester (Winter from 1st of October, Summer from 1st of April)
     * End synchronisation of the previous semester at 1st of June and 1st of December (2 months after end)
     * Start synchronisation for the next semester at 1st of June and 1st of December (4 months in advance)
     * @return Term[]
     */
    public function getTermsToSync() : array
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
                new Term($year - 1, 2),     // last winter term
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
                new Term($year, 1),             // last summer term
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
}