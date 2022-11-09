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
use FAU\Study\Data\ImportId;

/**
 * Handling hard restrictions for students' access to lecture events
 * These restrictions are officially defined by the courses of study and provided by campo
 * They should prevent a direct registration for events if not matching (registration request is fallback)
 *
 * The restrictions are checked for an ILIAS object (group or course) and an ILIAS user
 * Afterwards the result is available with getCheck... functions.
 */
class HardRestrictions
{
    protected Container $dic;
    protected ilLanguage $lng;
    protected Service $service;
    protected Repository $repo;

    /**
     * Last object check is passed
     */
    protected bool $checkPassed = false;

    /**
     * Message to the student from the last object check (shown on registration page)
     */
    protected string $checkMessage = '';

    /**
     * Result info text for course admins from the last object check (shown in membership administration)
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
     * Event without directly assigned rwstrictions or with directy assigned restrictions that are satisfied
     * @var ?Event
     */
    protected $checkedAllowedEvent = null;


    /**
     * Event with directly assigned restrictions that are not satisfied
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
     * @param ?Event $event         Event with restriction data
     * @param Module[] $modules     Modules with restriction data
     * @param bool $html            Get formatted html instead of plain text
     * @param bool $checked         The result of a check should be displayed
     */
    protected function getRestrictionTexts(?Event $event, array $modules, bool $html = true, bool $checked = false) : string
    {
        $texts = [];
        foreach ($modules as $module) {
            $resTexts = [];

            // first show the module's courses of study as restrictions
            $studyTexts = [];
            foreach ($this->dic->fau()->study()->repo()->getCoursesOfStudyForModule($module->getModuleId()) as $cos) {
                if ($checked) {
                    $fitting = in_array($cos->getCosId(), $module->getFittingCosIds());
                    $studyTexts[] =  $this->formatText($cos->getTitle(), $fitting, $html) . ' ' . $this->formatCheck($fitting, $html);
                }
                else {
                    $studyTexts[] =  $cos->getTitle();
                }
            }
            if (!empty($studyTexts)) {
                $studyTexts = array_unique($studyTexts);
                sort($studyTexts);
                $label = $this->formatLabel( $this->lng->txt(
                    count($studyTexts) == 1 ? 'studydata_cos' : 'fau_rest_one_of_studies'), '', '', $html);
                $resTexts[] = $label . $this->formatList($studyTexts, $html);
            }


            // then show the module's restrictions
            foreach ($module->getRestrictions() as $restriction) {
                $resTexts[] = $this->getRestrictionAsText($restriction, $html, $checked);
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
                if (!empty($restriction->getRegardingCosIds())) {
                    $cosTexts = [];
                    foreach ($this->dic->fau()->study()->repo()->getCoursesOfStudy($restriction->getRegardingCosIds()) as $cos) {
                        $cosTexts[] = $cos->getTitle()
                            . ($checked ? ' ' . $this->formatCheck(in_array($cos->getCosId(), $restriction->getFittingCosIds()), $html) : '');
                    }
                    $resTexts[] = $this->getRestrictionAsText($restriction, $html, $checked) . ' - ' . $this->lng->txt('fau_rest_regarding_cos')
                        . $this->formatList(array_unique($cosTexts), $html);
                }
                elseif (!empty($restriction->getExceptionCosIds())) {
                    $cosTexts = [];
                    foreach ($this->dic->fau()->study()->repo()->getCoursesOfStudy($restriction->getExceptionCosIds()) as $cos) {
                        $cosTexts[] = $cos->getTitle()
                            . ($checked ? ' ' . $this->formatCheck(in_array($cos->getCosId(), $restriction->getFittingCosIds()), $html) : '');
                    }
                    $resTexts[] = $this->getRestrictionAsText($restriction, $html, $checked) . ' - ' . $this->lng->txt('fau_rest_exception_cos')
                        . $this->formatList(array_unique($cosTexts), $html);
                }
                else {
                    $resTexts[] = $this->getRestrictionAsText($restriction, $html, $checked);
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
     * @param HardRestriction $restriction
     * @param bool $html                    Get formatted html instead of plain text
     * @param bool $checked                 Indicate the result if a check
     * @return string
     */
    protected function getRestrictionAsText(HardRestriction $restriction, $html = true, $checked = false) : string
    {
        $reqNames = [];
        foreach ($restriction->getRequirements() as $requirement) {
            if ($requirement->getId() != 0) {
                $reqNames[] = $requirement->getName()
                    . ($checked ? ' ' . $this->formatCheck($requirement->isSatisfied(), $html) : '');
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
                    $text .= $expression->getNumber() . '. ' . $this->lng->txt('fau_rest_subject_semester')
                        . ($checked ? ' ' . $this->formatCheck($expression->isSatisfied(), true) : '');
                    break;
                case HardRestriction::TYPE_CLINICAL_SEMESTER:
                    $text .= $expression->getNumber() . '. ' . $this->lng->txt('fau_rest_clinical_semester')
                        . ($checked ? ' ' . $this->formatCheck($expression->isSatisfied(), true) : '');
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
                    $text .= ($checked ? ' ' . $this->formatCheck($expression->isSatisfied(), false) : '');
                    break;
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
            foreach ($this->repo->getHardRestrictionsOfEvents([$event->getEventId()]) as $event_id => $restrictions) {
                foreach ($restrictions as $restriction) {
                    $event = $event->withRestriction($restriction);
                }
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
        $modules = $this->dic->fau()->study()->repo()->getModulesOfEvent($event_id);
        foreach ($this->repo->getHardRestrictionsOfModules(array_keys($modules)) as $module_id => $restrictions) {
            $module = $modules[$module_id];
            foreach ($restrictions as $restriction) {
                $module = $module->withRestriction($restriction);
            }
            $modules[$module->getModuleId()] = $module;
        }
        return $modules;
    }

    /**
     * Get the link to show restrictions of an object
     */
    public function getRestrictionsLinkForObject(int $obj_id) : string
    {
        if ($this->hasObjectRestrictions($obj_id)) {
            $importId = $this->dic->fau()->study()->repo()->getImportId($obj_id);
            return \fauHardRestrictionsGUI::getInstance()->getRestrictionsModalLink((int) $importId->getEventId(), (string) $importId->getTermId());
        }
        return '';
    }


    /**
     * Check if restrictions are defined for an object
     */
    public function hasObjectRestrictions($obj_id) : bool
    {
        $importId = $this->dic->fau()->study()->repo()->getImportId($obj_id);
        if (empty($event_id = $importId->getEventId())) {
            return false;
        }
        return $this->hasEventRestrictionsOrModules((int) $event_id);
    }

    /**
     * Check if an event has restrictions or modules assigned
     */
    public function hasEventRestrictionsOrModules(int $event_id) : bool
    {
        if ($this->dic->fau()->cond()->repo()->hasEventRestrictions($event_id)) {
            return true;
        }
        if ($this->dic->fau()->study()->repo()->hasEventModules($event_id)) {
            return true;
        }
        return false;
    }

    /**
     * Check if a user can join an ILIAS object
     */
    public function checkObject(int $obj_id, int $user_id) : bool
    {
        $import_id = $this->dic->fau()->study()->repo()->getImportId($obj_id);
        return $this->checkByImportId($import_id, $user_id);
    }

    /**
     * Check if a user can join an ILIAS object
     */
    public function checkByImportId(ImportId $import_id, int $user_id) : bool
    {
        $this->clearCheckResult();

        if (empty($event_id = $import_id->getEventId())) {
            // manual created course / group => no check message
            return $this->checkPassed = true;
        }

        $event = $this->getEventWithLoadedRestrictions($event_id);
        if (empty($event)) {
            $this->checkMessage = $this->lng->txt('fau_check_success_no_event');
            $this->checkInfo = $this->lng->txt('fau_check_info_passed');
            return $this->checkPassed = true;
        }

        $modules = $this->getModulesOfEventWithLoadedRestrictions($event_id);
        if (empty($modules) && empty($event->getRestrictions())) {
            $this->checkMessage = $this->lng->txt('fau_check_success_no_restriction');
            $this->checkInfo = $this->lng->txt('fau_check_info_passed');
            return $this->checkPassed = true;
        }

        $term = Term::fromString($import_id->getTermId());
        if (!$term->isValid()) {
            $this->checkMessage = $this->lng->txt('fau_check_failed_term_not_valid');
            $this->checkInfo = $this->lng->txt('fau_check_info_failed_term_not_valid');
            return $this->checkPassed = false;
        }

        if (empty($person = $this->dic->fau()->user()->repo()->getPersonOfUser($user_id))) {
            $this->checkMessage = $this->lng->txt('fau_check_failed_no_studydata');
            $this->checkInfo = $this->lng->txt('fau_check_info_failed_no_studydata');
            return $this->checkPassed = false;
        }

        if (empty($person->getStudiesOfTerm($term))) {
            $this->checkMessage = $this->lng->txt('fau_check_failed_no_studydata');
            $this->checkInfo = $this->lng->txt('fau_check_info_failed_no_studydata');
            return $this->checkPassed = false;
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

        return $this->checkPassed = (empty($this->checkedForbiddenEvent)
            && (empty($this->checkedForbiddenModules) || !empty($this->checkedAllowedModules)));
    }

    /**
     * Get the HTML message from the registration check
     * This message can be shown to the student on the registration page
     */
    public function getCheckResultMessage() : string
    {
        if (!empty($this->checkMessage)) {
            return $this->checkMessage;
        }

        // don't show details for passed check
        if ($this->checkPassed) {
           return $this->lng->txt('fau_check_passed_restrictions');
        }

        // show details for failed event and module restrictions
        return $this->lng->txt('fau_check_failed_restrictions')
            . $this->getRestrictionTexts($this->checkedForbiddenEvent, $this->checkedForbiddenModules, true, true);
    }

    /**
     * Get the info text from the registration check
     * This text can be used on the waiting list and for the member export of courses
     * Only the event and modules are shown that are relevant for the result
     */
    public function getCheckResultInfo(bool $html = false, ?int $selected_module_id = null) : string
    {
        if (!empty($this->checkInfo)) {
            return $this->checkInfo;
        }

        // select what to be shown for a passed or failed check
        $label =  $this->lng->txt($this->checkPassed ? 'fau_check_info_passed_restrictions': 'fau_check_info_failed_restrictions');
        $checkedEvent = $this->checkPassed ? $this->checkedAllowedEvent : $this->checkedForbiddenEvent;
        $checkedModules = $this->checkPassed ? $this->checkedAllowedModules : $this->checkedForbiddenModules;

        if (isset($selected_module_id) && isset($this->checkedModules[$selected_module_id])) {
            // limit the display of restrictions restrictions if a module is selected, e.g. for display in the waiting list
            return $label . ': ' . $this->getRestrictionTexts($checkedEvent, [$checkedModules[$selected_module_id]], $html, true);
        }
        else {
            // no module selected or selected module not for the event => show all failed event and module restrictions
            return $label . ': ' . $this->getRestrictionTexts($checkedEvent, $checkedModules, $html, true);
        }
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
            $list[] = $cos->getTitle(false);
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
     * Get the modules that are forbidden for selection at registration
     * @return Module[]
     */
    public function getCheckedForbiddenModules(): array
    {
        return $this->checkedForbiddenModules;
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
        $this->checkPassed = false;
        $this->checkMessage = '';
        $this->checkInfo = '';
        $this->checkedTerm = null;
        $this->checkedUserCos = [];
        $this->checkedAllowedModules = [];
        $this->checkedForbiddenModules = [];
        $this->checkedAllowedEvent = null;
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
            $restriction = $this->checkRestriction($restriction, $subjects, $achievements);
            $checkedEvent = $checkedEvent->withRestriction($restriction);
            if (!$restriction->isSatisfied()) {
                $oneRestrictionFailed = true;
            }
        }

        if ($oneRestrictionFailed) {
            $this->checkedAllowedEvent = null;
            $this->checkedForbiddenEvent = $checkedEvent;
            return false;
        }
        else {
            $this->checkedAllowedEvent = $checkedEvent;
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
        $checkedModule = $checkedModule->withFittingCosIds(array_keys($subjects));
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
            $restriction = $this->checkRestriction($restriction, $subjects, $achievements);
            $checkedModule = $checkedModule->withRestriction($restriction);
            if (!$restriction->isSatisfied()) {
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
     * @return HardRestriction with result available by isSatisfied()
     */
    protected function checkRestriction(HardRestriction $restriction, array $subjects, array $achievements) : HardRestriction
    {
        $checkedRestriction = $restriction;

        // event based restriction is not relevant for certain courses of study
        if (!empty($restriction->getExceptionCosIds())) {
            $found = false;
            foreach ($subjects as $subject) {
                if (in_array($subject->getCourseOfStudyDbId(), $restriction->getExceptionCosIds())) {
                    // course of study is an exception for the restriction
                    $checkedRestriction = $checkedRestriction->withFittingCosId($subject->getCourseOfStudyDbId());
                    $found = true;
                }
            }
            if ($found) {
                // one course of study is an exception for this restriction
                return $checkedRestriction->withSatisfied(true);
            }
        }

        // event based restriction is only relevant for certain courses of study
        if (!empty($restriction->getRegardingCosIds())) {
            $found = false;
            foreach ($subjects as $subject) {
                if (in_array($subject->getCourseOfStudyDbId(), $restriction->getRegardingCosIds())) {
                    $checkedRestriction = $checkedRestriction->withFittingCosId($subject->getCourseOfStudyDbId());
                    $found = true;
                }
            }
            if (!$found) {
                // no course of study is relevant for this restriction
                return $checkedRestriction->withSatisfied(true);
            }
        }

        $achievedIds = [];
        foreach ($achievements as $achievement) {
            $achievedIds[] = $achievement->getRequirementId();
        }

        // remove the expressions to add them lather with their result
        $checkedRestriction = $checkedRestriction->withoutExpressions();

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
                    $checkedRestriction = $checkedRestriction->withExpression($expression->withSatisfied($found));
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
                    $checkedRestriction = $checkedRestriction->withExpression($expression->withSatisfied($found));
                    break;

                case HardRestriction::TYPE_REQUIREMENT:
                    // remove the expressions to add them lather with their result
                    $checkedRestriction = $checkedRestriction->withoutRequirements();
                    $found = 0;
                    foreach ($restriction->getRequirements() as $requirement) {
                        $achieved = in_array($requirement->getId(), $achievedIds);
                        $checkedRestriction = $checkedRestriction->withRequirement($requirement->withSatisfied($achieved));
                        if ($achieved) {
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
                    }
                    if ($expression->getCompare() == HardExpression::COMPARE_MIN
                        && $found >= $expression->getNumber()) {
                        $checkedRestriction = $checkedRestriction->withExpression($expression->withSatisfied(true));
                        $oneExpressionPassed = true;
                    }
                    elseif ($expression->getCompare() == HardExpression::COMPARE_MAX
                        && $found <= $expression->getNumber()) {
                        $checkedRestriction = $checkedRestriction->withExpression($expression->withSatisfied(true));
                        $oneExpressionPassed = true;
                    }
                    else {
                        $checkedRestriction = $checkedRestriction->withExpression($expression->withSatisfied(false));
                    }
                    break;
            } // end switch restriction type
        } // end expressions loop

        return $checkedRestriction->withSatisfied($oneExpressionPassed);
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

    /**
     * Format a satisfied / not satisfied result of a check
     * @param bool $result
     * @param bool $html
     */
    protected function formatCheck(bool $result, bool $html)
    {
        if ($html) {
            return $result ?
                '<span style="font-weight: bold; color: green;">✓</span>' :
                '<span style="font-weight: bold; color: red;">✗</span>';
        }
        else {
            return $result ? '✓' : '✗';
        }
    }

    /**
     * Format a text with an optional highlight
     *
     * @param bool $result
     * @param bool $html
     */
    protected function formatText(string $text, bool $highlight, bool $html)
    {
        if ($html) {
            return $highlight ?
                '<strong>' . $text . '</strong>' :
                $text;
        }
        else {
            return $text;
        }
    }

}