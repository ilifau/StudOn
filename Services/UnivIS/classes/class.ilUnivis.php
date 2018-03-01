<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
*  fim: [univis] class for mapping univis entities to studon objects
*/
class ilUnivis
{
	static $start_dates = array(
		'2016w' => '2016-10-17',
		'2017s' => '2017-04-24',
		'2017w' => '2017-10-16',
		'2018s' => '2018-04-09',
		'2018w' => '2018-10-15',
		'2019s' => '2019-04-23',
		'2019w' => '2019-10-14',
		'2020s' => '2020-04-20',
		'2020w' => '2020-10-12'
	);

	static $end_dates = array(
		'2016w' => '2017-02-11',
		'2017s' => '2017-07-30',
		'2017w' => '2018-02-10',
		'2018s' => '2018-07-14',
		'2018w' => '2019-02-09',
		'2019s' => '2019-07-27',
		'2019w' => '2020-02-07',
		'2020s' => '2020-07-24',
		'2020w' => '2021-02-05'
	);


	/**
	* Get all untrashed objects for an import id
	*
	* @param    string   	univis id (e.g 2011s.Lecture.391275)
	* @return   array   	arrays of object data (ref_id, obj_id, title, ...)
	*/
	static function _getUntrashedObjectsForUnivisId($a_univis_id)
	{
		return ilObject::_getUntrashedObjectsForImportId($a_univis_id);
	}
	
	/**
	* Get all untrashed objects for a semester
	*
	* @param    string      import id
	* @return   array   	arrays of object data (ref_id, obj_id, title, ...)
	*/
	static function _getUntrashedObjectsForSemester($a_semester)
	{
		return ilObject::_getUntrashedObjectsForImportId($a_semester.'%', 'like');
	}
	
	/**
	* Get the main univis id for object id
	*
	* @param	int		$a_object_id		object id
	* @return	string	id                  import_id
	*/
	static function _getUnivisIdForObjectId($a_obj_id)
	{
		return ilObject::_getImportIdForObjectId($a_obj_id);
	}

	/**
	 * Get additional univis ids for an object id
	 * These are entered with the catalog 'univis' in the meta data
	 *
	 * Additional id's are used to query the members of related univis courses from mycampus
	 * @param $a_obj_id
	 * @return array
	 */
	static function _getAdditionalUnivisIdsForObjectId($a_obj_id)
	{
		global $ilDB;

		$ids = array();
		$query = "SELECT m.entry FROM il_meta_identifier m ".
			" WHERE m.obj_id = ". $ilDB->quote($a_obj_id, 'integer').
			" AND m.catalog = 'univis'";
		$res = $ilDB->query($query);
		while ($row = $ilDB->fetchAssoc($res))
		{
			$ids[] = $row['entry'];
		}

		return $ids;
	}
	
	
	/**
	 * Get a string representing the current semester
	 * 
	 * @return	string 	year and semester, e.g. '2013w' (ws) or '2013s' (ss)
	 */
	public static function _getRunningSemester()
	{
		$month = (int) date('m');
		if ($month <= 3)
		{
			// winter semester of last year
	        $cur_year = (int) date('Y') - 1;
			$cur_sem = 'w';
		}
		elseif ($month <= 9)
		{
	        // summer semester
	        $cur_year = (int) date('Y');
			$cur_sem = 's';
		}
		else
		{
			// winter semester of this year
	        $cur_year = (int) date('Y');
			$cur_sem = 'w';
	    }
	    
	    return sprintf("%04d%s", $cur_year, $cur_sem);
	}
	
	
	/**
	 * Get a string representing the current semester
	 * 
	 * @return	string 	year and semester, e.g. '2013w' (ws) or '2013s' (ss)
	 */
	public static function _getNextSemester()
	{
		$month = (int) date('m');
		if ($month <= 3)
		{
			// next summer semester in this year
	        $cur_year = (int) date('Y');
			$next_sem = 's';
		}
		elseif ($month <= 9)
		{
	        // next winter semester
	        $cur_year = (int) date('Y');
			$cur_sem = 'w';
		}
		else
		{
			// next summer selexter in next year
	        $cur_year = (int) date('Y') + 1;
			$cur_sem = 's';
	    }
	    
	    return sprintf("%04d%s", $cur_year, $cur_sem);
	}


	/**
	 * Get the date string of the lectures start
	 * @param string $a_semester
	 * @return string					e.g. '2018-04-09'
	 */
	public static function _getLecturesStartDate($a_semester)
	{
		if (isset(self::$start_dates[$a_semester]))
		{
			return self::$start_dates[$a_semester];
		}
		return '';
	}


	/**
	 * Get the date string of the lectures end
	 * @param string $a_semester
	 * @return string		e.g. '2018-07-24'
	 */
	public static function _getLecturesEndDate($a_semester)
	{
		if (isset(self::$end_dates[$a_semester]))
		{
			return self::$end_dates[$a_semester];
		}
		return '';
	}
}
?>