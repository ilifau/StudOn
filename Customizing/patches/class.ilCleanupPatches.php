<?php
/**
 * fim: [cust] specific patches.
 */
class ilCleanupPatches
{
	/**
	 * Move unused images from the media objects folder
	 */
	public function moveDeletedMediaObjects($params = array('keep_deleted_after' => '2015-01-01 00:00:00'))
	{
		require_once "./Services/MediaObjects/classes/class.ilObjMediaObject.php";

		$mobdir = "/nfs/iliasdata/studon/webdata/StudOn/mobs" ;
		$movedir = "/nfs/iliasdata/studon/webdata/StudOn/mobs_deleted";
		$keep_deleted_after = $params["keep_deleted_after"];

		$objects = 0;
		$unused = 0;
		$used = 0;

		$dh = opendir($mobdir);
		while ($filename = readdir($dh))
		{
			if (substr($filename,0,3) != 'mm_') {
				continue;
			}

			$objects++;

			$mob_id = (int) substr($filename,3);
			$usages = ilObjMediaObject::lookupUsages($mob_id, true);

			$needed = false;
			foreach($usages as $usage)
			{
				// uncomment this if you just want to move the unused mobs
				$needed = true;
				break;

				$obj_id = ilObjMediaObject::getParentObjectIdForUsage($usage, true);

				switch ($usage['type'])
				{
					// category, course, group, folder pages
					case 'cat:pg':
					case 'crs:pg':
					case 'grp:pg':
					case 'fold:pg':
					// blog, learning module, wiki pages
					case 'blp:pg':
					case 'lm:pg':
					case 'wpg:pg':
						if ($this->checkIsNeeded($obj_id, $keep_deleted_after))
						{
							$needed = true;
							break 2;
						}
						break;

					// test questions
					case 'qpl:pg':
					case 'qpl:html':

						if ($this->checkIsNeeded($obj_id, $keep_deleted_after))
						{
							$needed = true;
							break 2;
						}

						$obj_type = ilObject::_lookupType($obj_id);
						if ($obj_type == 'qpl')
						{
							// give access if question pool is used by readable test
							// for random selection of questions
							include_once('./Modules/Test/classes/class.ilObjTestAccess.php');
							$tests = ilObjTestAccess::_getRandomTestsForQuestionPool($obj_id);
							foreach ($tests as $test_id)
							{
								if ($this->checkIsNeeded($test_id, $keep_deleted_after))
								{
									$needed = true;
									break 3;
								}
							}
						}
						break;

					// other usage type: assume needed
					default:
						$needed = true;
						break 2;
				}
			}

			if ($needed)
			{
				$used++;
			}
			else
			{
				$unused++;
				echo "$objects $filename: unused\n";

				rename ($mobdir."/".$filename, $movedir."/".$filename);

			}
		}
		closedir($dh);


		echo "Objects: $objects \n";
		echo "Unused: $unused \n";
		echo "Used: $used \n";
	}

	/**
	 * Delete old page history entries
	 * Afterwards a moveDeletedMediaObjects should be called
	 */
	public function deleteOldPageHistory($params = array('delete_until' => '2015-01-01 00:00:00'))
	{
		global $ilDB;

		$usages = 0;

		$query = "SELECT page_id, parent_type, hdate, nr FROM page_history "
				." WHERE hdate < " . $ilDB->quote($params['delete_until'], 'date')
				." AND parent_type <> 'wpg'"
				." ORDER BY hdate ASC";
		$result = $ilDB->query($query);

		echo "Delete media object usages for old page history ...";
		while ($row = $ilDB->fetchAssoc($result))
		{
			$query2 = "DELETE FROM mob_usage "
					. " WHERE usage_type = ". $ilDB->quote($row['parent_type'].':pg', 'text')
					. " AND usage_id = " . $ilDB->quote($row['page_id'], 'integer')
					. " AND usage_hist_nr = " . $ilDB->quote($row['nr'], 'integer');
			$rows = $ilDB->manipulate($query2);

			echo "Page " . $row["page_id"]. " ", $row['parent_type'] . " " . $row["hdate"] . ": ". $rows . " \n";
			$usages += $rows;
		}

		echo "Delete old page history entries ...\n";
		$query3 = "DELETE FROM page_history "
			." WHERE hdate < " . $ilDB->quote($params['delete_until'], 'date')
			." AND parent_type <> 'wpg'";
		$entries = $ilDB->manipulate($query3);

		echo "Deleted mob usages: ". $usages . "\n";
		echo "Deleted history entries: ". $entries . "\n";
	}


	/**
	 * check if an object is still needed (untrashed references exists)
	 * @param  integer	$obj_id			object id (all references are tested)
	 * @param	string	$deleted_after	objects with later deletion time are still neded
	 * @return boolean
	 */
	private function checkIsNeeded($obj_id, $deleted_after)
	{
		global $ilDB;
		static $checked = array();

		// looukup in cache
		if (isset($checked[$obj_id]))
		{
			return $checked[$obj_id];
		}

		// assume deleted
		$checked[$obj_id] = false;

		// check references in repository
		$query =
			"SELECT COUNT(*) AS refs FROM object_reference"
			." WHERE (deleted IS NULL OR deleted = '0000-00-00 00:00:00' OR deleted >". $ilDB->quote($deleted_after, 'date') . ")"
			." AND obj_id = ". $ilDB->quote($obj_id, 'integer');

	//	echo $query. "\n";

		$result = $ilDB->query($query);
		$row = $ilDB->fetchAssoc($result);
		if ($row['refs'] > 0)
		{
			$checked[$obj_id] = true;
		}

		// check references in personal workspace
		$query =
			"SELECT COUNT(*) AS refs FROM object_reference_ws"
			." WHERE (deleted IS NULL OR deleted = '0000-00-00 00:00:00' OR deleted >". $ilDB->quote($deleted_after, 'date') . ")"
			." AND obj_id = ". $ilDB->quote($obj_id, 'integer');

		$result = $ilDB->query($query);
		$row = $ilDB->fetchAssoc($result);
		if ($row['refs'] > 0)
		{
			$checked[$obj_id] = true;
		}

		// return checked value
		return $checked[$obj_id];
	}



    public function checkDoublePermissionTemplates($params = array('cleanup' => false, 'min'=> 2))
    {
        global $ilDB;

        $doubles = array();
        $doubles['>=2'] = 0;
        $doubles['>=100'] = 0;
        $doubles['>=1000'] = 0;
        $doubles['>=10000'] = 0;
        $doubles['>=100000'] = 0;

        $query1 = "SELECT DISTINCT parent FROM rbac_templates";
        $result1 = $ilDB->query($query1);

        while ($row1 = $ilDB->fetchAssoc($result1))
        {
            $query2 = "
                SELECT rol_id, `type`, ops_id, parent, count(*) AS num
                FROM rbac_templates
                WHERE parent = %s
                GROUP BY rol_id, `type`, ops_id, parent
                HAVING num >= %s
            ";

            $result2 = $ilDB->queryF($query2, array('integer','integer'), array($row1['parent'], $params['min']));
            while ($row2 = $ilDB->fetchAssoc($result2))
            {
                if ($row2['num'] >= 10000) $doubles['>=100000']++;
                elseif ($row2['num'] >= 10000) $doubles['>=10000']++;
                elseif ($row2['num'] >= 1000) $doubles['>=1000']++;
                elseif ($row2['num'] >= 100) $doubles['>=100']++;
                elseif ($row2['num'] >= 10) $doubles['>=10']++;
                elseif ($row2['num'] >= 2) $doubles['>=2']++;

                echo implode(', ', $row2) . "\n";

                if ($params['cleanup'])
                {
                    $ilDB->beginTransaction();
                    try
                    {
                        $query3 = "
                            DELETE FROM rbac_templates
                            WHERE rol_id = %s
                            AND `type` = %s
                            AND ops_id = %s
                            AND parent = %s
                        ";
                        $ilDB->manipulateF($query3,
                            array('integer','text','integer','integer'),
                            array($row2['rol_id'], $row2['type'], $row2['ops_id'] ,$row2['parent'])
                        );

                        $query4 = "
                            INSERT INTO rbac_templates(rol_id, `type`, ops_id, parent)
                            VALUES(%s, %s, %s, %s)
                        ";
                        $ilDB->manipulateF($query4,
                            array('integer','text','integer','integer'),
                            array($row2['rol_id'], $row2['type'], $row2['ops_id'] ,$row2['parent'])
                        );
                    }
                    catch (Exception $e)
                    {
                        $ilDB->rollback();
                        echo $e->getMessage();
                        echo $e->getTraceAsString();
                        return;
                    }
                    $ilDB->commit();
                }
            }
        }

       echo "Doubles: ";
       var_dump($doubles);
    }

	/**
	 * Empty the system trash
	 *
	 * Removes all objects of the given type thar were deleted before a certain date
	 *
	 * The query searches for all directly deleted objects
	 * The ilRepUtil deletes all sub objects, too
	 * This has the same effct than removing the single objects manually from the tray
	 *
	 * @param array $params
	 */
	public function removeTrashedObjects($params = array('types' => 'cat,crs', 'deleted_before' => '2014-10-01 00:00:00', 'limit'=> null))
	{
		global $ilDB, $ilLog;

		include_once("./Services/Repository/classes/class.ilRepUtil.php");

		$types = explode(',', $params['types']);

		$query = "SELECT o.type, o.title, o.obj_id, r.ref_id, r.deleted, t.tree, t.path"
				. " FROM tree t"
				. " INNER JOIN object_reference r on t.child = r.ref_id"
				. " INNER JOIN object_data o on o.obj_id = r.obj_id"
				. " WHERE t.tree = -t.child"
				. (empty($types) ? "" : " AND " .$ilDB->in('type', $types, false, 'text'))
				. " AND (r.deleted IS NULL OR r.deleted < " .$ilDB->quote($params['deleted_before'], 'date')
				. ")"
				. "ORDER BY o.type, r.deleted";
		$res = $ilDB->query($query);

		$deleted = array();
		$deleted_sum = 0;
		while ($row = $ilDB->fetchAssoc($res))
		{
			if (isset($params['limit']) and $deleted_sum >= $params['limit'])
			{
				break;
			}
			$logstr = "obj_id: ".$row['obj_id'].", ref_id: ".$row['ref_id'].", type: ".$row['type']
				.", path:".$row['path'].", title: ".$row['title'].", deleted: ".$row['deleted'];
			$ilLog->write("ilSpecificPatches::removeTrashedObjects: ".$logstr);

			echo $logstr."\n";

			try
			{
				ilRepUtil::removeObjectsFromSystem(array($row['ref_id']), false);
			}
			catch (Exception $e)
			{
				$ilLog->write("ilSpecificPatches::removeTrashedObjects: ".$e->getMessage()."\n".$e->getTraceAsString());

				echo $e->getMessage();
				echo $e->getTraceAsString();
				continue;
			}

			$deleted[$row['type']]++;
			$deleted_sum++;
		}

		echo "Deleted: ";
		var_dump($deleted);
	}
}

