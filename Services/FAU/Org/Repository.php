<?php declare(strict_types=1);

namespace FAU\Org;

use FAU\RecordRepo;
use FAU\Org\Data\Orgunit;
use FAU\RecordData;

/**
 * Repository for accessing FAU user data
 */
class Repository extends RecordRepo
{
    /**
     * @return Orgunit[]
     */
    public function getOrgunits() : array
    {
        return $this->getAllRecords(Orgunit::model());
    }

    /**
     * Save record data of an allowed type
     * @param Orgunit $record
     */
    public function save(RecordData $record)
    {
        $this->replaceRecord($record);
    }

    /**
     * Delete record data of an allowed type
     * @param Orgunit $record
     */
    public function delete(RecordData $record)
    {
        $this->deleteRecord($record);
    }


}