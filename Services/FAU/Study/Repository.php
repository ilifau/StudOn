<?php declare(strict_types=1);

namespace FAU\Study;

use FAU\RecordRepo;
use FAU\Study\Data\Module;
use FAU\Study\Data\CourseOfStudy;
use FAU\Study\Data\ModuleCos;
use FAU\RecordData;
use FAU\Study\Data\ModuleEvent;
use FAU\Study\Data\EventModule;

/**
 * Repository for accessing FAU user data
 * @todo replace type hints with union types in PHP 8
 */
class Repository extends RecordRepo
{

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
     * @param Module|CourseOfStudy|ModuleCos|ModuleEvent $record
     */
    public function save(RecordData $record)
    {
        $this->replaceRecord($record);
    }

    /**
     * Delete record data of an allowed type
     * @param Module|CourseOfStudy|ModuleCos|ModuleEvent $record
     */
    public function delete(RecordData $record)
    {
        $this->deleteRecord($record);
    }
}