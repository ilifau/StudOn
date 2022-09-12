<?php declare(strict_types=1);

namespace FAU\Cond;

use FAU\RecordRepo;
use FAU\RecordData;
use FAU\Cond\Data\ModuleRestriction;
use FAU\Cond\Data\Requirement;
use FAU\Cond\Data\Restriction;
use FAU\Cond\Data\CosCondition;
use FAU\Cond\Data\DocCondition;
use FAU\Cond\Data\HardRestriction;
use FAU\Cond\Data\HardExpression;
use FAU\Cond\Data\HardRequirement;

/**
 * Repository for accessing condition data
 */
class Repository extends RecordRepo
{
    const TARGET_MODULE = 'module';
    const TARGET_EVENT = 'event';

    /**
     * Check if an ILIAs objects has a soft condition defined
     */
    public function checkObjectHasSoftCondition(int $obj_id) : bool
    {
        $query1 = "SELECT 1 FROM fau_cond_cos WHERE ilias_obj_id =" . $this->db->quote($obj_id, 'integer');
        $query2 = "SELECT 1 FROM fau_cond_doc_prog WHERE ilias_obj_id =" . $this->db->quote($obj_id, 'integer');
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
        return $this->getSingleRecord($query, DocCondition::model(), $default);
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

    /**
     * @param int $module_id
     * @return HardRestriction[] (indexed by restriction name, e.g. 'V01')
     */
    public function getHardRestrictionsOfModule(int $module_id) : array
    {
        return $this->getHardRestrictionsOfTarget(self::TARGET_MODULE, $module_id);
    }

    /**
     * @param int $event_id
     * @return HardRestriction[] (indexed by restriction name, e.g. 'V01')
     */
    public function getHardRestrictionsOfEvent(int $event_id) : array
    {
        return $this->getHardRestrictionsOfTarget(self::TARGET_EVENT, $event_id);
    }

    /**
     * @return HardRestriction[] (indexed by restriction name, e.g. 'V01')
     */
    protected function getHardRestrictionsOfTarget(string $target, int $id) : array
    {
        switch ($target) {
            case self::TARGET_MODULE:
                $tablename = 'fau_cond_mod_rests';
                $keyname = 'module_id';
                break;

            case self::TARGET_EVENT:
                $tablename = 'fau_cond_event_rests';
                $keyname = 'event_id';
                break;

            default:
                return [];
        }

        $query = "
            SELECT t.compulsory AS requirement_compulsory,
            rs.id expression_id, rs.restriction, rs.`type`, rs.compare, rs.`number`, rs.compulsory AS expression_compulsory,
            rq.requirement_id, rq.requirement_name
            FROM $tablename t 
            JOIN fau_cond_restrictions rs ON rs.restriction = t.restriction
            LEFT JOIN fau_cond_requirements rq ON rq.requirement_id = t.requirement_id
            WHERE t.$keyname = " . $this->db->quote($id, 'integer');
        $result = $this->db->query($query);

        $restrictions = [];
        while ($row = $this->db->fetchAssoc($result)) {
            if (empty($restriction = $restrictions[$row['restriction']])) {
                $restriction = new HardRestriction(
                    $row['restriction'],
                    $row['type']
                );
            }

            if (!$restriction->hasExpression((int) $row['expression_id'])) {
                $restriction = $restriction->withExpression(new HardExpression(
                    (int) $row['expression_id'],
                    $row['compare'],
                    (int) $row['number'],
                    $row['expression_compulsory']
                ));
            }

            if (!$restriction->hasRequirement((int) $row['requirement_id'])) {
                $restriction = $restriction->withRequirement(new HardRequirement(
                    (int) $row['requirement_id'],
                    $row['requirement_name'],
                    $row['requirement_compulsory']
                ));
            }
            $restrictions[$restriction->getRestriction()] = $restriction;
        }
        return $restrictions;
    }
}