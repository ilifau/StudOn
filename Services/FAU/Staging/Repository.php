<?php

namespace FAU\Staging;

use FAU\RecordRepo;
use FAU\Staging\Data\Education;
use FAU\Staging\Data\DipData;
use FAU\Staging\Data\EventModule;
use FAU\Staging\Data\ModuleCos;
use FAU\Staging\Data\Achievement;
use FAU\Staging\Data\Course;
use FAU\Staging\Data\CourseResponsible;
use FAU\Staging\Data\Event;
use FAU\Staging\Data\EventOrgunit;
use FAU\Staging\Data\EventResponsible;
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

/**
 * Repository for accessing the staging database
 * @todo: replace 'static' type hints with return types in PHP 8
 */
class Repository extends RecordRepo
{
    /**
     * Set the status of processed DIP records in the database
     * TRUE in production
     * FALSE in development
     */
    private bool $setProcessed = false;

    /**
     * Get the identity of a user
     */
    public function getIdentity(string $uid) : ?Identity
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
        return $this->getAllRecords(Identity::model());
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
    public function getCoursesToDo() : array
    {
        return $this->getDipRecords(Course::model());
    }

    /**
     * @return CourseResponsible[]
     */
    public function getCourseResponsiblesToDo() : array
    {
        return $this->getDipRecords(CourseResponsible::model());
    }

    /**
     * @return Education[]
     */
    public function getEducationsToDo() : array
    {
        return $this->getDipRecords(Education::model());
    }

    /**
     * @return Event[]
     */
    public function getEventsToDo() : array
    {
        return $this->getDipRecords(Event::model());
    }

    /**
     * @return EventModule[]
     */
    public function getEventModulesToDo() : array
    {
        return $this->getDipRecords(EventModule::model());
    }

    /**
     * @return EventOrgunit[]
     */
    public function getEventOrgunitsToDo() : array
    {
        return $this->getDipRecords(EventOrgunit::model());
    }

    /**
     * @return EventResponsible[]
     */
    public function getEventResponsiblesToDo() : array
    {
        return $this->getDipRecords(EventResponsible::model());
    }

    /**
     * @return IndividualDate[]
     */
    public function getIndividualDatesToDo() : array
    {
        return $this->getDipRecords(IndividualDate::model());
    }

    /**
     * @return IndividualInstructor[]
     */
    public function getIndividualInstructorsToDo() : array
    {
        return $this->getDipRecords(IndividualInstructor::model());
    }

    /**
     * @return Instructor[]
     */
    public function getInstructorsToDo() : array
    {
        return $this->getDipRecords(Instructor::model());
    }

    /**
     * @return ModuleCos[]
     */
    public function getModuleCosToDo() : array
    {
        return $this->getDipRecords(ModuleCos::model());
    }

    /**
     * @return ModuleRestriction[]
     */
    public function getModuleRestrictionsToDo() : array
    {
        return $this->getDipRecords(ModuleRestriction::model());
    }

    /**
     * @return PlannedDate[]
     */
    public function getPlannedDatesToDo() : array
    {
        return $this->getDipRecords(PlannedDate::model());
    }

    /**
     * @return Restriction[]
     */
    public function getRestrictionsToDo() : array
    {
        return $this->getDipRecords(Restriction::model());
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
     * Get the record objects for DIP table rows with a certain status
     *
     * @return static[]
     */
    private function getDipRecords(DipData $model, string $dip_status = DipData::MARKED) : array
    {
        $query = "SELECT * FROM " . $this->db->quoteIdentifier($model::tableName())
            . " WHERE " . $this->getDipStatusCondition($dip_status)
            . " ORDER BY dip_timestamp ASC ";
        // DIP Records are read once - no caching needed
        return $this->queryRecords($query, $model, false);
    }

    /**
     * @param static $record
     */
    public function setDipProcessed(DipData $record)
    {
        if (!$this->setProcessed) {
            return;
        }

        switch ($record->getDipStatus()) {
            case DipData::INSERTED:
            case DipData::CHANGED:
                $this->updateRecord($record->asProcessed());
                break;
            case DipData::DELETED:
                $this->deleteRecord($record);
        }
    }


    /**
     * Get the SQL condition to query for a DIP status
     */
    private function getDipStatusCondition(string $dip_status) : string
    {
        switch ($dip_status) {
            case DipData::INSERTED:
                return "dip_status = 'inserted'";
            case DipData::CHANGED:
                return "dip_status = 'changed'";
            case DipData::DELETED:
                return "dip_status = 'deleted'";
            case DipData::MARKED:
            default:
                return "dip_status IS NOT NULL";
        }
    }
}