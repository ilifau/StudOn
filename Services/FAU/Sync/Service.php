<?php declare(strict_types=1);

namespace FAU\Sync;

use ILIAS\DI\Container;

/**
 * Service for synchronizing data between staging database and studon
 */
class Service
{
    protected Container $dic;
    protected Repository $repository;

    /**
     * Constructor
     */
    public function __construct(Container $dic)
    {
        $this->dic = $dic;
    }

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