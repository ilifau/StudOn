<?php

namespace FAU\Cond;

use ILIAS\DI\Container;
use FAU\Study\Data\Module;
use FAU\Cond\Data\HardRestriction;
use ilLanguage;
use FAU\Cond\Data\HardExpression;

/**
 * Handling hard restrictions for students' access to lecture events
 * These restrictions are officially defined by the courses of study and provided by campo
 * They should complete prevent registration for events if not matchinf
 */
class HardRestrictions
{
    protected Container $dic;
    protected ilLanguage $lng;
    protected Service $service;
    protected Repository $repo;

    public function __construct (Container $dic)
    {
        $this->dic = $dic;
        $this->lng = $dic->language();
        $this->service = $dic->fau()->cond();
        $this->repo = $dic->fau()->cond()->repo();
    }

    public function getEventRestrictionsAsText(int $event_id) : string
    {
        $texts = [];
        foreach ($this->getModulesWithRestrictionsOfEvent($event_id) as $module) {
            $resTexts = [];
            if (!empty($restrictions = $module->getRestrictions())) {
                foreach ($restrictions as $restriction) {
                    $resTexts[] = $this->getRestrictionAsText($restriction);
                }
                $texts[] = $module->getModuleName() . ": \n"
                    . implode("; \n", $resTexts);
            }

        }
        return implode("\n\n", $texts);
    }

    /**
     * Get the modules of an event with added restrictions
     * @param int $event_id
     * @return Module[]
     */
    protected function getModulesWithRestrictionsOfEvent(int $event_id) : array
    {
        $modules = [];
        foreach ($this->dic->fau()->study()->repo()->getModulesOfEvent($event_id) as $module) {
            foreach ($this->repo->getHardRestrictionsOfModule($module->getModuleId()) as $restriction) {
                $module = $module->withRestriction($restriction);
            }
            $modules[$module->getModuleId()] = $module;
        }
        return $modules;
    }


    public function getRestrictionAsText(HardRestriction $restriction) : string
    {
        $expTexts = [];
        foreach ($restriction->getExpressions() as $expression) {
            $text = '';
            if ($expression->getNumber() != 0) {
                switch ($expression->getCompare()) {
                    case HardExpression::COMPARE_MIN:
                        $text .= $this->lng->txt('fau_rest_min') . ' ';
                        break;
                    case HardExpression::COMPARE_MAX:
                        $text .= $this->lng->txt('fau_rest_max') . ' ';
                        break;
                }
            }
            switch ($restriction->getType()) {
                case HardRestriction::TYPE_SUBJECT_SEMESTER:
                    $text .= $expression->getNumber() . '.' . $this->lng->txt('fau_rest_subject_semester');
                    break;
                case HardRestriction::TYPE_CLINICAL_SEMESTER:
                    $text .= $expression->getNumber() . '.' . $this->lng->txt('fau_rest_clinical_semester');
                    break;
                case HardRestriction::TYPE_REQUIREMENT:
                    if ($expression->getNumber() == 0) {
                        $text .= $this->lng->txt('fau_rest_0_requirement');
                    }
                    elseif ($expression->getNumber() == 1) {
                        $text .= $this->lng->txt('fau_rest_1_requirement');
                    }
                    else {
                        $text .= sprintf($this->lng->txt('fau_rest_n_requirements'), $expression->getNumber());
                    }
                    switch ($expression->getCompulsory()) {
                        case HardExpression::COMPULSORY_PF:
                            $text .= ' '. $this->lng->txt('fau_rest_pf');
                            break;
                        case HardExpression::COMPULSORY_WP:
                            $text .= ' '. $this->lng->txt('fau_rest_wp');
                            break;
                    }
            }
            $expTexts[] = $text;
        }

        $sumText = implode($this->lng->txt('fau_rest_or'), $expTexts);

        if (!empty($requirements = $restriction->getRequirements())) {
            $reqNames = [];
            foreach ($requirements as $requirement) {
                $reqNames[] = $requirement->getName();
            }
            $sumText .= ': ' . implode(', ', $reqNames);
        }

        return $sumText;
    }
}