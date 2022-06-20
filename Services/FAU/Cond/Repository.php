<?php declare(strict_types=1);

namespace FAU\Cond;


use FAU\RecordRepo;
use FAU\RecordData;
use FAU\Cond\Data\ModuleRestriction;
use FAU\Cond\Data\Requirement;
use FAU\Cond\Data\Restriction;
use FAU\Cond\Data\CosCondition;
use FAU\Cond\Data\DocCondition;

/**
 * Repository for accessing condition data
 */
class Repository extends RecordRepo
{

    /**
     * Get the course of study conditions for an ilias object
     */
    public function getCosConditions(int $ilias_obj_id)
    {
        $query = "SELECT * FROM fau_cond_cos WHERE ilias_obj_id = " . $this->db->quote($ilias_obj_id, 'integer');
        return $this->queryRecords($query, CosCondition::model());
    }


    /**
     * Get the doc program conditions for an ilias object
     */
    public function getDocConditions(int $ilias_obj_id)
    {
        $query = "SELECT * FROM fau_cond_doc_prog WHERE ilias_obj_id = " . $this->db->quote($ilias_obj_id, 'integer');
        return $this->queryRecords($query, DocCondition::model());
    }


    /**
     * Save record data of an allowed type
     * @param ModuleRestriction|Requirement|Restriction $record
     */
    public function save(RecordData $record)
    {
        $this->replaceRecord($record);
    }


    /**
     * Delete record data of an allowed type
     * @param ModuleRestriction|Requirement|Restriction $record
     */
    public function delete(RecordData $record)
    {
        $this->deleteRecord($record);
    }
}