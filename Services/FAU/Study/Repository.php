<?php declare(strict_types=1);

namespace FAU\Study;

use FAU\RecordRepo;
use FAU\Study\Data\Module;
use FAU\Study\Data\CourseOfStudy;
use FAU\Study\Data\ModuleCos;
use FAU\RecordData;

/**
 * Repository for accessing FAU user data
 * @todo replace type hints with union types in PHP 8
 */
class Repository extends RecordRepo
{
    /**
     * Save record data of an allowed type
     * @param Module|CourseOfStudy|ModuleCos $record
     */
    public function save(RecordData $record)
    {
        $this->replaceRecord($record);
    }

    /**
     * Delete record data of an allowed type
     * @param Module|CourseOfStudy|ModuleCos $record
     */
    public function delete(RecordData $record)
    {
        $this->deleteRecord($record);
    }
}