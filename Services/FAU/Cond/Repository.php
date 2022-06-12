<?php declare(strict_types=1);

namespace FAU\Cond;


use FAU\RecordRepo;
use FAU\RecordData;

/**
 * Repository for accessing FAU user data
 */
class Repository extends RecordRepo
{

    /**
     * Save record data of an allowed type
     */
    public function save(RecordData $record)
    {
        $this->replaceRecord($record);
    }


    /**
     * Delete record data of an allowed type
     */
    public function delete(RecordData $record)
    {
        $this->deleteRecord($record);
    }
}