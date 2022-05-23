<?php declare(strict_types=1);

namespace FAU;

use ILIAS\DI\Container;

class Service
{
    protected Container $dic;
    protected User\Service $userService;


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


}