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
     * Check if an ILIAs objects has a soft condition defined
     */
    public function checkObjectHasSoftCondition(int $obj_id) : bool
    {
        $query1 = "SELECT 1 FROM fau_cond_cos WHERE obj_id =" . $this->db->quote($obj_id, 'integer');
        $query2 = "SELECT 1 FROM fau_cond_doc_prog WHERE obj_id =" . $this->db->quote($obj_id, 'integer');
        return $this->hasRecord($query1) || $this->hasRecord($query2);
    }

    /**
     * Get a single course of study condition
     * @return ?CosCondition
     */
    public function getCosCondition(int $id, ?CosCondition $default = null) : ?RecordData
    {
        $query = "SELECT * FROM fau_cond_cos WHERE id = " . $this->db->quote($id, 'integer');
        return $this->getSingleRecord($query, CosCondition::model(), $default);
    }

    /**
     * Get a single doc program condition
     * @return ?DocCondition
     */
    public function getDocCondition(int $id, ?DocCondition $default = null) : ?RecordData
    {
        $query = "SELECT * FROM fau_cond_doc_prog WHERE id = " . $this->db->quote($id, 'integer');
        return $this->getSingleRecord($query, CosCondition::model(), $default);
    }


    /**
     * Get the course of study conditions for an ilias object
     * @return CosCondition[]
     */
    public function getCosConditionsForObject(int $ilias_obj_id) : array
    {
        $query = "SELECT * FROM fau_cond_cos WHERE ilias_obj_id = " . $this->db->quote($ilias_obj_id, 'integer');
        return $this->queryRecords($query, CosCondition::model());
    }


    /**
     * Get the doc program conditions for an ilias object
     * @return DocCondition[]
     */
    public function getDocConditionsForObject(int $ilias_obj_id) : array
    {
        $query = "SELECT * FROM fau_cond_doc_prog WHERE ilias_obj_id = " . $this->db->quote($ilias_obj_id, 'integer');
        return $this->queryRecords($query, DocCondition::model());
    }


    /**
     * Save record data of an allowed type
     * @param CosCondition|DocCondition|ModuleRestriction|Requirement|Restriction $record
     */
    public function save(RecordData $record)
    {
        $this->replaceRecord($record);
    }


    /**
     * Delete record data of an allowed type
     * @param CosCondition|DocCondition|ModuleRestriction|Requirement|Restriction $record
     */
    public function delete(RecordData $record)
    {
        $this->deleteRecord($record);
    }
}