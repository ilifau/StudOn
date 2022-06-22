<?php declare(strict_types=1);

namespace FAU\Study;

use FAU\RecordRepo;
use FAU\Study\Data\Module;
use FAU\Study\Data\CourseOfStudy;
use FAU\Study\Data\ModuleCos;
use FAU\RecordData;
use FAU\Study\Data\ModuleEvent;
use FAU\Study\Data\CourseResponsible;
use FAU\Study\Data\Event;
use FAU\Study\Data\EventOrgunit;
use FAU\Study\Data\EventResponsible;
use FAU\Study\Data\IndividualDate;
use FAU\Study\Data\IndividualInstructor;
use FAU\Study\Data\Instructor;
use FAU\Study\Data\PlannedDate;
use FAU\Study\Data\DocProgramme;
use FAU\Study\Data\StudyDegree;
use FAU\Study\Data\StudyEnrolment;
use FAU\Study\Data\StudyField;
use FAU\Study\Data\StudyForm;
use FAU\Study\Data\StudySchool;
use FAU\Study\Data\StudySubject;
use FAU\Study\Data\Course;
use FAU\Study\Data\StudyStatus;
use FAU\Study\Data\StudyType;

/**
 * Repository for accessing data of study related data
 * @todo replace type hints with union types in PHP 8
 */
class Repository extends RecordRepo
{
    /**
     * @return DocProgramme[]
     */
    public function getDocProgrammes() : array
    {
        return $this->getAllRecords(DocProgramme::model());
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
     * @return StudyStatus[]
     */
    public function getStudyStatuses() : array
    {
        return $this->getAllRecords(StudyStatus::model());
    }


    /**
     * @return StudySubject[]
     */
    public function getStudySubjects() : array
    {
        return $this->getAllRecords(StudySubject::model());
    }

    /**
     * @return StudyType[]
     */
    public function getStudyTypes() : array
    {
        return $this->getAllRecords(StudyType::model());
    }

    /**
     * Get a single Doc Programme
     * @return DocProgramme|null
     */
    public function getDocProgramme(string $prog_code, ?DocProgramme $default = null) : ?RecordData
    {
        $query = "SELECT * from fau_study_doc_progs WHERE prog_code = " . $this->db->quote($prog_code, 'text');
        return $this->getSingleRecord($query, DocProgramme::model(), $default);
    }

    /**
     * Get a single Study Degree
     * @return StudyDegree|null
     */
    public function getStudyDegree(int $id, ?StudyDegree $default = null) : ?RecordData
    {
        $query = "SELECT * from fau_study_degrees WHERE degree_his_id = " . $this->db->quote($id, 'integer');
        return $this->getSingleRecord($query, StudyDegree::model(), $default);
    }

    /**
     * Get a single Study Enrolment
     * @return StudyEnrolment|null
     */
    public function getStudyEnrolment(int $id, ?StudyEnrolment $default = null) : ?RecordData
    {
        $query = "SELECT * from fau_study_enrolments WHERE enrolment_id = " . $this->db->quote($id, 'integer');
        return $this->getSingleRecord($query, StudyEnrolment::model(), $default);
    }


    /**
     * Get a single Study Field
     * @return StudyField|null
     */
    public function getStudyField(int $id, ?StudyField $default = null) : ?RecordData
    {
        $query = "SELECT * from fau_study_fields WHERE field_id = " . $this->db->quote($id, 'integer');
        return $this->getSingleRecord($query, StudyField::model(), $default);
    }

    /**
     * Get a single Study Form
     * @return StudyForm|null
     */
    public function getStudyForm(int $id, ?StudyForm $default = null) : ?RecordData
    {
        $query = "SELECT * from fau_study_forms WHERE form_id = " . $this->db->quote($id, 'integer');
        return $this->getSingleRecord($query, StudyForm::model(), $default);
    }


    /**
     * Get a single Study School
     * @return StudySchool|null
     */
    public function getStudySchool(int $id, ?StudySchool $default = null) : ?RecordData
    {
        $query = "SELECT * from fau_study_schools WHERE school_his_id = " . $this->db->quote($id, 'integer');
        return $this->getSingleRecord($query, StudySchool::model(), $default);
    }

    /**
     * Get a single Study Status
     * @return StudyStatus|null
     */
    public function getStudyStatus(int $id, ?StudyStatus $default = null) : ?RecordData
    {
        $query = "SELECT * from fau_study_status WHERE status_his_id = " . $this->db->quote($id, 'integer');
        return $this->getSingleRecord($query, StudyStatus::model(), $default);
    }

    /**
     * Get a single Study Subject
     * @return StudySubject|null
     */
    public function getStudySubject(int $id, ?StudySubject $default = null) : ?RecordData
    {
        $query = "SELECT * from fau_study_subjects WHERE subject_his_id = " . $this->db->quote($id, 'integer');
        return $this->getSingleRecord($query, StudySubject::model(), $default);
    }

    /**
     * Get a single Study Type
     * @return StudyType|null
     */
    public function getStudyType(string $uniquename, ?StudyType $default = null) : ?RecordData
    {
        $query = "SELECT * from fau_study_types WHERE type_uniquename = " . $this->db->quote($uniquename, 'text');
        return $this->getSingleRecord($query, StudyType::model(), $default);
    }


    /**
     * Gat a single Event
     * @return Event|null
     */
    public function getEvent(int $event_id, ?Event $default = null) : ?RecordData
    {
        $query = "SELECT * from fau_study_events WHERE event_id = " . $this->db->quote($event_id, 'integer');
        return $this->getSingleRecord($query, Event::model(), $default);
    }

    /**
     * Gat a single Course
     * @return Course|null
     */
    public function getCourse(int $course_id, ?Course $default = null) : ?RecordData
    {
        $query = "SELECT * from fau_study_courses WHERE course_id = " . $this->db->quote($course_id, 'integer');
        return $this->getSingleRecord($query, Course::model(), $default);
    }

    /**
     * Get the course of a planned date
     * @return Course|null
     */
    public function getCourseOfPlannedDate(int $planned_dates_id, ?Course $default = null) : ?RecordData
    {
        $query = "
            SELECT c.* from fau_study_courses c
            JOIN fau_study_plan_dates p ON p.course_id = c.course_id
            WHERE p.planned_dates_id = " . $this->db->quote($planned_dates_id, 'integer');
        return $this->getSingleRecord($query, Course::model(), $default);
    }

    /**
     * Get the course of an individual date
     * @return Course|null
     */
    public function getCourseOfIndividualDate(int $individual_dates_id, ?Course $default = null) : ?RecordData
    {
        $query = "
            SELECT c.* from fau_study_courses c
            JOIN fau_study_plan_dates p ON p.course_id = c.course_id
            JOIN fau_study_indi_dates i ON i.planned_dates_id = p.planned_dates_id
            WHERE i.individual_dates_id = " . $this->db->quote($individual_dates_id, 'integer');
        return $this->getSingleRecord($query, Course::model(), $default);
    }

    /**
     * Get Modules
     * @param int[]|null $ids  (get all if null, none if empty)
     * @return Module[]
     */
    public function getModules(array $ids = null) : array
    {
        if ($ids === null) {
            return $this->getAllRecords(Module::model());
        }
        elseif (empty($ids)) {
            return [];
        }
        $query = "SELECT * FROM fau_study_modules WHERE "
            . $this->db->in('module_id', $ids, false, 'integer');
        return $this->queryRecords($query, Module::model());
    }

    /**
     * Get Courses of Study
     * @param int[]|null $ids  (get all if null, none if empty)
     * @return CourseOfStudy[]
     */
    public function getCoursesOfStudy(?array $ids = null) : array
    {
        if ($ids === null) {
            return $this->getAllRecords(CourseOfStudy::model());
        }
        elseif (empty($ids)) {
            return [];
        }
        $query = "SELECT * FROM fau_study_cos WHERE "
            . $this->db->in('cos_id', $ids, false, 'integer');
        return $this->queryRecords($query, CourseOfStudy::model());
    }

    /**
     * Get Module to Course of Study assignments
     * @param int[]|null $cos_ids   (get all if null, none if empty)
     * @return ModuleCos[]
     */
    public function getModuleCos(?array $cos_ids = null) : array
    {
        if ($cos_ids === null) {
            return $this->getAllRecords(ModuleCos::model());
        }
        elseif (empty($cos_ids)) {
            return [];
        }
        $query = "SELECT * FROM fau_study_module_cos WHERE "
            . $this->db->in('cos_id', $cos_ids, false, 'integer');
        return $this->queryRecords($query, ModuleCos::model());
    }

    /**
     * Get Module to Event assignments
     * @param int[]|null $event_ids   (get all if null, none if empty)
     * @return ModuleEvent[]
     */
    public function getModuleEvent(?array $event_ids = null) : array
    {
        if ($event_ids === null) {
            return $this->getAllRecords(ModuleEvent::model());
        }
        elseif (empty($event_ids)) {
            return [];
        }
        $query = "SELECT * FROM fau_study_module_cos WHERE "
            . $this->db->in('event_id', $event_ids, false, 'integer');
        return $this->queryRecords($query, ModuleEvent::model());
    }



    /**
     * Save record data of an allowed type
     * @param Course|CourseOfStudy|CourseResponsible|DocProgramme|EventOrgunit|EventResponsible|IndividualDate|IndividualInstructor|Instructor|Module|ModuleCos|ModuleEvent|PlannedDate|StudyDegree|StudyEnrolment|StudyField|StudyForm|StudySchool|StudySubject $record
     */
    public function save(RecordData $record)
    {
        $this->replaceRecord($record);
    }

    /**
     * Delete record data of an allowed type
     * @param Course|CourseOfStudy|CourseResponsible|DocProgramme|EventOrgunit|EventResponsible|IndividualDate|IndividualInstructor|Instructor|Module|ModuleCos|ModuleEvent|PlannedDate|StudyDegree|StudyEnrolment|StudyField|StudyForm|StudySchool|StudySubject $record
     */
    public function delete(RecordData $record)
    {
        $this->deleteRecord($record);
    }
}