<?php declare(strict_types=1);

namespace FAU;

use ILIAS\DI\Container;

class Service
{
    protected Container $dic;
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
     * Get the Service for FUA org data
     */
    public function org() : Org\Service
    {
        if (!isset($this->orgService)) {
            $this->orgService = new Org\Service($this->dic);
        }
        return $this->orgService;
    }

    /**
     * Get the Service for Campo data
     */
    public function study() : Study\Service
    {
        if (!isset($this->studyService)) {
            $this->studyService = new Study\Service($this->dic);
        }
        return $this->studyService;
    }

    /**
     * Get the Service for User data
     */
    public function user() : User\Service
    {
        if (!isset($this->userService)) {
            $this->userService = new User\Service($this->dic);
        }
        return $this->userService;
    }

    /**
     * Get the Service for Staging data
     */
    public function staging() : Staging\Service
    {
        if (!isset($this->stagingService)) {
            $this->stagingService = new Staging\Service($this->dic);
        }
        return $this->stagingService;
    }

    /**
     * Get the Service for Synchronization of data
     */
    public function sync() : Sync\Service
    {
        if (!isset($this->syncService)) {
            $this->syncService = new Sync\Service($this->dic);
        }
        return $this->syncService;
    }

    /**
     * Get the Service for Tools
     */
    public function tools() : Tools\Service
    {
        if (!isset($this->toolsService)) {
            $this->toolsService = new Tools\Service($this->dic);
        }
        return $this->toolsService;
    }

}