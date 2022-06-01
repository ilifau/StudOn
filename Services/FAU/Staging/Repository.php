<?php

namespace FAU\Staging;

use FAU\Staging\Data\Education;
use FAU\Staging\Data\DipData;
use FAU\RecordRepo;
use FAU\Staging\Data\Module;
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

/**
 * Repository for accessing the staging database
 * @todo: replace 'static' type hints with return types in PHP 8
 */
class Repository extends RecordRepo
{
    /**
     * Set the status of procesed DIP records in the database
     * TRUE in production
     * FALSE in development
     */
    protected bool $setProcessed = false;

    /**
     * @return Achievement[]
     */
    public function getAchievementsToDo() : array
    {
        return $this->getDipRecords(Achievement::model());
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
     * @return Module[]
     */
    public function getModulesToDo() : array
    {
        return $this->getDipRecords(Module::model());
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
     * Get the record objects for DIP table rows with a certain status
     * @return static[]
     */
    public function getDipRecords(DipData $model, string $dip_status = DipData::MARKED) : array
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