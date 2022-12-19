<?php declare(strict_types=1);

namespace FAU\Cond;

use FAU\Cond\Data\EventRestCos;
use FAU\Cond\Data\EventRestriction;
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
     * @param CosCondition|DocCondition|EventRestriction|ModuleRestriction|Requirement|Restriction $record
     */
    public function save(RecordData $record)
    {
        $this->replaceRecord($record);
    }


    /**
     * Delete record data of an allowed type
     * @param CosCondition|DocCondition|EventRestriction|ModuleRestriction|Requirement|Restriction $record
     */
    public function delete(RecordData $record)
    {
        $this->deleteRecord($record);
    }


    /**
     * Get the restrictions assigned to modules
     * @param int[] $module_id
     * @return HardRestriction[][] (indexed by module_id and restriction name, e.g. 'V01')
     */
    public function getHardRestrictionsOfModules(array $module_ids) : array
    {
        return $this->getHardRestrictionsOfTargets(self::TARGET_MODULE, $module_ids);
    }

    /**
     * Get the restrictions assigned to events
     * @param int[] $event_ids
     * @return HardRestriction[][] (indexed by event_id and restriction name, e.g. 'V01')
     */
    public function getHardRestrictionsOfEvents(array $event_ids) : array
    {
        $restrictions = [];
        foreach($this->getHardRestrictionsOfTargets(self::TARGET_EVENT, $event_ids) as $event_id => $event_restriction) {
            foreach($event_restriction as $restriction) {
                foreach ($this->getEventRestCos($event_id, $restriction->getRestriction()) as $restCos) {
                    if ($restCos->isException()) {
                        $restriction = $restriction->withExceptionCosId($restCos->getCosId());
                    }
                    else {
                        $restriction = $restriction->withRegardingCosId($restCos->getCosId());
                    }
                }
                $restrictions[$event_id][$restriction->getRestriction()] = $restriction;
            }
        }
        return $restrictions;
    }

    /**
     * Get the hard restrictions assigned to specific targets
     * @param string  $target (TARGET_MODULE|TARGET_EVENT)
     * @param int[] $ids
     * @return HardRestriction[][] (indexed by target id and restriction name, e.g. 'V01')
     */
    protected function getHardRestrictionsOfTargets(string $target, array $ids) : array
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

        // requirements can be defined on different levels in campo
        // requirements on the level of a module have the original restriction name, e.g. 'V01'
        // requirements on a higher level have a suffix at the restriction name, indication the steps up, e.g. 'V01-1', 'V01-2', ...
        // requirements on the same level are are combine according to the restriction settings (min, max)
        // requirements on different levels are treated as separate restrictions

        $query = "
            SELECT t.$keyname AS id, t.compulsory AS requirement_compulsory, t.restriction,
            rs.id expression_id, rs.`type`, rs.compare, rs.`number`, rs.compulsory AS expression_compulsory,
            rq.requirement_id, rq.requirement_name
            FROM $tablename t
            JOIN fau_cond_restrictions rs ON t.restriction = rs.restriction OR t.restriction LIKE CONCAT(rs.restriction, '-%')
            LEFT JOIN fau_cond_requirements rq ON rq.requirement_id = t.requirement_id
            WHERE " . $this->db->in($keyname, $ids, false, 'integer');

       $result = $this->db->query($query);

        $restrictions = [];
        while ($row = $this->db->fetchAssoc($result)) {
            if (empty($restriction = $restrictions[$row['id']][$row['restriction']] ?? null)) {
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
            $restrictions[$row['id']][$restriction->getRestriction()] = $restriction;
        }
        return $restrictions;
    }


    /**
     * Get the couse of study relations ffor an event and restrictions
     * @param int $event_id
     * @param string $restriction
     * @return EventRestCos[]
     */
    public function getEventRestCos(int $event_id, string $restriction) : array
    {
        $query = "SELECT * from fau_cond_evt_rest_cos"
            . " WHERE event_id = " . $this->db->quote($event_id, 'integer')
            . " AND restriction = " . $this->db->quote($restriction, 'text');

        return $this->queryRecords($query, EventRestCos::model());
    }


    /**
     * Check if an event has restrictions
     * @param int $event_id
     * @return bool
     */
    public function hasEventRestrictions(int $event_id) : bool
    {
        $query = "SELECT event_id FROM fau_cond_event_rests WHERE event_id = "
            . $this->db->quote($event_id, 'integer')
            . " LIMIT 1";

        return !empty($this->getIntegerList($query, 'event_id'));
    }
}