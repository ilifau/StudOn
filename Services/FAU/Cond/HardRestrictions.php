<?php

namespace FAU\Cond;

use ILIAS\DI\Container;

/**
 * Handling hard restrictions for students' access to lecture events
 * These restrictions are officially defined by the courses of study and provided by campo
 * They should complete prevent registration for events if not matchinf
 */
class HardRestrictions
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