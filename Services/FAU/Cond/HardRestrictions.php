<?php

namespace FAU\Cond;

use FAU\Study\Data\CourseOfStudy;
use ILIAS\DI\Container;
use ilLanguage;
use FAU\Study\Data\Module;
use FAU\Cond\Data\HardRestriction;
use FAU\Cond\Data\HardExpression;
use FAU\Cond\Data\HardRequirement;
use FAU\User\Data\Person;
use FAU\Study\Data\Term;
use FAU\Study\Data\ModuleCos;


/**
 * Handling hard restrictions for students' access to lecture events
 * These restrictions are officially defined by the courses of study and provided by campo
 * They should complete prevent registration for events if not matching
 */
class HardRestrictions
{
    protected Container $dic;
    protected ilLanguage $lng;
    protected Service $service;
    protected Repository $repo;

    /**
     * Message from the last check
     */
    protected string $checkMessage = '';

    /**
     * Term for which the conditions were checked
     */
    protected ?Term $checkedTerm;

    /**
     * Courses of studies of the user for which the conditions were checked
     * @var CourseOfStudy[]
     */
    protected array $checkedUserCos = [];

    /**
     * Modules that are allowed for a selection at registration
     * The restrictions are cleared in these modules
     * @var Module[]
     */
    protected array $checkedAllowedModules = [];


    /**
     * Modules that are forbidden for a selection at registration
     * The restrictions in these modules are those that are not satisfied
     * Modules without restriction in this array are not studied
     * @var Module[]
     */
    protected array $checkedForbiddenModules = [];


    /**
     * Constructor
     */
    public function __construct (Container $dic)
    {
        $this->dic = $dic;
        $this->lng = $dic->language();
        $this->service = $dic->fau()->cond();
        $this->repo = $dic->fau()->cond()->repo();
    }

    /**
     * Get all restriction texts of an event
     */
    public function getEventRestrictionTexts(int $event_id, bool $html = true) : string
    {
        $modules = $this->getModulesOfEventWithLoadedRestrictions($event_id);
        return $this->getModuleRestrictionTexts($modules, $html);
    }


    /**
     * Get the restriction texts of modules
     *
     * - If used for the display of defined restrictions in lists and on the info page of a course,
     *   then the modules should have loaded all available restrictions, but 'all_modules' should be false.
     *   This will show only the modules with defined restrictions.
     *
     * - If used for the display of a message on a registration page, that registration is not allowed,
     *   then only the modules should be included, that are not available in the user's courses of study or which have failed restrictions.
     *   The modules should include only their failed restrictions and 'all_modules' should be true.
     *
     * @param Module[] $modules     Modules with restriction data
     * @param bool $html            Get formatted html instead of plain text
     */
    public function getModuleRestrictionTexts(array $modules, bool $html = true) : string
    {
        $texts = [];
        foreach ($modules as $module) {
            $resTexts = [];

            // first show the courses of study as restrictions
            foreach ($this->dic->fau()->study()->repo()->getCoursesOfStudyForModule($module->getModuleId()) as $cos) {
                if ($html) {
                    $resTexts[] = '<li>' . $cos->getTitle() . '</li>';
                }
                else {
                    $resTexts[] =  $cos->getTitle();
                }
            }
            $resTexts = array_unique($resTexts);
            sort($resTexts);

            // show the defined restrictions
            foreach ($module->getRestrictions() as $restriction) {
                if ($html) {
                    $resTexts[] = '<li>' . $this->getRestrictionAsText($restriction) . '</li>';
                }
                else {
                    $resTexts[] = $this->getRestrictionAsText($restriction);
                }
            }

            if ($html) {
                $moduleTitle = $this->lng->txt('fau_module')
                    . ' <strong>' . $module->getModuleName() . '</strong> ('. $module->getModuleNr() . ')' .': ';
            }
            else {
                $moduleTitle = $this->lng->txt('fau_module')
                    . ' ' . $module->getModuleName() . ' ('. $module->getModuleNr() . ')' .': ';
            }

            if (!empty($resTexts)) {
                if ($html) {
                    $texts[] = '<li>' . $moduleTitle . '<ul>' . implode("\n", $resTexts) . '</ul></li>';
                }
                else {
                    $texts[] = $moduleTitle . "\n" . implode("; \n", $resTexts);
                }
            }
            else {
                if ($html) {
                    $texts[] = '<li>' . $moduleTitle . '<ul><li>' .  $this->lng->txt('fau_rest_module_in_cos') .'</li></ul></li>';
                }
                else {
                    $texts[] = $moduleTitle .  "\n" . $this->lng->txt('fau_rest_module_in_cos');
                }
            }
        }
        if (!empty($texts)) {
            // avoid doubling of the same module with same restrictions for different courses of study
            $texts = array_unique($texts);
            sort($texts);

            if ($html) {
                return '<ul>'. implode("\n", $texts) . '</ul>';
            }
            else {
                return implode("\n\n", $texts);
            }
        }
        return '';
    }


    /**
     * Get the textual explanation of a restriction
     * This will be used both for HTML and text messages of module restrictions
     * The returned string is a single line plain text
     */
    protected function getRestrictionAsText(HardRestriction $restriction) : string
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

        $sumText = implode(' '. $this->lng->txt('fau_rest_or') . ' ', $expTexts);

        if (!empty($requirements = $restriction->getRequirements())) {
            $reqNames = [];
            foreach ($requirements as $requirement) {
                $reqNames[] = $requirement->getName();
            }
            $sumText .= ' ' . $this->lng->txt('fau_rest_from') . ': ' . implode(', ', $reqNames);
        }

        return $sumText;
    }

    /**
     * Get the modules of an event with added restrictions
     * @param int $event_id
     * @return Module[] indexed by module id
     */
    protected function getModulesOfEventWithLoadedRestrictions(int $event_id) : array
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

    /**
     * Check if a user can join an ILIAS object
     */
    public function checkObject(int $obj_id, int $user_id) : bool
    {
        $this->clearCheckResult();

        $importId = $this->dic->fau()->study()->repo()->getImportId($obj_id);
        if (empty($event_id = $importId->getEventId())) {
            // manual created course / group => no check message
            return true;
        }
        if (empty($term_id = $importId->getTermId())) {
            $this->checkMessage = $this->lng->txt('fau_check_failed_term_not_valid');
            return false;
        }
        return $this->checkEvent($event_id, $user_id, $term_id);
    }

    /**
     * Check if a user can participate in an event
     */
    public function checkEvent(int $event_id, int $user_id, string $term_id) : bool
    {
        $this->clearCheckResult();

        $modules = $this->getModulesOfEventWithLoadedRestrictions($event_id);
        if (empty($modules)) {
            $this->checkMessage = $this->lng->txt('fau_check_success_no_module');
            return true;
        }

        $term = Term::fromString($term_id);
        if (!$term->isValid()) {
            $this->checkMessage = $this->lng->txt('fau_check_failed_term_not_valid');
            return false;
        }

        if (empty($person = $this->dic->fau()->user()->repo()->getPersonOfUser($user_id))) {
            $this->checkMessage = $this->lng->txt('fau_check_failed_no_studydata');
            return false;
        }

        if (empty($person->getStudiesOfTerm($term))) {
            $this->checkMessage = $this->lng->txt('fau_check_failed_no_studydata');
            return false;
        }

        // note for what the check was done
        $this->checkedTerm = $term;
        $this->checkedUserCos = $this->dic->fau()->study()->repo()->getCoursesOfStudy($person->getCourseOfStudyDbIds($term));

        // find the matching relations of event modules to the users' courses of study
        $matching = $this->dic->fau()->study()->repo()->getModuleCos(array_keys($modules), $person->getCourseOfStudyDbIds($term));
        if (empty($matching)) {
            //$this->checkMessage = $this->lng->txt('fau_check_failed_matching_modules');
            $this->checkedForbiddenModules = $modules;
            return false;
        }


        // check all modules of the event
        // - module must match the courses of study
        // - all restrictions of hte module must be satisfied

        foreach ($modules as $module) {
            $this->checkModule($module, $person, $term, $matching);
        }
        return !empty($this->checkedAllowedModules);
    }

    /**
     * Get the HTML message from the registration check
     */
    public function getCheckResultMessage() : string
    {
        if (!empty($this->checkMessage)) {
            return $this->checkMessage;
        }

        if (!empty($this->checkedAllowedModules)) {
           $this->checkMessage = $this->lng->txt('fau_check_success_with_modules');
        }
        elseif (!empty($this->checkedForbiddenModules)) {
            $this->checkMessage = $this->lng->txt('fau_check_failed_restrictions')
                . $this->getModuleRestrictionTexts($this->checkedForbiddenModules, true);
        }

        return $this->checkMessage;
    }

    /**
     * Get the term for which the check was done
     */
    public function getCheckedTermTitle(): string
    {
        return $this->checkedTerm ? $this->dic->fau()->study()->getTermText($this->checkedTerm) : '';
    }

    /**
     * @return CourseOfStudy
     */
    public function getCheckedUserCosTexts(bool $html = true) : string
    {
        if (empty($this->checkedUserCos)) {
            return '';
        }

        $list = [];
        foreach ($this->checkedUserCos as $cos)
        {
            if ($html) {
              $list[] = '<li>' . $cos->getTitle() . '</li>';
            }
            else {
              $list[] = $cos;
            }
        }
        if ($html) {
            return '<ul>' . implode("\n", $list) . '</ul>';
        }
        else {
            return implode("\n", $list);
        }
    }

    /**
     * Get the modules that are allowed for selection at registration
     * @return Module[]
     */
    public function getCheckedAllowedModules(): array
    {
        return $this->checkedAllowedModules;
    }

    /**
     * Clear the result data from a check
     */
    protected function clearCheckResult()
    {
      $this->checkMessage = '';
      $this->checkedTerm = null;
      $this->checkedUserCos = [];
      $this->checkedAllowedModules = [];
      $this->checkedForbiddenModules = [];
    }

    /**
     * Check if a module is allowed for a person in a term
     * @param ModuleCos[] $matching   Matching between the modules of an event and the subjects of the student
     */
    protected function checkModule(Module $module, Person $person, Term $term, array $matching) : bool
    {
        // prepare a clone of the module that gets the check result
        // only the failed restrictions will be added
        // this allows a display the actual failed restrictions
        $checkedModule = $module->withoutRestrictions();

        // get the relevant subjects of the student for the module
        // if no subject matches, then the module should not be allowed
        $cos_ids = [];
        foreach ($matching as $moduleCos) {
            if ($moduleCos->getModuleId() == $module->getModuleId()) {
                $cos_ids[] = $moduleCos->getCosId();
            }
        }
        $subjects = $person->getSubjectsWithCourseOfStudyDbIds($term, $cos_ids);
        if (empty($subjects)) {
            $this->checkedForbiddenModules[] = $checkedModule;
            return false;
        }

        // allow the module directly if no restrictions are defined
        if (empty($module->getRestrictions())) {
            $this->checkedAllowedModules[] = $checkedModule;
            return true;
        }

        // load the achieved requirements of the person
        // the repo query is cached
        $achievedIds = [];
        foreach ($this->dic->fau()->user()->repo()->getAchievementsOfPerson($person->getPersonId()) as $achievement) {
            $achievedIds[] = $achievement->getRequirementId();
        }

        // check the hard restrictions defined for the module
        // all restrictions must be passed, if one is failed then the module is forbidden
        $oneRestrictionFailed = false;
        foreach ($module->getRestrictions() as $restriction) {

            // check the expressions defined for the restrictions
            // in case of semester related expressions, only one subject needs to fit
            // a restriction may have more requirements related expressions,
            // these are OR-combined, i.e. only one needs to be passed
            $oneExpressionPassed = false;
            foreach ($restriction->getExpressions() as $expression) {

                switch ($restriction->getType()) {

                    case HardRestriction::TYPE_SUBJECT_SEMESTER:
                        $found = false;
                        foreach ($subjects as $subject) {
                            if ($expression->getCompare() == HardExpression::COMPARE_MIN
                                && $subject->getStudySemester() >= $expression->getNumber()) {
                                $found = true;
                            }
                            if ($expression->getCompare() == HardExpression::COMPARE_MAX
                                && $subject->getStudySemester() <= $expression->getNumber()) {
                                $found = true;
                            }
                        }
                        if ($found) {
                            $oneExpressionPassed = true;
                        }
                        break;

                    case HardRestriction::TYPE_CLINICAL_SEMESTER:
                        $found = false;
                        foreach ($subjects as $subject) {
                            if ($expression->getCompare() == HardExpression::COMPARE_MIN
                                && $subject->getClinicalSemester() >= $expression->getNumber()) {
                                $found = true;
                            }
                            if ($expression->getCompare() == HardExpression::COMPARE_MAX
                                && $subject->getClinicalSemester() <= $expression->getNumber()) {
                                $found = true;
                            }
                        }
                        if ($found) {
                            $oneExpressionPassed = true;
                        }
                        break;

                    case HardRestriction::TYPE_REQUIREMENT:
                        $found = 0;
                        foreach ($restriction->getRequirements() as $requirement) {
                            if (in_array($requirement->getId(), $achievedIds)) {

                                switch ($expression->getCompulsory()) {
                                    case HardExpression::COMPULSORY_PF:
                                        if ($requirement->getCompulsory() == HardRequirement::COMPULSORY_PF) {
                                            $found++;
                                        }
                                        break;

                                    case HardExpression::COMPULSORY_WP:
                                        if ($requirement->getCompulsory() == HardRequirement::COMPULSORY_PF
                                            || $requirement->getCompulsory() == HardRequirement::COMPULSORY_WP) {
                                            $found++;
                                        }
                                        break;

                                    default:
                                        $found++;
                                }
                            }

                            if ($expression->getCompare() == HardExpression::COMPARE_MIN
                                && $found >= $expression->getNumber()) {
                                $oneExpressionPassed = true;
                            }
                            if ($expression->getCompare() == HardExpression::COMPARE_MAX
                                && $found <= $expression->getNumber()) {
                                $oneExpressionPassed = true;
                            }
                        }
                        break;
                } // end switch restriction type
            } // end expressions loop

            // no expression is passed => restriction is failed
            if (!$oneExpressionPassed) {
                $checkedModule = $checkedModule->withRestriction($restriction);
                $oneRestrictionFailed = true;
            }
        } // end restrictions loop

        if ($oneRestrictionFailed) {
            $this->checkedForbiddenModules[] = $checkedModule;
            return false;
        }
        else {
            $this->checkedAllowedModules[] = $checkedModule;
            return true;
        }
    }
}