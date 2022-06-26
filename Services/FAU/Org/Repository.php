<?php declare(strict_types=1);

namespace FAU\Org;

use FAU\RecordRepo;
use FAU\Org\Data\Orgunit;
use FAU\RecordData;

/**
 * Repository for accessing organisational data
 * @todo replace type hints with union types in PHP 8
 */
class Repository extends RecordRepo
{
    /**
     * @param string $fauornr
     * @return Orgunit|null
     */
    public function getOrgunit(int $id) : ?RecordData
    {
        $query = "SELECT * FROM fau_org_orgunits WHERE id = " . $this->db->quote($id, 'integer');
        return $this->getSingleRecord($query, Orgunit::model());
    }

    /**
     * @param string $fauornr
     * @return Orgunit|null
     */
    public function getOrgunitByNumber(string $fauognr) : ?RecordData
    {
        $query = "SELECT * FROM fau_org_orgunits WHERE fauorg_nr = " . $this->db->quote($fauognr, 'text');
        return $this->getSingleRecord($query, Orgunit::model());
    }

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