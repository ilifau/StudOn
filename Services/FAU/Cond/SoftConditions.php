<?php

namespace FAU\Cond;

use ILIAS\DI\Container;
use ilCust;
use ilObject;
use FAU\User\Data\Person;
use FAU\Cond\Data\CosCondition;
use FAU\Cond\Data\DocCondition;
use ilDate;
use ILIAS\DI\Exceptions\Exception;
use FAU\Study\Data\Term;
use ilDatePresentation;

/**
 * Handling soft conditions for students' access to StudOn courses and groups
 * These conditions are defined by the course or group admins
 * They prevent a direct registration but allow registration requests if not matching
 */
class SoftConditions
{
    protected Container $dic;
    protected \ilLanguage $lng;
    protected Service $service;
    protected Repository $repo;

    public function __construct (Container $dic)
    {
        $this->dic = $dic;
        $this->lng = $dic->language();
        $this->service = $dic->fau()->cond();
        $this->repo = $dic->fau()->cond()->repo();
    }

    /**
     * Clone the conditions from one ILIAS object to another
     */
    public function cloneConditions(int $from_obj_id, int $to_obj_id)
    {
        foreach ($this->repo->getCosConditionsForObject($from_obj_id) as $condition) {
            $this->repo->save($condition->cloneFor($to_obj_id));
        }

        foreach ($this->repo->getDocConditionsForObject($from_obj_id) as $condition) {
            $this->repo->save($condition->cloneFor($to_obj_id));
        }
    }

    /**
     * Delete the conditions of an object (e.g. if the object is deleted)
     */
    public function deleteConditionsOfObject(int $obj_id)
    {
        foreach ($this->repo->getCosConditionsForObject($obj_id) as $condition) {
            $this->repo->delete($condition);
        }

        foreach ($this->repo->getDocConditionsForObject($obj_id) as $condition) {
            $this->repo->delete($condition);
        }
    }


    /**
     * Get a text describing the conditions
     */
    public function getConditionsAsText(int $obj_id) : string
    {
        $texts = [];

        foreach ($this->repo->getCosConditionsForObject($obj_id) as $cond) {
            $reftext = $this->dic->fau()->study()->getReferenceTermText($cond->getRefTerm());

            $ctext = [];
            if (!empty($subject = $this->dic->fau()->study()->repo()->getStudySubject($cond->getSubjectHisId()))) {
                $ctext[] = $subject->getSubjectTitle($this->lng->getLangKey());
            }
            if (!empty($degree = $this->dic->fau()->study()->repo()->getStudyDegree($cond->getDegreeHisId()))) {
                $ctext[] = $degree->getDegreeTitle($this->lng->getLangKey());
            }
            if (!empty($enrolment = $this->dic->fau()->study()->repo()->getStudyEnrolment($cond->getEnrolmentId()))) {
                $ctext[] = $enrolment->getEnrolmentTitle($this->lng->getLangKey());
            }
            if (!empty($cond->getMinSemester()) && !empty($cond->getMaxSemester())) {
                $ctext[] = sprintf($this->lng->txt('studycond_min_max_semester'), $cond->getMinSemester(), $cond->getMaxSemester(), $reftext);
            }
            elseif (!empty($cond->getMinSemester())) {
                $ctext[] = sprintf($this->lng->txt('studycond_min_semester'), $cond->getMinSemester(), $reftext);
            }
            elseif (!empty($cond->getMaxSemester())) {
                $ctext[] = sprintf($this->lng->txt('studycond_max_semester'), $cond->getMaxSemester(), $reftext);
            }

            $text[] = implode($this->lng->txt('studycond_criteria_delimiter') . ' ', $ctext);
        }

        foreach ($this->repo->getDocConditionsForObject($obj_id) as $cond) {

            $ctext = [];
            if (!empty($program = $this->dic->fau()->study()->repo()->getDocProgramme($cond->getProgCode()))) {
                $ctext[] = $program->getProgText();
            }

            $min_approval_date = $cond->getMinApprovalDate() ? new ilDate($cond->getMinApprovalDate()) : null;
            $max_approval_date = $cond->getMaxApprovalDate() ? new ilDate($cond->getMaxApprovalDate()) : null;

            if (!empty($min_approval_date) && !empty($max_approval_date)) {
                $ctext[] = sprintf(
                    $this->lng->txt('studycond_min_max_approval_date'),
                    ilDatePresentation::formatPeriod($min_approval_date, $max_approval_date)
                );
            }
            elseif (!empty($min_approval_date)) {
                $ctext[] = sprintf($this->lng->txt('studycond_min_approval_date'), ilDatePresentation::formatDate($min_approval_date));
            }
            elseif (!empty($max_approval_date)) {
                $ctext[] = sprintf($this->lng->txt('studycond_max_approval_date'), ilDatePresentation::formatDate($max_approval_date));
            }

            $texts[] = implode($this->lng->txt('studycond_criteria_delimiter') . ' ', $ctext);
        }

        if (count($texts)) {
            return implode($this->lng->txt('studycond_condition_delimiter') . ' ', $texts);
        } else {
            return $this->lng->txt('studycond_no_condition_defined');
        }
    }


    /**
     * Check study data based access conditions
     *
     * This check is called from ilRbacSystem->checkAccessOfUser() for "read" operations
     * A positive result will overrule the rbac restrictions
     * Therefore this check requires a condition to exist and being fulfilled
     */
    public function checkAccess(int $ref_id, int $user_id) : bool
    {
        // Performance improvement
        // only check a few objects which are listed in the custom config
        $ref_ids = explode(',', ilCust::get('studydata_check_ref_ids'));
        if (!in_array($ref_id, $ref_ids)) {
            return false;
        }
        return $this->check(ilObject::_lookupObjId($ref_id), $user_id);
    }


    /**
     * Check if conditions are fulfilled
     */
    public function check(int $obj_id, int $user_id) : bool
    {
        // no person data found => no member of fau
        if (empty($person = $this->dic->fau()->user()->repo()->getPersonOfUser($user_id))) {
            return false;
        }

        $cos_conditions = $this->repo->getCosConditionsForObject($obj_id);
        $doc_conditions = $this->repo->getDocConditionsForObject($obj_id);

        // no conditions given => check passed
        if (empty($cos_conditions) && empty($doc_conditions)) {
            return true;
        }

        // one course of study condition fits => check passed
        foreach ($cos_conditions as $condition) {
            if ($this->checkCosCondition($condition, $person)) {
                return true;
            }
        }

        // one doc programme condition fits => check passed
        foreach ($doc_conditions as $condition) {
            if ($this->checkDocCondition($condition, $person)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if the course of study condition is fulfilled
     * @todo check school
     */
    protected function checkCosCondition(CosCondition $condition, Person $person) : bool
    {
        $term = $condition->getRefTerm();
        if (!isset($term) || !$term->isValid()) {
            $term = $this->dic->fau()->study()->getCurrentTerm();
        }

        // check the criteria for each study
        // all defined criteria must be satisfied
        // continue with next study on failure
        foreach ($person->getStudiesOfTerm($term) as $study) {

            // check school
            // todo

            // check degree
            if (!empty($condition->getDegreeHisId())
                && (empty($study->getDegreeDbId()) || $study->getDegreeDbId() != $condition->getDegreeHisId())
            ) {
                //log_line('degree_failed');
                continue;   // failed
            }

            // check enrolment
            if (!empty($condition->getEnrolmentId())
                && (empty($study->getEnrollmentDbId()) || $study->getEnrollmentDbId() != $condition->getEnrolmentId())
            ) {
                //log_line('enrolment_failed');
                continue;   // failed
            }

            // check subjects and semester
            // only one subject/semester combination must fit
            $subject_semester_passed = false;
            foreach ($study->getSubjects() as $subject) {

                if (!empty($condition->getSubjectHisId())
                    && (empty($subject->getSubjectDbId() || $subject->getSubjectDbId() != $condition->getSubjectHisId()))
                ) {
                    //log_line('subject_failed');
                    continue; // failed;
                }

                if (!empty($condition->getMinSemester())
                    && (empty($subject->getStudySemester() || $subject->getStudySemester() < $condition->getMinSemester()))
                ) {
                    //log_line('min_semester_failed');
                    continue; // failed;
                }

                if (!empty($condition->getMaxSemester())
                    && (empty($subject->getStudySemester() || $subject->getStudySemester() > $condition->getMaxSemester()))
                ) {
                    //log_line('max_semester_failed');
                    continue; // failed;
                }

                // this subject/semester combination fits
                $subject_semester_passed = true;
                //log_line('subject_semester_passed');
                break;
            }

            // this study fits
            if ($subject_semester_passed) {
                return true;
            }

        }
        // none of the studies fits
        //log_line('none fits');
        return false;
    }

    /**
     * Check if the doc programme condition is fulfilled
     */
    protected function checkDocCondition(DocCondition $condition, Person $person) : bool
    {
        if (!empty($condition->getProgCode())
            && (empty($person->getDocProgrammesCode()) || $person->getDocProgrammesCode() != $condition->getProgCode())
        ) {
            return false;
        }

        if (!empty($condition->getMinApprovalDate())) {
           if (empty($person->getDocApprovalDate())) {
               return false;
           }
           try {
               $minData = new ilDate($condition->getMinApprovalDate(), IL_CAL_DATE);
               $docDate =new ilDate($person->getDocApprovalDate(), IL_CAL_DATE);
               if (ilDate::_before($docDate, $minData)) {
                   return false;
               }
           }
           catch (Exception $e) {
                return false;
           }
        }

        if (!empty($condition->getMaxApprovalDate())) {
            if (empty($person->getDocApprovalDate())) {
                return false;
            }
            try {
                $maxData = new ilDate($condition->getMaxApprovalDate(), IL_CAL_DATE);
                $docDate =new ilDate($person->getDocApprovalDate(), IL_CAL_DATE);
                if (ilDate::_after($docDate, $maxData)) {
                    return false;
                }
            }
            catch (Exception $e) {
                return false;
            }
        }

        // the doc programme fits
        return true;
    }
}