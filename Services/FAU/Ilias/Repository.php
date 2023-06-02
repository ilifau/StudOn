<?php

namespace FAU\Ilias;

use FAU\Ilias\Data\ContainerData;
use FAU\RecordRepo;
use FAU\Study\Data\ImportId;
use FAU\Study\Data\Term;

/**
 * Repository for accessing ilias data
 */
class Repository extends RecordRepo
{
    protected \ilDBInterface $db;
    protected \ilLogger $logger;

    /**
     * Constructor
     */
    public function __construct(\ilDBInterface $a_db, \ilLogger $logger)
    {
        parent::__construct($a_db, $logger);
    }

    /**
     * Find ids of courses that can be autofilled after the fair subscription time
     * @return int[]	object ids
     */
    public function findFairAutoFillCourseIds() : array
    {
        // find all course ids with a finished fair period in the last month
        // that are not filled or last filled before the fair period
        //
        // paraSub: don't restrict to courses with max members because max members may be defined in the parallel groups
        // Details are checked in \FAU\Ilias\Registration::doAutoFill()
        $query = "
			SELECT s.obj_id
			FROM crs_settings s
			INNER JOIN object_reference r ON r.obj_id = s.obj_id
			WHERE r.deleted IS NULL
			AND s.activation_type > 0
			AND (s.activation_start IS NULL OR s.activation_start <= UNIX_TIMESTAMP())
			AND (s.activation_end IS NULL OR s.activation_end >= UNIX_TIMESTAMP())
			AND s.sub_auto_fill > 0
			AND s.sub_fair > (UNIX_TIMESTAMP() - 3600 * 24 * 30)
			AND s.sub_fair < UNIX_TIMESTAMP()
			AND (s.sub_last_fill IS NULL OR s.sub_last_fill < s.sub_fair)
		";

        return $this->getIntegerList($query, 'obj_id', false);
    }

    /**
     * Find ids of groups that can be autofilled after the fair subscription time
     * @return int[]	object ids
     */
    public function findFairAutoFillGroupIds() : array
    {
        // find all groups with a finished fair period in the last month
        // that are not filled or last filled before the fair period
        // paraSub: parallel groups inside campo courses are ignored here because they don't have an autofill setting
        // Details are checked in \FAU\Ilias\Registration::doAutoFill()
        $query = "
			SELECT s.obj_id
			FROM grp_settings s
			INNER JOIN object_reference r ON r.obj_id = s.obj_id
			WHERE r.deleted IS NULL
			AND s.grp_type <> 1
			AND registration_mem_limit > 0
			AND registration_max_members > 0
			AND s.sub_auto_fill > 0
			AND s.sub_fair > (UNIX_TIMESTAMP() - 3600 * 24 * 30)
			AND sub_fair < UNIX_TIMESTAMP()
			AND (s.sub_last_fill IS NULL OR s.sub_last_fill < s.sub_fair)
		";

        return $this->getIntegerList($query, 'obj_id', false);
    }

    /**
     * Get the id of groups with a subscription by a user
     * @param int   $user_id
     * @param int[] $obj_ids
     * @return int[]
     */
    public function getSubscribedObjectIds(int $user_id, array $obj_ids) : array
    {
        $query = "
            SELECT obj_id
            FROM crs_waiting_list
            WHERE usr_id = " . $this->db->quote($user_id, 'integer') . "
            AND " . $this->db->in('obj_id', $obj_ids, false, 'integer');

        return $this->getIntegerList($query, 'obj_id', false);
    }

    /**
     * Change the role assignments from one role to another
     */
    public function changeRoleAssignments(int $from_role_id, int $to_role_id)
    {
        $query = "UPDATE rbac_ua SET rol_id=" . $this->db->quote($to_role_id, 'integer')
            . " WHERE rol_id=" . $this->db->quote($from_role_id, 'integer');
        $this->db->manipulate($query);
    }

    /**
     * Copy the waiting list from one object to another
     */
    public function copyWaitingList(int $from_obj_id, int $to_obj_id)
    {
        $query = "
            REPLACE INTO crs_waiting_list(obj_id, usr_id, sub_time, subject, to_confirm, module_id)
            SELECT %s, usr_id, sub_time, subject, to_confirm, module_id FROM crs_waiting_list
            WHERE obj_id = %s";
        $this->db->manipulateF($query, ['integer', 'integer'], [$to_obj_id, $from_obj_id]);
    }

    /**
     * Clear the waiting list of an object
     */
    public function clearWaitingList(int $from_obj_id)
    {
        $query = "DELETE FROM crs_waiting_list WHERE obj_id = %s";
        $this->db->manipulateF($query, ['integer'], [$from_obj_id]);
    }


    /**
     * Get the user ids of course or group members
     * @param int[] $obj_ids
     * @return int[][] user_id => obj_ids
     */
    public function getObjectsMemberIds(array $obj_ids) : array
    {
        $query = "
            SELECT r.obj_id, ua.usr_id
            FROM object_reference r 
            JOIN rbac_fa fa ON fa.parent = r.ref_id AND fa.assign = 'y'
            JOIN object_data o ON o.obj_id = fa.rol_id AND (o.title LIKE 'il_crs_member%' OR o.title LIKE 'il_grp_member%')
            JOIN rbac_ua ua ON ua.rol_id = fa.rol_id
            WHERE " . $this->db->in('r.obj_id', $obj_ids, false, 'integer');
        $result = $this->db->query($query);

        $list = [];
        while ($row = $this->db->fetchAssoc($result)) {
            $list[$row['usr_id']][] = $row['obj_id'];
        }
        return $list;
    }


    /**
     * Get the user ids of users on course or group waiting lists
     * @param int[] $obj_ids
     * @return int[][] user_id => obj_ids
     */
    public function getObjectsWaitingIds(array $obj_ids) : array
    {
        $query = "
            SELECT obj_id, usr_id FROM crs_waiting_list
            WHERE " . $this->db->in('obj_id', $obj_ids, false, 'integer');
        $result = $this->db->query($query);

        $list = [];
        while ($row = $this->db->fetchAssoc($result)) {
            $list[$row['usr_id']][] = $row['obj_id'];
        }
        return $list;
    }


    /**
     * Get the path of a node in the repository treen
     */
    public function getTreePath(int $ref_id) : ?string
    {
        $query = "SELECT path from tree WHERE child = " . $this->db->quote($ref_id, 'integer');
        $result = $this->db->query($query);
        if ($row = $this->db->fetchAssoc($result)) {
            return (string) $row['path'];
        }
        else {
            return null;
        }
    }


    /**
     * fins the data of courses or groups within a parent
     * @return ContainerData[] (indexed by object id)
     */
    public function findCoursesOrGroups(int $parent_ref_id, ?Term $term = null, $with_groups = true) : array
    {
        $containers = [];

        if (empty($path = $this->getTreePath($parent_ref_id))) {
            return [];
        }

        $query = "SELECT r.ref_id, o.*
            FROM tree t
            JOIN object_reference r ON r.ref_id = t.child
            JOIN object_data o ON o.obj_id = r.obj_id
            WHERE " . $this->db->like('t.path', 'text', $path . '%', false);

        if (isset($term) && $term->isValid()) {
            $query .= "AND " . $this->db->like('o.import_id', 'text', 'FAU/Term=' . $term->toString() . '%', false);
        }

        if ($with_groups) {
            $query .= " AND o.type in ('crs', 'grp')";
        }
        else {
            $query .= " AND o.type = 'crs'";
        }


        $result = $this->db->query($query);
        while ($row = $this->db->fetchAssoc($result)) {
            $containers[$row['obj_id']] = new ContainerData(
                (string) $row['title'],
                (string) $row['description'],
                (string) $row['type'],
                (int) $row['ref_id'],
                (int) $row['obj_id'],
                ImportId::fromString($row['import_id'])
            );
        }

        return $containers;
    }
}