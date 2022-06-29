<?php declare(strict_types=1);

namespace FAU\Study;

use ILIAS\DI\Container;
use FAU\Study\Data\Term;
use FAU\Study\Data\Course;

/**
 * Service for study related data
 */
class Service
{
    protected Container $dic;
    protected \ilLanguage $lng;

    protected Repository $repository;
    protected Matching $matching;
    protected Gui $gui;


    /**
     * Constructor
     */
    public function __construct(Container $dic)
    {
        $this->dic = $dic;
        $this->lng = $dic->language();
    }


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
     * Get the GUI Handler
     */
    public function gui() : Gui
    {
        if(!isset($this->gui)) {
            $this->gui = new Gui($this->dic);
        }
        return $this->gui;
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
     * Get the text for a term (current language)
     */
    public function getTermText(?Term $term) : string
    {
        if (!isset($term)) {
            return $this->lng->txt('studydata_unknown_semester');
        }
        elseif ($term->getTypeId() == Term::TYPE_ID_SUMMER) {
            return sprintf($this->lng->txt('studydata_semester_summer'), $term->getYear());
        }
        elseif ($term->getTypeId() == Term::TYPE_ID_WINTER) {
            $next = substr((string) $term->getYear(), 2,2);
            return sprintf($this->lng->txt('studydata_semester_winter'), $term->getYear(), $next);
        }
        else {
            return $this->lng->txt('studydata_ref_semester_invalid');
        }
    }

    /**
     * Get the text for a term in a specific language
     */
    public function getTermTextForLang(?Term $term, string $lang_code) : string
    {
        if (!isset($term)) {
            return $this->lng->txtlng('fau','studydata_unknown_semester', $lang_code);
        }
        elseif ($term->getTypeId() == Term::TYPE_ID_SUMMER) {
            return sprintf($this->lng->txtlng('fau','studydata_semester_summer', $lang_code), $term->getYear());
        }
        elseif ($term->getTypeId() == Term::TYPE_ID_WINTER) {
            $next = substr((string) $term->getYear(), 2,2);
            return sprintf($this->lng->txtlng('fau', 'studydata_semester_winter', $lang_code), $term->getYear(), $next);
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

}