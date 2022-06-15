<?php

namespace FAU\Cond;

use ILIAS\DI\Container;

/**
 * Handling soft conditions for students' access to StudOn courses and groups
 * These conditions are defined by the course or group admins
 * They prevent a direct registration but allow registration requests if not matching
 */
class SoftConditions
{
    protected Container $dic;
    protected Service $service;
    protected Repository $repo;

    public function __construct (Container $dic)
    {
        $this->dic = $dic;
        $this->service = $dic->fau()->cond();
        $this->repo = $dic->fau()->cond()->repo();
    }
}