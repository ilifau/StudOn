<?php

namespace FAU\Cond;

use FAU\Cond\Data\Restriction;
use FAU\Cond\Data\RestrictionText;
use FAU\Study\Data\CourseOfStudy;
use FAU\Study\Data\Event;
use FAU\Tools\Format;
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
    protected Format $format;

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
    protected ?Term $checkedTerm = null;

    /**
     * ID of the last checked import id
     */
    protected ?ImportId $checkedImportId = null;

    /**
     * ID of the last checked user
     */
    protected ?int $checkedUserId = null;

    /**
     * Courses of studies of the user for which the conditions were checked
     * @var CourseOfStudy[]
     */
    protected array $checkedUserCos = [];

    /**
     * Modules that fit the user's courses of study
     * These may have satisfied or unsatisfied restrictions
     * @var Module[] (indexed by module id)
     */
    protected $checkedFittingModules = [];

    /**
     * Modules that are allowed for a selection at registration
     * The restrictions are cleared in these modules
     * @var Module[] (indexed by module id)
     */
    protected array $checkedAllowedModules = [];


    /**
     * Modules that are forbidden for a selection at registration
     * The restrictions in these modules are those that are not satisfied
     * Modules without restriction in this array are not studied
     * @var Module[] (indexed by module id)
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
        $this->format = $dic->fau()->tools()->format();
    }
    

    /**
     * Get all restriction texts of an event
     * @return string[]
     */
    public function getEventRestrictionTexts(int $event_id, bool $html = true) : array
    {
        $event = $this->getEventWithLoadedRestrictions($event_id);
        $modules = $this->getModulesOfEventWithLoadedRestrictions($event_id);
        $texts = [];
        foreach ($this->getRestrictionTexts($event, $modules, $html) as $restrictionText) {
            $texts[] = $restrictionText->getContent();
        }
        return $texts;
    }


    /**
     * Get the restriction texts of an event and its modules
     *
     * @param ?Event $event         Event with restriction data
     * @param Module[] $modules     Modules with restriction data
     * @param bool $html            Get formatted html instead of plain text
     * @param bool $checked         The result of a check should be displayed
     * @return RestrictionText[]    indexed by hash
     */
    protected function getRestrictionTexts(?Event $event, array $modules, bool $html = true, bool $checked = false, ?int $selected_module_id = null) : array
    {
        $texts = [];
        foreach ($modules as $module) {
            $resTexts = [];

            // first show the module's courses of study as restrictions
            $studyTexts = [];
            foreach ($this->dic->fau()->study()->repo()->getCoursesOfStudyForModule($module->getModuleId()) as $cos) {
                if ($checked) {
                    $fitting = in_array($cos->getCosId(), $module->getFittingCosIds());
                    $studyTexts[] =  $this->format->text($cos->getTitle(), $fitting, $html) . ' ' . $this->format->check($fitting, $html);
                }
                else {
                    $studyTexts[] =  $cos->getTitle();
                }
            }
            if (!empty($studyTexts)) {
                $studyTexts = array_unique($studyTexts);
                sort($studyTexts);
                $label = $this->format->label( $this->lng->txt(
                    count($studyTexts) == 1 ? 'studydata_cos' : 'fau_rest_one_of_studies'), '', '', $html);
                $resTexts[] = $label . $this->format->list($studyTexts, $html);
            }


            // then show the module's restrictions
            foreach ($module->getRestrictions() as $restriction) {
                $resTexts[] = $this->getRestrictionAsText($restriction, $html, $checked);
            }

            // put all module information together
            $label = $this->format->label($this->lng->txt('fau_module'),  $module->getModuleName(), $module->getModuleNr(), $html);
            if (!empty($resTexts)) {
                $module_text = $label . $this->format->list($resTexts, $html);
            }
            else {
                $module_text = $label . $this->format->list([$this->lng->txt('fau_rest_module_in_cos')], $html);
            }

            $restrictionText = new RestrictionText(
                $module_text,
                true,
                isset($this->checkedFittingModules[$module->getModuleId()]),
                isset($this->checkedAllowedModules[$module->getModuleId()]),
                $module->getModuleId() == $selected_module_id
            );

            // avoid doubling of of somilar restictions texts (same content and filtering flags)
            $texts[$restrictionText->hash()] = $restrictionText;
        }

        usort($texts, function(RestrictionText $text1, RestrictionText $text2) {
            return $text2->getContent() <=> $text1->getContent();
        });

        // add the restrictions of the event before the module restrictions
        $resTexts = [];
        if (!empty($event) && !empty($event->getRestrictions()))
        {
            foreach ($event->getRestrictions() as $restriction) {
                if (!empty($restriction->getRegardingCosIds())) {
                    $cosTexts = [];
                    foreach ($this->dic->fau()->study()->repo()->getCoursesOfStudy($restriction->getRegardingCosIds()) as $cos) {
                        $cosTexts[] = $cos->getTitle()
                            . ($checked ? ' ' . $this->format->check(in_array($cos->getCosId(), $restriction->getFittingCosIds()), $html) : '');
                    }
                    $resTexts[] = $this->getRestrictionAsText($restriction, $html, $checked) . ' - ' . $this->lng->txt('fau_rest_regarding_cos')
                        . $this->format->list(array_unique($cosTexts), $html);
                }
                elseif (!empty($restriction->getExceptionCosIds())) {
                    $cosTexts = [];
                    foreach ($this->dic->fau()->study()->repo()->getCoursesOfStudy($restriction->getExceptionCosIds()) as $cos) {
                        $cosTexts[] = $cos->getTitle()
                            . ($checked ? ' ' . $this->format->check(in_array($cos->getCosId(), $restriction->getFittingCosIds()), $html) : '');
                    }
                    $resTexts[] = $this->getRestrictionAsText($restriction, $html, $checked) . ' - ' . $this->lng->txt('fau_rest_exception_cos')
                        . $this->format->list(array_unique($cosTexts), $html);
                }
                else {
                    $resTexts[] = $this->getRestrictionAsText($restriction, $html, $checked);
                }
            }
            $label = $this->format->label($this->lng->txt('fau_campo_event'), '', '', $html);

            $text = new RestrictionText(
                $label . $this->format->list($resTexts, $html),
                false,
                true,
                isset($this->checkedAllowedEvent) && $this->checkedAllowedEvent->getEventId() == $event->getEventId(),
                true
            );

            $texts = array_merge([$text->hash() => $text], $texts);
        }

        return $texts;
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
                    . ($checked ? ' ' . $this->format->check($requirement->isSatisfied(), $html) : '');
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
                        . ($checked ? ' ' . $this->format->check($expression->isSatisfied(), true) : '');
                    break;
                case HardRestriction::TYPE_CLINICAL_SEMESTER:
                    $text .= $expression->getNumber() . '. ' . $this->lng->txt('fau_rest_clinical_semester')
                        . ($checked ? ' ' . $this->format->check($expression->isSatisfied(), true) : '');
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
                    $text .= ($checked ? ' ' . $this->format->check($expression->isSatisfied(), $html) : '');
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
        $modules = $this->repo->getModulesOfEventWithRestrictionOrCos($event_id);
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
     * Check if restrictions are defined for an object
     */
    public function hasObjectRestrictions($obj_id) : bool
    {
        $importId = $this->dic->fau()->study()->repo()->getImportId($obj_id);
        if (empty($event_id = $importId->getEventId())) {
            return false;
        }
        return $this->hasEventOrModuleRestrictions((int) $event_id);
    }

    /**
     * Check if an event has restrictions based on event or module
     */
    public function hasEventOrModuleRestrictions(int $event_id) : bool
    {
        if ($this->dic->fau()->cond()->repo()->hasEventRestrictions($event_id)) {
            return true;
        }
        if ($this->dic->fau()->cond()->repo()->hasModuleRestrictions($event_id)) {
            return true;
        }
        if ($this->dic->fau()->study()->repo()->hasModuleWithCos($event_id)) {
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
     * Check if a user can join courses of an event given by import_id
     */
    public function checkByImportId(ImportId $import_id, int $user_id) : bool
    {
        $this->clearCheckResult();

        $this->checkedImportId = $import_id;
        $this->checkedUserId = $user_id;

        if (empty($event_id = $import_id->getEventId())) {
            $this->checkMessage = $this->lng->txt('fau_check_success_no_event');
            $this->checkInfo = $this->lng->txt('fau_check_info_passed');
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

        // set and return the check result
        return $this->checkPassed = (empty($this->checkedForbiddenEvent)
            && (empty($this->checkedForbiddenModules) || !empty($this->checkedAllowedModules)));
    }

    /**
     * Get the result whether the check is passed
     */
    public function getCheckPassed() : bool
    {
        return $this->checkPassed;
    }

    /**
     * Get message from the registration check
     * The message text is directly addressed to the checked user
     * It can be shown to the student on the registration page and in the check result modal
     */
    public function getCheckMessage() : string
    {
        if (!empty($this->checkMessage)) {
            return $this->checkMessage;
        }

        if ($this->checkPassed) {
            return $this->lng->txt('fau_check_passed_restrictions');
        }
        else {
            return $this->lng->txt('fau_check_failed_restrictions');
        }
    }

    /**
     * Get the info from the registration check
     * This info is not directly addressed to the cheked user
     * It can be used in the membership administration and for the member export of courses
     */
    public function getCheckInfo() : string
    {
        if (!empty($this->checkInfo)) {
            return $this->checkInfo;
        }

        if ($this->checkPassed) {
            return $this->lng->txt('fau_check_info_passed_restrictions');
        }
        else {
            return $this->lng->txt('fau_check_info_failed_restrictions');
        }
    }


    /**
     * Get the details from the registration check
     * This text can be used for exports of the waiting list and members
     * Only the event and modules are shown that are relevant for the result
     * @return string
     */
    public function getCheckDetails(bool $html = true, ?int $selected_module_id = null) : string
    {
        // select what to be shown for a passed or failed check
        $checkedEvent = $this->checkPassed ? $this->checkedAllowedEvent : $this->checkedForbiddenEvent;
        $checkedModules = $this->checkPassed ? $this->checkedAllowedModules : $this->checkedForbiddenModules;

        if (isset($selected_module_id) && isset($checkedModules[$selected_module_id])) {
            // limit the display of restrictions restrictions if a module is selected, e.g. member export
            $texts = $this->getRestrictionTexts($checkedEvent, [$checkedModules[$selected_module_id]], $html, true);
        }
        else {
            // no module selected or selected module not for the event => show all failed event and module restrictions
            $texts = $this->getRestrictionTexts($checkedEvent, $checkedModules, $html, true);
        }

        $details = [];
        foreach ($texts as $text) {
            $details[] = $text->getContent();
        }
        return $this->format->list($details, $html);
    }

    /**
     * Get the details from the registration check
     * This text can be used for exports of the waiting list and members
     * Only the event and modules are shown that are relevant for the result
     * @return RestrictionText[]
     */
    public function getCheckedRestrictionTexts(bool $html = true, ?int $selected_module_id = null) : array
    {
        // select what to be shown for a passed or failed check
        $checkedEvent = $this->checkPassed ? $this->checkedAllowedEvent : $this->checkedForbiddenEvent;
        $checkedModules = array_merge($this->checkedAllowedModules, $this->checkedForbiddenModules);

        return $this->getRestrictionTexts($checkedEvent, $checkedModules, $html, true, $selected_module_id);
    }


    /**
     * Get the term for which the check was done
     */
    public function getCheckedTermTitle(): string
    {
        return $this->checkedTerm ? $this->dic->fau()->study()->getTermText($this->checkedTerm) : '';
    }


    /**
     * Get the id if the checked import id
     */
    public function getCheckedImportId(): ?ImportId
    {
        return $this->checkedImportId;
    }


    /**
     * Get the id if the checked user
     */
    public function getCheckedUserId(): ?int
    {
        return $this->checkedUserId;
    }


    /**
     * Get the texts for the checks courses of study of a usr
     */
    public function getCheckedUserCosTexts(bool $html = true) : string
    {
        $list = [];
        foreach ($this->checkedUserCos as $cos) {
            $list[] = $cos->getTitle(false);
        }
        return $this->format->list($list, $html);

    }

    /**
     * Get the modules that are allowed for selection at registration
     * If restrictions are fulfilled, then only these modules should be selectable for direct registration
     * @return Module[] (indexed by module id)
     */
    public function getCheckedAllowedModules(): array
    {
        return $this->checkedAllowedModules;
    }

    /**
     * Get the modules that are forbidden for selection at registration
     * @return Module[] (indexed by module id)
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
     * Get the options to select a module
     * Include only the modules that fit to the user's courses of study
     * Modules with failed restrictions can be disabled, @see getCheckedModuleSelectDisabledIds
     * @return array module id => module label with check result indicator
     */
    public function getCheckedModuleSelectOptions() : array
    {
        $options = [];
        foreach ($this->getCheckedFittingModules() as $module) {
            if (isset($this->checkedAllowedModules[$module->getModuleId()])) {
                $options[$module->getModuleId()] = '✓ ' . $module->getLabel();
            }
            else {
                $options[$module->getModuleId()] = '✗ ' . $module->getLabel();
            }
        }
        return $options;
    }

    /**
     * Get the ids of modules that should be disabled in the module selection
     * If allowed modules exist, disable all other modules in the list
     * Don't disable fitting modules, if no modules are allowed
     * In this case, acceptance into the course is needed
     * This will be an acceptance of the selected module, even if id doesn't passed the conditions
     * @return int[]
     */
    public function getCheckedModuleSelectDisabledIds() : array
    {
        if (empty($this->checkedAllowedModules)) {
            // disable only forbidden modules that don't fit to the courses of study
            return array_diff(array_keys($this->checkedForbiddenModules), array_keys($this->checkedFittingModules));
        }
        else {
            // disable all forbidden modules becaause an sllowed module can be selected
            return array_keys($this->checkedForbiddenModules);
        }
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
        $this->checkedUserId = null;
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
        $cos_ids = $person->getCourseOfStudyDbIds($term);
        $subjects = $person->getSubjectsWithCourseOfStudyDbIds($term, $cos_ids);
        
        // the fitting cos_ids may be more than the cos ids of the person
        // at least one must exist for module to be allowed
        $fitting_cos_ids = $this->getFittingCosIdsForModule($module->getModuleId(), $cos_ids, $term);
        $checkedModule = $checkedModule->withFittingCosIds($fitting_cos_ids);
        if (!empty($fitting_cos_ids)) {
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

        if (empty($fitting_cos_ids) || $oneRestrictionFailed) {
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
     * Get the cos_ids that fit to the a module for a student
     * This may be cos_ids directly shared between the studies and the module
     * Or if may by cos_ids with a shared certain degree between the studies and the module
     *
     * @param int   $module_id
     * @param int[] $cos_ids
     * @param Term  $term
     * @return int[]
     */
    protected function getFittingCosIdsForModule(int $module_id, array $cos_ids, Term $term) : array
    {
        $degrees = $this->dic->fau()->study()->repo()->getFittingDegrees($cos_ids, [
            'Austauschstudium Bachelor',
            'Austauschstudium Master',
            'Austauschstudium Promotion'
        ]);
        return $this->dic->fau()->study()->repo()->getFittingCosIds($module_id, $cos_ids, $degrees);
    }

}