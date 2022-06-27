<?php

namespace FAU\Study;

use ILIAS\DI\Container;
use FAU\Study\Data\ModuleCos;

/**
 * Calculating matchings between study data of students and events
 */
class Matching
{
    protected Container $dic;
    protected Service $service;
    protected Repository $repo;

    /**
     * Constructor
     */
    public function __construct(Container $dic)
    {
        $this->dic = $dic;
        $this->service = $dic->fau()->study();
        $this->repo = $dic->fau()->study()->repo();
    }


    /**
     * Get the option data to select module for an event
     *
     * @return array [ Module[], CourseOfStudy[], ModuleCos[] ]
     */
    public function getModuleSelectOptionsForEvent(int $event_id, array $cos_ids = []) : array
    {
        /** @var ModuleCos[] $matching */
        $matching = [];

        $module_ids = [];
        foreach($this->repo->getModuleEvent([$event_id]) as $moduleEvent) {
           $module_ids[$moduleEvent->getModuleId()] = true;
        }

        $matched_cos_ids = [];
        foreach($this->repo->getModuleCos($cos_ids) as $moduleCos) {
            if (isset($module_ids[$moduleCos->getModuleId()])) {
                $matched_cos_ids[$moduleCos->getCosId()]  = true;
                $matching[] = $moduleCos;
            }
        }

        return [
            $this->repo->getModules(array_keys($module_ids)),
            $this->repo->getCoursesOfStudy(array_keys($matched_cos_ids)),
            $matching
        ];
    }
}