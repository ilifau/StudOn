<?php

namespace FAU\Ilias;

use FAU\RecordRepo;

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
        $query = "
			SELECT s.obj_id
			FROM crs_settings s
			INNER JOIN object_reference r ON r.obj_id = s.obj_id
			WHERE r.deleted IS NULL
			AND s.activation_type > 0
			AND (s.activation_start IS NULL OR s.activation_start <= UNIX_TIMESTAMP())
			AND (s.activation_end IS NULL OR s.activation_end >= UNIX_TIMESTAMP())
			AND s.sub_mem_limit > 0
			AND s.sub_max_members > 0
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


}