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
     * @return Orgunit[]
     */
    public function getOrgunitsWithRefId() : array
    {
       $query = "SELECT * FROM fau_org_orgunits WHERE ilias_ref_id IS NOT NULL";
       return $this->queryRecords($query, Orgunit::model(), false);
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