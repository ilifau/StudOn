<?php declare(strict_types=1);

namespace FAU\Study;

use ILIAS\DI\Container;
use FAU\Study\Data\Term;
use FAU\SubService;
use ilLink;
use ilUtil;
use FAU\Study\Data\ImportId;
use ilObjCourse;
use ilContainer;
use ilObject;

/**
 * Service for study related data
 */
class Service extends SubService
{
    protected \ilLanguage $lng;

    protected Repository $repository;
    protected Matching $matching;
    protected Search $search;


    /**
     * Get the repository for user data
     */
    public function repo() : Repository
    {
        if(!isset($this->repository)) {
            $this->repository = new Repository($this->dic->database(), $this->dic->logger()->fau());
        }
        return $this->repository;
    }

    /**
     * Get the matching functions
     */
    public function matching() : Matching
    {
        if(!isset($this->matching)) {
            $this->matching = new Matching($this->dic);
        }
        return $this->matching;
    }

    /**
     * Get the searching functions
     */
    public function search() : Search
    {
        if(!isset($this->search)) {
            $this->search = new Search($this->dic);
        }
        return $this->search;
    }


    /**
     * Check if an object is needed for campo
     */
    public function isObjectForCampo(int $obj_id) : bool
    {
        return $this->repo()->isIliasObjIdUsedInCourses($obj_id)
            || $this->repo()->getImportId($obj_id)->isForCampo();
    }

    /**
     * Get the url to an entry in campo
     * @param int $event_id
     * @param Term $term
     * @return string
     */
    public function getCampoUrl(int $event_id, ?Term $term = null)
    {
        $link = 'https://www.campo.fau.de:443/qisserver/pages/startFlow.xhtml?_flowId=detailView-flow&unitId=' . $event_id;
        if (isset($term) && $term->toString() == '20222') {
            $link .= '&periodId=395';
        }
        return $link;
    }


    /**
     * Get the link to campo for an ilias course
     */
    public function getCampoLinkForObject(int $obj_id) : string
    {
        $importId = $this->repo()->getImportId($obj_id);
        If (!empty($event_id = $importId->getEventId())) {
            $term = Term::fromString($importId->getTermId());
            $url = $this->getCampoUrl($event_id, $term);
            $title = $this->lng->txt('fau_campo_link') . ($term->isValid() ? ' (' . $this->dic->fau()->study()->getTermText($term, true) .')' : '');
            return '<a target="_blank" href="' . $url . '">' . $title . '</a>';
        }
        return '';
    }


    /**
     * Get the select options for courses of study
     */
    public function getCourseOfStudySelectOptions(?int $emptyId = null) : array
    {
        $options = [];
        if (isset($emptyId)) {
            $options[$emptyId] = $this->lng->txt("please_select");
        }

        $list = [];
        foreach ($this->repo()->getCoursesOfStudy() as $cos) {
            $list[$cos->getTitle()][] = $cos->getCosId();
        }
        foreach ($list as $title => $cos_ids) {
            $options[implode(',', $cos_ids)] = $title;
        }

        asort($options,  SORT_NATURAL);
        return $options;
    }


    /**
     * Get the select options for study modules
     */
    public function getModuleSelectOptions(?int $emptyId = null) : array
    {
        $options = [];

        // bundle all modules with the same name
        $list = [];
        foreach ($this->repo()->getModules() as $module) {
            $title = $module->getModuleName(); // . ' ('. $module->getModuleNr() . ')';
            $list[$title][] = $module->getModuleId();
        }
        foreach ($list as $title => $module_ids) {
            $options[implode(',', $module_ids)] = $title;
        }
        asort($options,  SORT_NATURAL);

        if (isset($emptyId)) {
            $options = array_merge([$emptyId => $this->lng->txt("please_select")], $options);
        }
        return $options;
    }



    /**
     * Get the options for selecting a subject
     *
     * @param ?int $emptyId   add a 'please select' at the beginning with that id
     * @param ?int $chosenId   add a 'unknown option' at the end if that id is not in the list
     * @return array    id => text
     */
    public function getSubjectSelectOptions(?int $emptyId = null, ?int $chosenId = null) : array
    {
        $options = [];
        if (isset($emptyId)) {
            $options[$emptyId] = $this->lng->txt("please_select");
        }
        foreach ($this->repo()->getStudySubjects() as $subject) {
            $title = $subject->getSubjectTitle($this->lng->getLangKey()) ?? $this->lng->txt('studydata_unknown_subject');
            $options[$subject->getSubjectHisId()] = $title . ' [' . $subject->getSubjectUniquename() . ']';

        }
        if (isset($chosenId) && !isset($options[$chosenId]) && (!isset($emptyId) || $chosenId != $emptyId)) {
            $options[$chosenId] = $this->lng->txt('studydata_unknown_subject');
        }

        asort($options,  SORT_NATURAL);
        return $options;
    }


    /**
     * Get the options for selecting a degree
     *
     * @param ?int $emptyId   add a 'please select' at the beginning with that id
     * @param ?int $chosenId   add a 'unknown option' at the end if that id is not in the list
     * @return array    id => text
     */
    public function getDegreeSelectOptions(?int $emptyId = null, ?int $chosenId = null) : array
    {
        $options = [];
        if (isset($emptyId)) {
            $options[$emptyId] = $this->lng->txt("please_select");
        }
        foreach ($this->repo()->getStudyDegrees() as $degree) {
            $title = $degree->getDegreeTitle($this->lng->getLangKey()) ?? $this->lng->txt('studydata_unknown_degree');
            $options[$degree->getDegreeHisId()] = $title . ' [' . $degree->getDegreeUniquename() . ']';

        }
        if (isset($chosenId) && !isset($options[$chosenId]) && (!isset($emptyId) || $chosenId != $emptyId)) {
            $options[$chosenId] = $this->lng->txt('studydata_unknown_degree');
        }

        asort($options,  SORT_NATURAL);
        return $options;
    }

    /**
     * Get the options for selecting an Enrolment
     *
     * @param ?int $emptyId   add a 'please select' at the beginning with that id
     * @param ?int $chosenId   add a 'unknown option' at the end if that id is not in the list
     * @return array    id => text
     */
    public function getEnrolmentSelectOptions(?int $emptyId = null, ?int $chosenId = null) : array
    {
        $options = [];
        if (isset($emptyId)) {
            $options[$emptyId] = $this->lng->txt("please_select");
        }
        foreach ($this->repo()->getStudyEnrolments() as $enrolment) {
            $title = $enrolment->getEnrolmentTitle($this->lng->getLangKey()) ?? $this->lng->txt('studydata_unknown_enrolment');
            $options[$enrolment->getEnrolmentId()] = $title . ' [' . $enrolment->getEnrolmentUniquename() . ']';

        }
        if (isset($chosenId) && !isset($options[$chosenId]) && (!isset($emptyId) || $chosenId != $emptyId)) {
            $options[$chosenId] = $this->lng->txt('studydata_unknown_enrolment');
        }

        asort($options,  SORT_NATURAL);
        return $options;
    }


    /**
     * Get the options for selecting a school
     *
     * @param ?int $emptyId   add a 'please select' at the beginning with that id
     * @param ?int $chosenId   add a 'unknown option' at the end if that id is not in the list
     * @return array    id => text
     */
    public function getSchoolSelectOptions(?int $emptyId = null, ?int $chosenId = null) : array
    {
        $options = [];
        if (isset($emptyId)) {
            $options[$emptyId] = $this->lng->txt("please_select");
        }
        foreach ($this->repo()->getStudySchools() as $school) {
            $title = $school->getSchoolTitle($this->lng->getLangKey()) ?? $this->lng->txt('studydata_unknown_school');
            $options[$school->getSchoolHisId()] = $title . ' [' . $school->getSchoolUniquename() . ']';

        }
        if (isset($chosenId) && !isset($options[$chosenId]) && (!isset($emptyId) || $chosenId != $emptyId)) {
            $options[$chosenId] = $this->lng->txt('studydata_unknown_school');
        }

        asort($options,  SORT_NATURAL);
        return $options;
    }


    /**
     * Get the options for selecting a doc program
     *
     * @param ?string $emptyId   add a 'please select' at the beginning with that id
     * @param ?string $chosenId   add a 'unknown option' at the end if that id is not in the list
     * @return array    id => text
     */
    public function getDocProgSelectOptions(?string $emptyId = null, ?string $chosenId = null) : array
    {
        $options = [];
        if (isset($emptyId)) {
            $options[$emptyId] = $this->lng->txt("please_select");
        }
        foreach ($this->repo()->getDocProgrammes() as $prog) {
            $title = $prog->getProgText() ?? $this->lng->txt('studydata_unknown_doc_program');
            $options[$prog->getProgCode()] = $title . ' [' . $prog->getProgCode() . ']';

        }
        if (isset($chosenId) && !isset($options[$chosenId]) && (!isset($emptyId) || $chosenId != $emptyId)) {
            $options[$chosenId] = $this->lng->txt('studydata_unknown_doc_program');
        }

        asort($options,  SORT_NATURAL);
        return $options;
    }

    /**
     * Get the options for selecting a term
     *
     * @param ?string $emptyId   add a 'please select' at the beginning with that id
     * @param ?string $chosenId   add a 'unknown option' at the end if that id is not in the list
     * @return array    id => text
     */
    public function getTermSelectOptions(?string $emptyId = null, ?string $chosenId = null) : array
    {
        $options = [];
        if (isset($emptyId)) {
            $options[$emptyId] = $this->lng->txt("please_select");
        }
        for ($year = date('Y') - 2; $year < date('Y') + 2; $year++) {
            $term = new Term($year, 1);
            $options[$term->toString()] = $this->getTermText($term);
            $term = new Term($year, 2);
            $options[$term->toString()] = $this->getTermText($term);
        }
        if (isset($chosenId) && !isset($options[$chosenId]) && (!isset($emptyId) || $chosenId != $emptyId)) {
            $term = Term::fromString($chosenId);
            $options[$chosenId] = $this->getTermText($term);
        }
        return $options;
    }

    /**
     * Get the options to search for courses in a term
     * Here the empty option refers to 'no term' before winter 2022
     * Actual terms start with winter 2022
     * @param ?string $chosenId   add at the end if that id is not in the list
     * @param bool $addNone   add a 'none' option at the beginning of the list
     * @return array    id => text
     */
    public function getTermSearchOptions(?string $chosenId = null, bool $addNone = true) : array
    {
        $options = [];

        if ($addNone) {
            $options['none'] = $this->lng->txt("studydata_no_or_former_semester");
        }

        for ($year = 2022; $year < date('Y') + 2; $year++) {
            if ($year > 2022) {
                $term = new Term($year, 1);
                $options[$term->toString()] = $this->getTermText($term);
            }
            $term = new Term($year, 2);
            $options[$term->toString()] = $this->getTermText($term);
        }
        if (isset($chosenId) && !isset($options[$chosenId]) && (!isset($emptyId) || $chosenId != $emptyId)) {
            $term = Term::fromString($chosenId);
            $options[$chosenId] = $this->getTermText($term);
        }
        return $options;
    }


    /**
     * Get the term for the current semester
     * @return Term
     */
    public function getCurrentTerm() : Term
    {
        $year = (int) date('Y');
        $month = (int) date('m');

        if ($month < 4) {
            // winter term of last year
            return new Term($year - 1, 2);
        }
        elseif ($month < 10) {
            // summer term of current year
            return new Term($year, 1);
        }
        else {
            // winter term of this year
            return new Term($year, 2);
        }
    }


    /**
     * Get the term for the next semester
     * @return Term
     */
    public function getNextTerm() : Term
    {
        $year = (int) date('Y');
        $month = (int) date('m');

        if ($month < 4) {
            // summer semester of current year
            return new Term($year, 1);
        }
        elseif ($month < 10) {
            // winter semester of current
            return new Term($year, 2);
        }
        else {
            // summer semester of next year
            return new Term($year + 1, 1);
        }
    }


    /**
     * Get the text for a term (current language)
     */
    public function getTermText(?Term $term, bool $short = false) : string
    {
        if (!isset($term)) {
            return $this->lng->txt('studydata_unknown_semester');
        }
        elseif ($term->getTypeId() == Term::TYPE_ID_SUMMER) {
            return sprintf($this->lng->txt($short ? 'studydata_semester_summer_short' : 'studydata_semester_summer'), $term->getYear());
        }
        elseif ($term->getTypeId() == Term::TYPE_ID_WINTER) {
            $next = substr((string) $term->getYear(), 2,2);
            return sprintf($this->lng->txt($short ? 'studydata_semester_winter_short' : 'studydata_semester_winter'), $term->getYear(), $next + 1);
        }
        else {
            return $this->lng->txt('studydata_ref_semester_invalid');
        }
    }

    /**
     * Get the text for a term in a specific language
     */
    public function getTermTextForLang(?Term $term, string $lang_code, bool $short = false) : string
    {
        if (!isset($term)) {
            return $this->lng->txtlng('fau','studydata_unknown_semester', $lang_code);
        }
        elseif ($term->getTypeId() == Term::TYPE_ID_SUMMER) {
            if ($short) {
                $year = substr((string) $term->getYear(), 2,2);
                return sprintf($this->lng->txtlng('fau','studydata_semester_summer_short', $lang_code), $year);
            }
            else {
                return sprintf($this->lng->txtlng('fau','studydata_semester_summer', $lang_code), $term->getYear());
            }
        }
        elseif ($term->getTypeId() == Term::TYPE_ID_WINTER) {
            $year = substr((string) $term->getYear(), 2,2);
            $next = substr((string) $term->getYear(), 2,2) + 1;
            if ($short) {
                return sprintf($this->lng->txtlng('fau', 'studydata_semester_winter_short', $lang_code), $year, $next);
            }
            else {
                return sprintf($this->lng->txtlng('fau', 'studydata_semester_winter_short', $lang_code), $term->getYear(), $next);
            }
        }
        else {
            return $this->lng->txtlng('fau', 'studydata_ref_semester_invalid', $lang_code);
        }
    }


    /**
     * Get the text for a reference term
     */
    public function getReferenceTermText(?Term $term) : string
    {
        if (!isset($term)) {
            return $this->lng->txt('studydata_ref_semester_any');
        }
        elseif ($term->getTypeId() == Term::TYPE_ID_SUMMER) {
            return sprintf($this->lng->txt('studydata_ref_semester_summer'), $term->getYear());
        }
        elseif ($term->getTypeId() == Term::TYPE_ID_WINTER) {
            return sprintf($this->lng->txt('studydata_ref_semester_winter'), $term->getYear(), $term->getYear() + 1);
        }
        else {
            return $this->lng->txt('studydata_ref_semester_invalid');
        }
    }

    /**
     * Resolve a link target coming frm campo
     */
    public function redirectFromTarget(string $target)
    {
        $parts = explode('_', $target);

        if ($parts[0] == 'campo') {

            if ($parts[1] == 'course') {

                $course_id = (int) $parts[2];

                if (!empty($course = $this->repo()->getCourse($course_id))) {
                    if (!empty($ref_id = $this->dic->fau()->ilias()->objects()->getIliasRefIdForCourse($course))) {
                        $this->dic->ctrl()->redirectToURL(ilLink::_getStaticLink($ref_id));
                    }
                }

                ilUtil::sendFailure($this->lng->txt('campo_course_not_found'), true);
            }
        }
        ilUtil::redirect(\ilUserUtil::getStartingPointAsUrl());
    }

}