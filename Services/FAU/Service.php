<?php declare(strict_types=1);

namespace FAU;

use ILIAS\DI\Container;

/**
 * Main Service for FAU integration
 * This works as a factory for the sub services (Study, User ...)
 */
class Service
{
    protected Container $dic;
    protected Cond\Service $condService;
    protected Org\Service $orgService;
    protected Staging\Service $stagingService;
    protected Study\Service $studyService;
    protected Sync\Service $syncService;
    protected Tools\Service $toolsService;
    protected User\Service $userService;

    public function __construct(Container $dic)
    {
        $this->dic = $dic;
    }

    /**
     * Get the service for registration conditions
     */
    public function cond() : Cond\Service
    {
        if (!isset($this->condService)) {
            $this->condService = new Cond\Service($this->dic);
        }
        return $this->condService;
    }


    /**
     * Get the service for organisational data
     */
    public function org() : Org\Service
    {
        if (!isset($this->orgService)) {
            $this->orgService = new Org\Service($this->dic);
        }
        return $this->orgService;
    }

    /**
     * Get the service for event and course related data
     */
    public function study() : Study\Service
    {
        if (!isset($this->studyService)) {
            $this->studyService = new Study\Service($this->dic);
        }
        return $this->studyService;
    }

    /**
     * Get the service for user related data
     */
    public function user() : User\Service
    {
        if (!isset($this->userService)) {
            $this->userService = new User\Service($this->dic);
        }
        return $this->userService;
    }

    /**
     * Get the service for staging data
     */
    public function staging() : Staging\Service
    {
        if (!isset($this->stagingService)) {
            $this->stagingService = new Staging\Service($this->dic);
        }
        return $this->stagingService;
    }

    /**
     * Get the service for synchronization of data
     */
    public function sync() : Sync\Service
    {
        if (!isset($this->syncService)) {
            $this->syncService = new Sync\Service($this->dic);
        }
        return $this->syncService;
    }

    /**
     * Get the service for tools
     */
    public function tools() : Tools\Service
    {
        if (!isset($this->toolsService)) {
            $this->toolsService = new Tools\Service($this->dic);
        }
        return $this->toolsService;
    }
}