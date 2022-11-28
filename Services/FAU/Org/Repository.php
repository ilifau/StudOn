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
    public function getAssignableOrgunits() : array
    {
        $query = "SELECT * FROM fau_org_orgunits WHERE assignable = 1";
        return $this->queryRecords($query, Orgunit::model(), false);
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
     * @return Orgunit[]
     */
    public function getOrgunitsByRefId(int $ref_id) : array
    {
        $query = "SELECT * FROM fau_org_orgunits WHERE ilias_ref_id =" . $this->db->quote($ref_id, 'integer');
        return $this->queryRecords($query, Orgunit::model(), false);
    }

    /**
     * @param int[] $ref_ids
     * @return Orgunit[]
     */
    public function getOrgunitsByRefIds(array $ref_ids) : array
    {
        $query = "SELECT * FROM fau_org_orgunits WHERE " . $this->db->in('ilias_ref_id', $ref_ids, false,'integer');
        return $this->queryRecords($query, Orgunit::model(), false);
    }

    /**
     * Get the paths of orgunits with ids
     * @param int[] $ids
     * @return string[]
     */
    public function getOrgunitPathsByIds(array $ids, bool $useCache = true) : array
    {
        if (empty($ids)) {
            return [];
        }
        $query = "SELECT path FROM fau_org_orgunits WHERE " . $this->db->in('id', $ids, false, 'integer');
        return $this->getStringList($query, 'path', $useCache);
    }


    /**
     * Get the ids of orgunits within a certain path
     * @return int[]
     */
    public function getOrgunitIdsByPath(string $path, bool $includeParent = true, bool $useCache = true) : array
    {
        $query = "SELECT id FROM fau_org_orgunits"
            . " WHERE path LIKE " . $this->db->quote($path . '.%', 'text')
            . ( $includeParent ? ' OR path = ' . $this->db->quote($path, 'text') : '');
        return $this->getIntegerList($query, 'id', $useCache);
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