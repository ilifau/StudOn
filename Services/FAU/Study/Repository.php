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
     * @return StudySubject[]
     */
    public function getStudySubjects() : array
    {
        return $this->getAllRecords(StudySubject::model());
    }


    /**
     * Gat a single Event
     * @return Event|null
     */
    public function getEvent(int $event_id) : ?RecordData
    {
        $query = "SELECT * from fau_study_events WHERE event_id = " . $this->db->quote($event_id, 'integer');
        foreach ($this->queryRecords($query, Event::model()) as $event) {
            return  $event;
        }
        return null;
    }

    /**
     * Gat a single Course
     * @return Course|null
     */
    public function getCourse(int $course_id) : ?RecordData
    {
        $query = "SELECT * from fau_study_course WHERE course_id = " . $this->db->quote($course_id, 'integer');
        foreach ($this->queryRecords($query, Course::model()) as $course) {
            return  $course;
        }
        return null;
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