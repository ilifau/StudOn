<?php declare(strict_types=1);

namespace FAU\Sync;

use ILIAS\DI\Container;
use FAU\SubService;

/**
 * Service for synchronizing data between staging database and studon
 */
class Service extends SubService
{
    protected Repository $repository;


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

    /**
     * Get the repository for user data
     */
    public function repo() : Repository
    {
        if(!isset($this->repository)) {
            $this->repository = new Repository($this->dic->database(), $this->dic->logger()->fau());
        }
        return $this->repository;
    }

}