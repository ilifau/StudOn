<?php
/* fim: [studycond] new class. */

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* Class ilStudyAccess
* 
* This class handles specific access rights based on study data
*
* @author Fred Neumann <fred.neumann@fim.uni-erlangen.de> 
*/
class ilStudyAccess
{
	/**
	 * check study data based access conditions
	 * 
	 * This check is called from ilRbacSystem->checkAccessOfUser() for "read" operations
	 * A positive result will overrule the rbac restrictions
	 * 
	 * @param 	int			ref id of an object
	 * @param 	int			user id
	 * @return  boolean		access is granted (true/false)
	 */
	public static function _checkAccess($a_ref_id, $a_user_id)
	{


        // only check objects which are
        $ref_ids = explode(',', ilCust::get('studydata_check_ref_ids'));
        if (!in_array($a_ref_id, $ref_ids))
        {
            return false;
        }

        $conditionsdata = self::_getConditionsData($a_ref_id);
		// don't return true for non-existing conditions
		// a condition has to exist because fitting will overrule!
		$studydata = self::_getStudyData($a_user_id);
		if (!count($studydata))
		{
			return false;
		}
		
		return self::_checkConditions($conditionsdata, $studydata);
	}
	
	
	/**
	 * Check the mapping of conditions data and study data
	 * @param 	array		conditions data	
	 * @param 	array		study data
	 * @return unknown_type
	 */
	public static function _checkConditions($a_conditionsdata, $a_studydata)
	{
		// check the conditions
		// only one condition needs to be satisfied
		foreach ($a_conditionsdata as $cond)
		{
			$cond_offset = self::_getRunningSemesterOffset($cond['ref_semester']);

			// check the studydata for each condition
			// only one study needs to be satisfied
			foreach ($a_studydata as $study)
			{
				// check the criteria for each study
				// all defined criteria must be satisfiesd
				// continue with next study on failure

				// check validity of the ref_semester in the study data
				// too old or new ref_semester will fail all conditions
				$stud_offset = self::_getRunningSemesterOffset($study['ref_semester']);
				if ($stud_offset < -1 or $stud_offset > 1)
				{
					continue; // failed	
				}		
				
				// check school
				// use modulus 10 beacuse PhilFak has codings 1, 11, 21 etc.
				if ($cond['school_id'] and ($cond['school_id'] % 10 != $study['school_id'] % 10))
				{
					continue; // failed
				}
				
				// check degree
				if ($cond['degree_id'] and ($cond['degree_id'] != $study['degree_id']))
				{
					continue; // failed
				}
	
				// check subjects and semester
				// only one subject/semester combination must fit
				$subject_semester_passed = false;
				foreach ($study['subjects'] as $subject)
				{
					if ($cond['subject_id'] and $cond['subject_id'] != $subject['subject_id'])
					{
						continue; // failed
					}
					
					if ($cond['min_semester'])
					{
						if (empty($subject['semester']) or ($cond['min_semester'] + $cond_offset > $subject['semester'] + $stud_offset))
						{
								continue; // failed
						}
					}
					
					if ($cond['max_semester'])
					{
						if (empty($subject['semester']) or ($cond['max_semester'] + $cond_offset < $subject['semester'] + $stud_offset))
						{
								continue; // failed
						}
					}
					
					// this subject/semester combination fits
					$subject_semester_passed = true;
					break; 
				}
				
				// this study fits;
				if ($subject_semester_passed)
				{
					return true;
				}
			}
		}

		// none of the studies fits;
		return false;
	}

		
	/**
	 * get the study condition data of an object
	 * 
	 * @param 	int			ref_id
	 * @return 	array		list of assoc condition data arrays
	 */
	private static function _getConditionsData($a_ref_id)
	{
		global $ilDB;

		// Don't use a cache for conditions
		// The result will be stored in RBAC cache

		$query = 'SELECT ref_id, school_id, subject_id, degree_id, min_semester, max_semester, ref_semester'
			.' FROM il_studycond'
			.' WHERE ref_id = '.$ilDB->quote($a_ref_id,'integer');
		$result = $ilDB->query($query);

		$data = array();
		while ($row = $ilDB->fetchAssoc($result))
		{
			$data[] = $row;
		}
		
		return $data;
	}

	/**
	 * get the study data of a user
	 * 
	 * study_no and subject_no are only used for sorting and nesting the structure
	 * They are not added to the result to avoid inconsistencies
	 * 
	 * @param 	integer		user id
	 * @param	boolean		use cached data (default: true)
	 * @return 	array		list of assoc study data arrays with nested subject ids
	 * @see		ilStudyData::_readStudyData 	(version without cache)
	 */
	public static function _getStudyData($a_user_id, $a_with_cache = true)
	{
		global $ilDB;
		static $cached_data = array();

		if ($a_with_cache and isset($cached_data[$a_user_id]))
		{
			return $cached_data[$a_user_id];
		}

		$query = 'SELECT usr_id, study_no, school_id, degree_id, ref_semester'
			. ' FROM usr_study'
			. ' WHERE usr_id='. $ilDB->quote($a_user_id,'integer')
			. ' ORDER BY study_no ASC';
		$result = $ilDB->query($query);

		$data = array();
		while ($row = $ilDB->fetchAssoc($result))
		{
			$subjects = array();
			$query2 = 'SELECT subject_id, semester'
				.' FROM usr_subject'
				.' WHERE usr_id='. $ilDB->quote($a_user_id,'integer')
				.' AND study_no='. $ilDB->quote($row["study_no"],'integer')
				.' ORDER BY subject_no ASC';
			$result2 = $ilDB->query($query2);
			while ($row2 = $ilDB->fetchAssoc($result2))
			{
				$subjects[] = array(
					"subject_id" => $row2["subject_id"],
					"semester" => $row2["semester"]
				);
			}
			$row["subjects"] = $subjects;
			
			unset($row["study_no"]);
			$data[] = $row;
		}

		if ($a_with_cache)
		{
			$cached_data[$a_user_id] = $data;
		}
		return $data;
	}
	
	
	/**
	* Get the offset of the running semester in relation to the given semester
	*
	* @param    string      year and semester, e.g. '20092' (ws) or '20101' (ss)
	* @return   int         offset (-1, 0, 1)
	*/
	public static function _getRunningSemesterOffset($a_semester = '')
	{
		if (strlen($a_semester) != 5 or !is_numeric($a_semester))
		{
	        // not identifiably
	        return 0;
	    }

		$current = self::_getRunningSemesterString();
		$cur_year = (int) substr($current, 0, 4);
		$cur_sem = (int) substr($current, 4);
			    
		$ref_year = (int) substr($a_semester, 0, 4);
		$ref_sem = (int) substr($a_semester, 4);

		return ($cur_year - $ref_year) * 2 + ($cur_sem - $ref_sem);
	}

	
	/**
	 * Get a string representing the current semester
	 * 
	 * @return	string 	year and semester year and semester, e.g. '20092' (ws) or '20101' (ss)
	 */
	public static function _getRunningSemesterString()
	{
		$month = (int) date('m');
		if ($month <= 3)
		{
			// winter semester of last year
	        $cur_year = (int) date('Y') - 1;
			$cur_sem = 2;
		}
		elseif ($month <= 9)
		{
	        // summer semester
	        $cur_year = (int) date('Y');
			$cur_sem = 1;
		}
		else
		{
			// winter semester of this year
	        $cur_year = (int) date('Y');
			$cur_sem = 2;
	    }
	    
	    return sprintf("%04d%01d", $cur_year, $cur_sem);
	}
	
}
?>
