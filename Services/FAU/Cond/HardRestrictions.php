<?php

namespace FAU\Cond;

use FAU\Cond\Data\Restriction;
use FAU\Study\Data\CourseOfStudy;
use FAU\Study\Data\Event;
use FAU\User\Data\Achievement;
use FAU\User\Data\Subject;
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
     * Message to the student from the last check
     */
    protected string $checkMessage = '';

    /**
     * Result info text for course admins from the last check
     */
    protected string $checkInfo = '';


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
     * Modules that fit the user's courses of study
     * These may have satisfied or unsatisfied restrictions
     * @var Module[]
     */
    protected $checkedFittingModules = [];

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
     * Event with restrictions that are not satisfied
     * @var ?Event
     */
    protected $checkedForbiddenEvent = null;


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
        $event = $this->getEventWithLoadedRestrictions($event_id);
        $modules = $this->getModulesOfEventWithLoadedRestrictions($event_id);
        return $this->getRestrictionTexts($event, $modules, $html);
    }


    /**
     * Get the restriction texts of an event and its modules
     *
     * - If used for the display of defined restrictions in lists and on the info page of a course
     *   then the event and modules should have loaded all available restrictions.
     *
     * - If used for the display of a message on a registration page, that registration is not allowed,
     *   then the event and modules should include only their failed restrictions.
     *
     * @param ?Event $event         Event with restriction data
     * @param Module[] $modules     Modules with restriction data
     * @param bool $html            Get formatted html instead of plain text
     */
    protected function getRestrictionTexts(?Event $event, array $modules, bool $html = true) : string
    {
        $texts = [];
        foreach ($modules as $module) {
            $resTexts = [];

            // first show the module's courses of study as restrictions
            $studyTexts = [];
            foreach ($this->dic->fau()->study()->repo()->getCoursesOfStudyForModule($module->getModuleId()) as $cos) {
                $studyTexts[] =  $cos->getTitle();
            }
            if (!empty($studyTexts)){
                $studyTexts = array_unique($studyTexts);
                sort($studyTexts);
                $label = $this->formatLabel( $this->lng->txt(
                    count($studyTexts) == 1 ? 'studydata_cos' : 'fau_rest_one_of_studies'), '', '', $html);
                $resTexts[] = $label . $this->formatList($studyTexts, $html);
            }


            // then show the module's restrictions
            foreach ($module->getRestrictions() as $restriction) {
                $resTexts[] = $this->getRestrictionAsText($restriction);
            }

            // put all module information together
            $label = $this->formatLabel($this->lng->txt('fau_module'),  $module->getModuleName(), $module->getModuleNr(), $html);
            if (!empty($resTexts)) {
                $texts[] = $label . $this->formatList($resTexts, $html);
            }
            else {
                $texts[] = $label . $this->formatList([$this->lng->txt('fau_rest_module_in_cos')], $html);
            }
        }

        // avoid a double showing of the same module with same restrictions for different courses of study
        $texts = array_unique($texts);
        sort($texts);

        // add the restrictions of the event before the module restrictions
        $resTexts = [];
        if (!empty($event) && !empty($event->getRestrictions()))
        {
            foreach ($event->getRestrictions() as $restriction) {
                $resText = $this->getRestrictionAsText($restriction);
                if (!empty($restriction->getRegardingCosIds())) {
                    $cosTexts = [];
                    foreach ($this->dic->fau()->study()->repo()->getCoursesOfStudy($restriction->getRegardingCosIds()) as $cos) {
                        $cosTexts[] = $cos->getTitle();
                    }
                    $resTexts[] = $this->getRestrictionAsText($restriction) . ' - ' . $this->lng->txt('fau_rest_regarding_cos')
                        . $this->formatList(array_unique($cosTexts), $html);
                }
                elseif (!empty($restriction->getExceptionCosIds())) {
                    $cosTexts = [];
                    foreach ($this->dic->fau()->study()->repo()->getCoursesOfStudy($restriction->getExceptionCosIds()) as $cos) {
                        $cosTexts[] = $cos->getTitle();
                    }
                    $resTexts[] = $this->getRestrictionAsText($restriction) . ' - ' . $this->lng->txt('fau_rest_exception_cos')
                        . $this->formatList(array_unique($cosTexts), $html);
                }
                else {
                    $resTexts[] = $this->getRestrictionAsText($restriction);
                }
            }
            $label = $this->formatLabel($this->lng->txt('fau_campo_event'), '', '', $html);
            $texts = array_merge([$label . $this->formatList($resTexts, $html)], $texts);
        }

        return $this->formatList($texts, $html, true);
    }


    /**
     * Get the textual explanation of a restriction
     * This will be used both for HTML and text messages of module restrictions
     * The returned string is a single line plain text
     */
    protected function getRestrictionAsText(HardRestriction $restriction) : string
    {
        $reqNames = [];
        foreach ($restriction->getRequirements() as $requirement) {
            if ($requirement->getId() != 0) {
                $reqNames[] = $requirement->getName();
            }
        }

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
                    $text .= $expression->getNumber() . '. ' . $this->lng->txt('fau_rest_subject_semester');
                    break;
                case HardRestriction::TYPE_CLINICAL_SEMESTER:
                    $text .= $expression->getNumber() . '. ' . $this->lng->txt('fau_rest_clinical_semester');
                    break;
                case HardRestriction::TYPE_REQUIREMENT:

                    if ($expression->getNumber() == count($reqNames) && $expression->getCompare() == HardExpression::COMPARE_MIN) {
                        // better formulate "min" condition, if all are needed
                        if ($expression->getNumber() == 1) {
                            $text = $this->lng->txt('fau_rest_requirement');
                        }
                        else {
                            $text = sprintf($this->lng->txt('fau_rest_n_requirements'), $expression->getNumber());
                        }
                    }
                    else if ($expression->getNumber() == 0) {
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
                            $text .= ' '. $this->lng->txt('fau_rest_pf_wp');
                            break;
                    }
            }
            $expTexts[] = $text;
        }

        $sumText = implode(' '. $this->lng->txt('fau_rest_or') . ' ', $expTexts);

        if (!empty($reqNames)) {
            $sumText .= ': ' . implode(', ', $reqNames);
        }

        return $sumText;
    }

    /**
     * Get the event object with added restrictions
     * @param int $event_id
     * @return ?Event
     */
    protected function getEventWithLoadedRestrictions(int $event_id) : ?Event
    {
        $event = $this->dic->fau()->study()->repo()->getEvent($event_id);
        if (!empty($event)) {
            foreach ($this->repo->getHardRestrictionsOfEvent($event->getEventId()) as $restriction) {
                $event = $event->withRestriction($restriction);
            }
        }
        return $event;
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
     * Check if restrictions are fefined for an object
     */
    public function hasRestrictions($obj_id) : bool
    {
        $importId = $this->dic->fau()->study()->repo()->getImportId($obj_id);
        if (empty($event_id = $importId->getEventId())) {
            return false;
        }

        $event = $this->getEventWithLoadedRestrictions($event_id);
        if (!empty($event) && !empty($event->getRestrictions())) {
            return true;
        }

       foreach ($this->getModulesOfEventWithLoadedRestrictions($event_id) as $module) {
           if (!empty($module->getRestrictions())) {
               return true;
           }
       }

       return false;
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

        $event = $this->getEventWithLoadedRestrictions($event_id);
        if (empty($event)) {
            $this->checkMessage = $this->lng->txt('fau_check_success_no_event');
            $this->checkInfo = $this->lng->txt('fau_check_info_passed');
            return true;
        }

        $modules = $this->getModulesOfEventWithLoadedRestrictions($event_id);
        if (empty($modules) && empty($event->getRestrictions())) {
            $this->checkMessage = $this->lng->txt('fau_check_success_no_restriction');
            $this->checkInfo = $this->lng->txt('fau_check_info_passed');
            return true;
        }

        $term = Term::fromString($importId->getTermId());
        if (!$term->isValid()) {
            $this->checkMessage = $this->lng->txt('fau_check_failed_term_not_valid');
            $this->checkInfo = $this->lng->txt('fau_check_info_failed_term_not_valid');
            return false;
        }

        if (empty($person = $this->dic->fau()->user()->repo()->getPersonOfUser($user_id))) {
            $this->checkMessage = $this->lng->txt('fau_check_failed_no_studydata');
            $this->checkInfo = $this->lng->txt('fau_check_info_failed_no_studydata');
            return false;
        }

        if (empty($person->getStudiesOfTerm($term))) {
            $this->checkMessage = $this->lng->txt('fau_check_failed_no_studydata');
            $this->checkInfo = $this->lng->txt('fau_check_info_failed_no_studydata');
            return false;
        }

        // note for what the check was done
        $this->checkedTerm = $term;
        $this->checkedUserCos = $this->dic->fau()->study()->repo()->getCoursesOfStudy($person->getCourseOfStudyDbIds($term));

        // check the restrictions of the event
        // - all restrictions of the event must be satisfied
        $this->checkEvent($event, $person, $term);

        // check all modules of the event
        // - at least one module must match the courses of study
        // - all restrictions of that module must be satisfied
        foreach ($modules as $module) {
            $this->checkModule($module, $person, $term);
        }

        return empty($this->checkedForbiddenEvent) && !empty($this->checkedAllowedModules);
    }

    /**
     * Get the HTML message from the registration check
     */
    public function getCheckResultMessage() : string
    {
        if (!empty($this->checkMessage)) {
            return $this->checkMessage;
        }

        if (empty($this->checkedForbiddenEvent) && (empty($this->checkedForbiddenModules) || !empty($this->checkedAllowedModules))) {
           $this->checkMessage = $this->lng->txt('fau_check_passed_restrictions');
        }
        else {
            $this->checkMessage = $this->lng->txt('fau_check_failed_restrictions')
                . $this->getRestrictionTexts($this->checkedForbiddenEvent, $this->checkedForbiddenModules, true);
        }

        return $this->checkMessage;
    }

    /**
     * Get the info text from the registration check
     * Used on the waiting list and for the member export of courses
     */
    public function getCheckResultInfo(bool $html = false, ?int $selected_module_id = null) : string
    {
        if (!empty($this->checkInfo)) {
            return $this->checkInfo;
        }

        if (empty($this->checkedForbiddenEvent) && (empty($this->checkedForbiddenModules) || !empty($this->checkedAllowedModules))) {
            return $this->lng->txt('fau_check_info_passed_restrictions');
        }

        // limit the display of failed restrictions if a module is selected, e.g. for display in the waiting list
        if (isset($selected_module_id)) {
            if (isset($this->checkedAllowedModules[$selected_module_id])) {
                // selected module is allowed => show only restrictions for the event
                return $this->lng->txt('fau_check_info_failed_restrictions')
                    . $this->getRestrictionTexts($this->checkedForbiddenEvent, [], $html);
            }
            elseif (isset($this->checkedForbiddenModules[$selected_module_id])) {
                // selected module is forbidden => show only the restrictions for the event and this module
                return $this->lng->txt('fau_check_info_failed_restrictions')
                    . $this->getRestrictionTexts($this->checkedForbiddenEvent, [$this->checkedForbiddenModules[$selected_module_id]], $html);
            }
        }

        // no module selected or selected module not for the event => show event and all module restrictions
        return $this->lng->txt('fau_check_info_failed_restrictions')
            . $this->getRestrictionTexts($this->checkedForbiddenEvent, $this->checkedForbiddenModules, $html);
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
        $list = [];
        foreach ($this->checkedUserCos as $cos) {
            $list[] = $cos->getTitle();
        }
        return $this->formatList($list, $html);

    }

    /**
     * Get the modules that are allowed for selection at registration
     * If restrictions are fulfilled, then only these modules should be selectable for direct registration
     * @return Module[]
     */
    public function getCheckedAllowedModules(): array
    {
        return $this->checkedAllowedModules;
    }

    /**
     * Get the modules that are fitting the user's courses of study
     * These may have satisfied restrictions or not
     * If restrictions are not fulfilled, then these modules should be selectable for the registration by request
     * The course responsible may overrule the restrictions by accepting the request,
     * but the module selection should only be possible for modules fitting to the courses of study
     * @return Module[]
     */
    public function getCheckedFittingModules(): array
    {
        return $this->checkedFittingModules;
    }

    /**
     * Clear the result data from a check
     */
    protected function clearCheckResult()
    {
      $this->checkMessage = '';
      $this->checkInfo = '';
      $this->checkedTerm = null;
      $this->checkedUserCos = [];
      $this->checkedAllowedModules = [];
      $this->checkedForbiddenModules = [];
      $this->checkedForbiddenEvent = null;
    }

    /**
     * Check if the restrictions of an event are satisfied for a user
     */
    protected function checkEvent(Event $event, Person $person, Term $term) : bool
    {
        // prepare a clone of the event that gets the check result
        // only the failed restrictions will be added
        // this allows a display the actual failed restrictions
        $checkedEvent = $event->withoutRestrictions();

        // get the subjects studied in the given term
        $subjects = $person->getSubjects($term);

        // load the achieved requirements of the person (cached)
        $achievements = $this->dic->fau()->user()->repo()->getAchievementsOfPerson($person->getPersonId());

        // check the hard restrictions defined for the module
        // all restrictions must be passed, if one is failed then the module is forbidden
        $oneRestrictionFailed = false;
        foreach ($event->getRestrictions() as $restriction) {
            if (!$this->checkRestriction($restriction, $subjects, $achievements)) {
                $checkedEvent = $checkedEvent->withRestriction($restriction);
                $oneRestrictionFailed = true;
            }
        }

        if ($oneRestrictionFailed) {
            $this->checkedForbiddenEvent = $checkedEvent;
            return false;
        }
        else {
            $this->checkedForbiddenEvent = null;
            return true;
        }
    }


    /**
     * Check if a module is allowed for a person in a term
     */
    protected function checkModule(Module $module, Person $person, Term $term) : bool
    {
        // prepare a clone of the module that gets the check result
        // only the failed restrictions will be added
        // this allows a display the actual failed restrictions
        $checkedModule = $module->withoutRestrictions();

        // get the relevant subjects of the student for the module
        // if no subject matches, then the module should not be allowed
        $cos_ids = $this->dic->fau()->study()->repo()->getCoursesOfStudyIdsForModule($module->getModuleId());
        $subjects = $person->getSubjectsWithCourseOfStudyDbIds($term, $cos_ids);
        if (!empty($subjects)) {
            // module fits for the users study and may be selectable for a registration request
            // but further restrictions have to be checked
            $this->checkedFittingModules[$checkedModule->getModuleId()] = $checkedModule;
        }

        // load the achieved requirements of the person (cached)
        $achievements = $this->dic->fau()->user()->repo()->getAchievementsOfPerson($person->getPersonId());

        // check the hard restrictions defined for the module
        // all restrictions must be passed, if one is failed then the module is forbidden
        $oneRestrictionFailed = false;
        foreach ($module->getRestrictions() as $restriction) {
            if (!$this->checkRestriction($restriction, $subjects, $achievements)) {
                $checkedModule = $checkedModule->withRestriction($restriction);
                $oneRestrictionFailed = true;
            }
        }

        if (empty($subjects) || $oneRestrictionFailed) {
            $this->checkedForbiddenModules[$checkedModule->getModuleId()] = $checkedModule;
            return false;
        }
        else {
            $this->checkedAllowedModules[$checkedModule->getModuleId()] = $checkedModule;
            return true;
        }
    }

    /**
     * Check a restriction against the subjects and achievements of a user
     * @param HardRestriction $restriction
     * @param Subject[] $subjects
     * @param Achievement[] $achievements
     * @return bool restriction is satisfied
     */
    protected function checkRestriction(HardRestriction $restriction, array $subjects, array $achievements) : bool
    {
        // event based restriction is not relevant for certain courses of study
        if (!empty($restriction->getExceptionCosIds())) {
            foreach ($subjects as $subject) {
                if (in_array($subject->getCourseOfStudyDbId(), $restriction->getExceptionCosIds())) {
                    // course of study is an exception for the restriction
                    return true;
                }
            }
        }

        // event based restriction is only relevant for certain courses of study
        if (!empty($restriction->getRegardingCosIds())) {
            $found = false;
            foreach ($subjects as $subject) {
                if (in_array($subject->getCourseOfStudyDbId(), $restriction->getRegardingCosIds())) {
                    $found = true;
                }
            }
            if (!$found) {
                // no course of study is relevant for this restriction
                return true;
            }
        }

        $achievedIds = [];
        foreach ($achievements as $achievement) {
            $achievedIds[] = $achievement->getRequirementId();
        }

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

        return $oneExpressionPassed;
    }


    /**
     * Format a text as a label for a following list
     * @param string|null $prefix
     * @param string|null $text
     * @param string|null $addition
     * @param bool $html
     * @return string
     */
    protected function formatLabel(?string $prefix, ?string $text, ?string $addition, bool $html = true)
    {
        if (!empty($prefix) && !empty($text)) {
            $prefix .= ' ';
        }
        if (!empty($addition)) {
            $addition = ' (' . $addition . ')';
        }

        if ($html) {
            return $prefix . "<strong>" . $text . "</strong>" . $addition . ": \n";
        }
        else {
            return $prefix . $text . $addition . ": \n";
        }
    }

    /**
     * Format a list of texts for display
     * @param string[] $texts
     * @param bool $html    use HTML to format
     * @param bool $wide    separate the elements with an additional newline if not formatted by html
     */
    protected function formatList(array $texts, bool $html = true, bool $wide = false)
    {
        if (empty($texts)) {
            return '';
        }
        elseif ($html) {
            foreach ($texts as $index => $element) {
                $texts[$index] = '<li>' . $element . '</li>';
            }
            return '<ul>' . implode("\n", $texts) . '</ul>';
        }
        elseif ($wide) {
            return implode("\n\n", $texts);
        }
        else {
            return implode(";\n", $texts);
        }
    }
}