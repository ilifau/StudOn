<?php

namespace FAU\Staging;

use FAU\RecordRepo;
use FAU\Staging\Data\Education;
use FAU\Staging\Data\EventModule;
use FAU\Staging\Data\ModuleCos;
use FAU\Staging\Data\Achievement;
use FAU\Staging\Data\Course;
use FAU\Staging\Data\CourseResponsible;
use FAU\Staging\Data\Event;
use FAU\Staging\Data\EventOrgunit;
use FAU\Staging\Data\EventResponsible;
use FAU\Staging\Data\EventRestriction;
use FAU\Staging\Data\IndividualDate;
use FAU\Staging\Data\IndividualInstructor;
use FAU\Staging\Data\Instructor;
use FAU\Staging\Data\ModuleRestriction;
use FAU\Staging\Data\PlannedDate;
use FAU\Staging\Data\Restriction;
use FAU\Staging\Data\DocProgramme;
use FAU\Staging\Data\Exam;
use FAU\Staging\Data\ExamExaminer;
use FAU\Staging\Data\ExamParticipant;
use FAU\Staging\Data\Orgunit;
use FAU\Staging\Data\StudyDegree;
use FAU\Staging\Data\StudyEnrolment;
use FAU\Staging\Data\StudyField;
use FAU\Staging\Data\StudyForm;
use FAU\Staging\Data\StudySchool;
use FAU\Staging\Data\StudySubject;
use FAU\Staging\Data\Identity;
use FAU\Staging\Data\StudOnMember;
use FAU\Staging\Data\StudOnCourse;
use FAU\Study\Data\Term;
use FAU\RecordData;
use FAU\Staging\Data\StudyTerm;

/**
 * Repository for accessing the staging database
 * @todo: replace 'static' type hints with return types in PHP 8
 */
class Repository extends RecordRepo
{
    /**
     * Get the identity of a user
     */
    public function getIdentity(?string $uid) : ?Identity
    {
        $query = "SELECT * from identities WHERE pk_persistent_id = " . $this->db->quote($uid, 'text');
        /** @var Identity $identity */
       foreach ($this->queryRecords($query, Identity::model()) as $identity) {
           return $identity;
       }
        return null;
    }

    /**
     * @return Identity[]
     */
    public function getIdentities() : array
    {
        return $this->getAllRecords(Identity::model(), false);
    }
    
    /**
     * @return Achievement[]
     */
    public function getAchievements() : array
    {
        return $this->getAllRecords(Achievement::model(), false);
    }

    /**
     * @return Course[]
     */
    public function getCourses() : array
    {
        return $this->getAllRecords(Course::model(), false);
    }

    /**
     * @return CourseResponsible[]
     */
    public function getCourseResponsibles() : array
    {
        return $this->getAllRecords(CourseResponsible::model(), false);
    }

    /**
     * @return Education[]
     */
    public function getEducations() : array
    {
        return $this->getAllRecords(Education::model(), false);
    }

    /**
     * @return Event[]
     */
    public function getEvents() : array
    {
        return $this->getAllRecords(Event::model(), false);
    }

    /**
     * @return string[]
     */
    public function getEventTypeValues() : array
    {
        $query = "SELECT DISTINCT eventtype FROM campo_event";
        return $this->getStringList($query, 'eventtype', false);
    }


    /**
     * @return EventModule[]
     */
    public function getEventModules() : array
    {
        return $this->getAllRecords(EventModule::model(), false);
    }

    /**
     * @return EventOrgunit[]
     */
    public function getEventOrgunits() : array
    {
        return $this->getAllRecords(EventOrgunit::model(), false);
    }

    /**
     * @return EventResponsible[]
     */
    public function getEventResponsibles() : array
    {
        return $this->getAllRecords(EventResponsible::model(), false);
    }

    /**
     * @return IndividualDate[]
     */
    public function getIndividualDates() : array
    {
        return $this->getAllRecords(IndividualDate::model(), false);
    }

    /**
     * @return IndividualInstructor[]
     */
    public function getIndividualInstructors() : array
    {
        return $this->getAllRecords(IndividualInstructor::model(), false);
    }

    /**
     * @return Instructor[]
     */
    public function getInstructors() : array
    {
        return $this->getAllRecords(Instructor::model(), false);
    }

    /**
     * @return ModuleCos[]
     */
    public function getModuleCos() : array
    {
        return $this->getAllRecords(ModuleCos::model(), false);
    }

    /**
     * @return ModuleRestriction[]
     */
    public function getModuleRestrictions() : array
    {
        return $this->getAllRecords(ModuleRestriction::model(), false);
    }

    /**
     * @return EventRestriction[]
     */
    public function getEventRestrictions() : array
    {
        return $this->getAllRecords(EventRestriction::model(), false);
    }

    /**
     * @return PlannedDate[]
     */
    public function getPlannedDates() : array
    {
        return $this->getAllRecords(PlannedDate::model(), false);
    }

    /**
     * @return Restriction[]
     */
    public function getRestrictions() : array
    {
        return $this->getAllRecords(Restriction::model(), false);
    }

    /**
     * @return DocProgramme[]
     */
    public function getDocProgrammes() : array
    {
        return $this->getAllRecords(DocProgramme::model());
    }

    /**
     * @return Exam[]
     */
    public function getExams() : array
    {
        return $this->getAllRecords(Exam::model());
    }

    /**
     * Get the examiners of an exam
     * @return ExamExaminer[]
     */
    public function getExamExaminers(string $porgnr) : array
    {
        $query = "SELECT * FROM campo_exam_examiner WHERE porgnr = " . $this->db->quote($porgnr, 'text');
        return $this->queryRecords($query, ExamExaminer::model());
    }

    /**
     * Get the participants of an exam
     * @return ExamParticipant[]
     */
    public function getExamParticipants(string $porgnr) : array
    {
        $query = "SELECT * FROM campo_exam_participant WHERE porgnr = " . $this->db->quote($porgnr, 'text');
        return $this->queryRecords($query, ExamParticipant::model());
    }

    /**
     * @return Orgunit[]
     */
    public function getOrgunits() : array
    {
        return $this->getAllRecords(Orgunit::model());
    }

    /**
     * @return StudyDegree[]
     */
    public function getStudyDegrees() : array
    {
        return $this->getAllRecords(StudyDegree::model());
    }

    /**
     * @return StudyEnrolment[]
     */
    public function getStudyEnrolments() : array
    {
        return $this->getAllRecords(StudyEnrolment::model());
    }

    /**
     * @return StudyField[]
     */
    public function getStudyFields() : array
    {
        return $this->getAllRecords(StudyField::model());
    }

    /**
     * @return StudyForm[]
     */
    public function getStudyForms() : array
    {
        return $this->getAllRecords(StudyForm::model());
    }

    /**
     * @return StudySchool[]
     */
    public function getStudySchools() : array
    {
        return $this->getAllRecords(StudySchool::model());
    }

    /**
     * @return StudySubject[]
     */
    public function getStudySubjects() : array
    {
        return $this->getAllRecords(StudySubject::model());
    }

    /**
     * @return StudyTerm[]
     */
    public function getStudyTerms() : array
    {
        return $this->getAllRecords(StudyTerm::model());
    }

    /**
     * @return StudOnMember[]
     */
    public function getStudOnMembers(Term $term) : array
    {
        $query = "SELECT * FROM studon_members WHERE term_year=" . $this->db->quote($term->getYear(), 'integer')
            . " AND term_type_id=" . $this->db->quote($term->getTypeId(), 'integer');
        return $this->queryRecords($query, StudOnMember::model(), false, true);
    }

    /**
     * @return StudOnMember[]
     */
    public function getStudOnMembersOfCourse(int $course_id) : array
    {
        $query = "SELECT * FROM studon_members WHERE course_id=" . $this->db->quote($course_id, 'integer');
        return $this->queryRecords($query, StudOnMember::model(), false, true);
    }


    /**
     * Get a single StudOnCourse for back sync
     * @param int $course_id
     * @return StudOnCourse|null
     */
    public function getStudOnCourse(int $course_id) : ?RecordData
    {
        $query = "SELECT * FROM studon_courses WHERE course_id=" . $this->db->quote($course_id, 'integer');
        return $this->getSingleRecord($query, StudOnCourse::model(), null, false);
    }

    /**
     * @return StudOnCourse[]
     */
    public function getStudOnCourses(Term $term) : array
    {
        $query = "SELECT * FROM studon_courses WHERE term_year=" . $this->db->quote($term->getYear(), 'integer')
            . " AND term_type_id=" . $this->db->quote($term->getTypeId(), 'integer');
        return $this->queryRecords($query, StudOnCourse::model(), false, true);
    }


    /**
     * Save record data of an allowed type
     * @param StudOnMember|StudOnCourse $record
     */
    public function save(RecordData $record)
    {
        $this->replaceRecord($record);
    }

    /**
     * Delete record data of an allowed type
     * @param StudOnMember|StudOnCourse $record
     */
    public function delete(RecordData $record)
    {
        $this->deleteRecord($record);
    }

}