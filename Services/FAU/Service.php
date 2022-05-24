<?php declare(strict_types=1);

namespace FAU;

use ILIAS\DI\Container;

class Service
{
    protected Container $dic;
    protected User\Service $userService;
    protected Tools\Service $toolsService;

    public function __construct(Container $dic)
    {
        $this->dic = $dic;
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