<?php

/**
 * fim
 * Institut für Lern-Innovation
 * Friedrich-Alexander-Universität
 * Erlangen-Nürnberg
 * Germany
 * Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE
 */

/**
 * Registration period limiter class.
 *
 * @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
 * @author Jesus Copado <jesus.copado@fim.uni-erlangen.de>
 *
 * @version $Id$
 */
class ilRegistrationPeriodLimiter
{


	/* *****************************
	 * Validate new date functions
	 * **************************** */

	/**
	 * Determines if a proposed starting time for registration is available.
	 * @global type $ilDB
	 * @param integer $proposed_start Is the timestamp of the proposed start for the registration of the course
	 * @return boolean true if the proposed time is available
	 * @return boolean false if the proposed time isn't available
	 */
	public static function _isValidByNumberOfPlaces($proposed_start, $get_number = false)
	{
		global $ilDB;

		//Get timestamp from String in case of groups
		if (!is_int($proposed_start))
		{
			$proposed_start = self::_getTimestampFromDate($proposed_start);
		}
		if (!is_int($proposed_start))
		{
			return;
		}

		//COURSES
		$query_courses = "
		SELECT sum(s.sub_max_members) AS members 
		FROM crs_settings s
		INNER JOIN object_reference r ON r.obj_id = s.obj_id
		INNER JOIN tree t ON t.child = r.ref_id
		WHERE t.tree = 1
		AND s.sub_mem_limit > 0
		AND s.sub_limitation_type = " . $ilDB->quote(2, "integer") . "
		AND s.sub_start > " . $ilDB->quote($proposed_start - (int) ilCust::get('rpl_period_of_check_before'), "integer") . "
		AND s.sub_start < " . $ilDB->quote($proposed_start + (int) ilCust::get('rpl_period_of_check_after'), "integer");
		$executed_query_courses = $ilDB->query($query_courses);
		$result_courses = $ilDB->fetchAssoc($executed_query_courses);
		$places_in_courses_in_this_period = $result_courses["members"];

		//GROUPS
		$query_groups = "
		SELECT sum(s.registration_max_members) AS members 
		FROM grp_settings s
		INNER JOIN object_reference r ON r.obj_id = s.obj_id
		INNER JOIN tree t ON t.child = r.ref_id
		WHERE t.tree = 1
		AND s.registration_mem_limit > 0
		AND s.registration_unlimited = 0
 		AND s.registration_start > " . $ilDB->quote(date('Y-m-d H:i:s', ($proposed_start - (int) ilCust::get('rpl_period_of_check_before'))), "text") . "
		AND s.registration_start < " . $ilDB->quote(date('Y-m-d H:i:s', ($proposed_start + (int) ilCust::get('rpl_period_of_check_after'))), "text");
		$executed_query_groups = $ilDB->query($query_groups);
		$result_groups = $ilDB->fetchAssoc($executed_query_groups);
		$places_in_groups_in_this_period = $result_groups["members"];

		$period_users = $places_in_courses_in_this_period + $places_in_groups_in_this_period;

		if ($get_number)
		{
			return $period_users;
		}

		if ($period_users > ilCust::get('rpl_warning_cat_1'))
		{
			return 'rpl_warning_cat_1';
		}
		elseif ($period_users > ilCust::get('rpl_warning_cat_2'))
		{
			return 'rpl_warning_cat_2';
		}
		elseif ($period_users > ilCust::get('rpl_warning_cat_3'))
		{
			return 'rpl_warning_cat_3';
		}
		else
		{
			return '';
		}
	}

	public static function _getCoursesInWeek($week_start_timestamp)
	{
		global $ilDB;

		//COURSES
		$array_with_courses = array();
		$query_courses = "
		SELECT s.obj_id AS obj_id, s.sub_start AS start, s.sub_max_members AS members 
		FROM crs_settings s
		INNER JOIN object_reference r ON r.obj_id = s.obj_id
		INNER JOIN tree t ON t.child = r.ref_id
		WHERE t.tree = 1
		AND s.sub_mem_limit > 0
		AND s.sub_limitation_type = " . $ilDB->quote(2, "integer") . "
		AND s.sub_start >= " . $ilDB->quote($week_start_timestamp, "integer") . "
		AND s.sub_start < " . $ilDB->quote($week_start_timestamp + 604800, "integer") . " 
		ORDER BY s.sub_start";
		$executed_query_courses = $ilDB->query($query_courses);
		$counter = 0;
		while ($result_courses = $ilDB->fetchAssoc($executed_query_courses))
		{
			$array_with_courses[$counter]["start"] = (int) $result_courses["start"];
			$array_with_courses[$counter]["members"] = (int) $result_courses["members"];
			$counter++;
		}
		return $array_with_courses;
	}

	public static function _getCoursesInPeriod($rows = array(), $period_starting_timestamp)
	{
		global $ilDB, $lng;

		require_once('./Services/Link/classes/class.ilLink.php');
		require_once('./Modules/Course/classes/class.ilCourseConstants.php');
		$lng->loadLanguageModule('crs');

		//COURSES
		$query_courses = "
		SELECT r.ref_id, o.title, o.type, 
		s.sub_type, s.sub_start, s.sub_end, 
		s.sub_max_members, s.waiting_list, s.sub_fair
		FROM crs_settings s
		INNER JOIN object_data o ON o.obj_id = s.obj_id
		INNER JOIN object_reference r ON r.obj_id = s.obj_id
		INNER JOIN tree t ON t.child = r.ref_id
		WHERE t.tree = 1
		AND s.sub_mem_limit > 0
		AND s.sub_limitation_type = " . $ilDB->quote(IL_CRS_SUBSCRIPTION_LIMITED, "integer"). "
		AND s.sub_start >= " . $ilDB->quote($period_starting_timestamp, "integer") . "
		AND s.sub_start < " . $ilDB->quote($period_starting_timestamp + 1800, "integer") . " 
		ORDER BY s.sub_start";
		$executed_query_courses = $ilDB->query($query_courses);

		while ($result_courses = $ilDB->fetchAssoc($executed_query_courses))
		{
			$data = array();
			$data["ref_id"] = $result_courses["ref_id"];
			$data["start"] = date('Y-m-d H:i:s', (int) $result_courses["sub_start"]);
			$data["end"] = date('Y-m-d H:i:s', (int) $result_courses["sub_end"]);
			$data["title"] = $result_courses["title"];
			$data["link"] = ilLink::_getStaticLink($result_courses["ref_id"]);
			$data["places"] = $result_courses["sub_max_members"];
			$data["type"] = $lng->txt('course');
			switch ($result_courses["sub_type"])
			{
				case IL_CRS_SUBSCRIPTION_CONFIRMATION:
					$data["reg_type"] = $lng->txt('crs_subscription_options_confirmation');
					break;
				case IL_CRS_SUBSCRIPTION_DIRECT:
					$data["reg_type"] = $lng->txt('crs_subscription_options_direct');
					break;
				case IL_CRS_SUBSCRIPTION_PASSWORD:
					$data["reg_type"] = $lng->txt('crs_subscription_options_password');
					break;
			}
			if ($result_courses["sub_fair"] >= 0)
			{
				$data["policy"] = ilDatePresentation::formatDate(new ilDateTime($result_courses["sub_fair"], IL_CAL_UNIX));
			}
			else
			{
				$data["policy"] = $lng->txt('sub_fair_inactive_short');
			}
		
			// use start for sorting and add ref_id for unique rows
			$rows[$data["start"].$data["ref_id"]] = $data;	
		}
		return $rows;
	}

	public static function _getGroupsInWeek($week_start_timestamp)
	{
		global $ilDB;

		//GROUPS
		$array_with_groups = array();
		$query_groups = "
		SELECT s.obj_id AS obj_id, s.registration_start AS start, s.registration_max_members AS members 
		FROM grp_settings s
		INNER JOIN object_data o ON o.obj_id = s.obj_id
		INNER JOIN object_reference r ON r.obj_id = s.obj_id
		INNER JOIN tree t ON t.child = r.ref_id
		WHERE t.tree = 1
		AND s.registration_mem_limit > 0
		AND s.registration_unlimited = 0
 		AND s.registration_start >= " . $ilDB->quote(date('Y-m-d H:i:s', ($week_start_timestamp)), "text") . "
		AND s.registration_start < " . $ilDB->quote(date('Y-m-d H:i:s', ($week_start_timestamp + 604800)), "text") . "
		ORDER BY s.registration_start";
		$executed_query_groups = $ilDB->query($query_groups);
		$counter = 0;
		while ($result_groups = $ilDB->fetchAssoc($executed_query_groups))
		{
			$array_with_groups[$counter]["start"] = (int) self::_getTimestampFromDate($result_groups["start"]);
			$array_with_groups[$counter]["members"] = (int) $result_groups["members"];
			$counter++;
		}
		return $array_with_groups;
	}

	public static function _getGroupsInPeriod($rows = array(), $period_starting_timestamp)
	{
		global $ilDB, $lng;

		require_once('./Services/Link/classes/class.ilLink.php');
		require_once('./Modules/Group/classes/class.ilObjGroup.php');

		// GROUPS
		$query_groups = "
		SELECT r.ref_id, o.title, o.type, 
		s.registration_type, s.registration_start, s.registration_end, 
		s.registration_max_members, s.waiting_list, s.sub_fair
		FROM grp_settings s
		INNER JOIN object_data o ON o.obj_id = s.obj_id
		INNER JOIN object_reference r ON r.obj_id = s.obj_id
		INNER JOIN tree t ON t.child = r.ref_id
		WHERE t.tree = 1
		AND s.registration_mem_limit > 0
		AND s.registration_unlimited = 0
 		AND s.registration_start >= " . $ilDB->quote(date('Y-m-d H:i:s', ($period_starting_timestamp)), "text") . "
		AND s.registration_start < " . $ilDB->quote(date('Y-m-d H:i:s', ($period_starting_timestamp + 1800)), "text") . "
		ORDER BY s.registration_start";
		$executed_query_groups = $ilDB->query($query_groups);

		while ($result_groups = $ilDB->fetchAssoc($executed_query_groups))
		{
			$data = array();
			$data["ref_id"] = $result_groups["ref_id"];
			$data["start"] = $result_groups["registration_start"];
			$data["end"] = $result_groups["registration_end"];
			$data["title"] = $result_groups["title"];
			$data["link"] = ilLink::_getStaticLink($result_groups["ref_id"]);
			$data["places"] = $result_groups["registration_max_members"];
			$data["type"] = $lng->txt('group');
			switch ($result_groups["registration_type"])
			{
				case GRP_REGISTRATION_REQUEST:
					$data["reg_type"] = $lng->txt('crs_subscription_options_confirmation');
					break;
				case GRP_REGISTRATION_DIRECT:
					$data["reg_type"] = $lng->txt('crs_subscription_options_direct');
					break;
				case GRP_REGISTRATION_PASSWORD:
					$data["reg_type"] = $lng->txt('crs_subscription_options_password');
					break;
			}
			if ($result_groups["sub_fair"] >= 0)
			{
				$data["policy"] = ilDatePresentation::formatDate(new ilDateTime($result_groups["sub_fair"], IL_CAL_UNIX));
			}
			else
			{
				$data["policy"] = $lng->txt('sub_fair_inactive_short');
			}
		
			// use start for sorting and add ref_id for unique rows
			$rows[$data["start"].$data["ref_id"]] = $data;	
		}
		return $rows;		
	}

	public static function _joinCoursesAndGroups($array_of_courses, $array_of_groups, $start_timestamp)
	{
		$timestamps_array = self::_getTimestampsArray($start_timestamp);

		//Courses
		foreach ($array_of_courses as $course)
		{
			$course_start = $course["start"];
			$course_members = $course["members"];
			for ($i = 0; $i < sizeof($timestamps_array); $i++)
			{
				if ($timestamps_array[$i]["timestamp"] > $course_start)
				{
					$timestamps_array[$i]["members"] = $timestamps_array[$i]["members"] + $course_members;
					break;
				}
			}
		}
		
		//Groups
		foreach ($array_of_groups as $group)
		{
			$group_start = $group["start"];
			$group_members = $group["members"];
			for ($i = 0; $i < sizeof($timestamps_array); $i++)
			{
				if ($timestamps_array[$i]["timestamp"] > $group_start)
				{
					$timestamps_array[$i]["members"] = $timestamps_array[$i]["members"] + $group_members;
					break;
				}
			}
		}
		return $timestamps_array;
	}

	public static function _getTableViewData($start_timestamp)
	{
		$array_of_courses = self::_getCoursesInWeek($start_timestamp);
		$array_of_groups = self::_getGroupsInWeek($start_timestamp);
		$array_of_courses_and_groups = self::_joinCoursesAndGroups($array_of_courses, $array_of_groups, $start_timestamp);
		$data_array = self::_formatDataArray($array_of_courses_and_groups);
		return $data_array;
	}

	public static function _getPeriodViewData($start_timestamp)
	{
		$rows = array();
		$rows = self::_getCoursesInPeriod($rows, $start_timestamp);
		$rows = self::_getGroupsInPeriod($rows, $start_timestamp);
		return $rows;
	}

	public static function _getTimestampsArray($start_timestamp)
	{
		//Create the table of timestamps for the week
		$array_with_timestamps = array();
		$timestamp = $start_timestamp + 1800;
		for ($i = 0; $i < 336; $i++)
		{
			$array_with_timestamps[$i]["timestamp"] = $timestamp;
			$array_with_timestamps[$i]["members"] = 0;
			$timestamp = $timestamp + 1800;
		}
		return $array_with_timestamps;
	}
	
	public static function _getTimestampFromDate($date)
	{
		$separate = explode(" ", $date);
		$date = explode("-", $separate[0]);
		$year = (int) $date[0];
		$month = (int) $date[1];
		$day = (int) $date[2];
		$hours = explode(":", $separate[1]);
		$hour = (int) $hours[0];
		$minute = (int) $hours[1];
		$second = (int) $hours[2];
		$timestamp = mktime($hour, $minute, $second, $month, $day, $year);
		if (!is_int($timestamp))
		{
			return false;
		}
		else
		{
			return $timestamp;
		}
	}

	public static function _formatDataArray($lineal_array_of_courses_and_groups)
	{
		global $ilCtrl;
		//Get hours
		$array_of_data = self::_getHourString();

		//Monday
		for ($counter = 0; $counter < 48; $counter++)
		{
			$array_of_data[$counter][1]["members"] = $lineal_array_of_courses_and_groups[$counter]["members"];
			$array_of_data[$counter][1]["period"] = $lineal_array_of_courses_and_groups[$counter]["timestamp"] - 1800;
		}

		//Tuesday
		$counter = 0;
		for ($i = 48; $i < 96; $i++)
		{
			$array_of_data[$counter][2]["members"] = $lineal_array_of_courses_and_groups[$i]["members"];
			$array_of_data[$counter][2]["period"] = $lineal_array_of_courses_and_groups[$i]["timestamp"] - 1800;
			$counter++;
		}

		//Wednesday
		$counter = 0;
		for ($i = 96; $i < 144; $i++)
		{
			$array_of_data[$counter][3]["members"] = $lineal_array_of_courses_and_groups[$i]["members"];
			$array_of_data[$counter][3]["period"] = $lineal_array_of_courses_and_groups[$i]["timestamp"] - 1800;
			$counter++;
		}

		//Thurday
		$counter = 0;
		for ($i = 144; $i < 192; $i++)
		{
			$array_of_data[$counter][4]["members"] = $lineal_array_of_courses_and_groups[$i]["members"];
			$array_of_data[$counter][4]["period"] = $lineal_array_of_courses_and_groups[$i]["timestamp"] - 1800;
			$counter++;
		}

		//Friday
		$counter = 0;
		for ($i = 192; $i < 240; $i++)
		{
			$array_of_data[$counter][5]["members"] = $lineal_array_of_courses_and_groups[$i]["members"];
			$array_of_data[$counter][5]["period"] = $lineal_array_of_courses_and_groups[$i]["timestamp"] - 1800;
			$counter++;
		}

		//Saturday
		$counter = 0;
		for ($i = 240; $i < 288; $i++)
		{
			$array_of_data[$counter][6]["members"] = $lineal_array_of_courses_and_groups[$i]["members"];
			$array_of_data[$counter][6]["period"] = $lineal_array_of_courses_and_groups[$i]["timestamp"] - 1800;
			$counter++;
		}

		//Sunday
		$counter = 0;
		for ($i = 288; $i < 336; $i++)
		{
			$array_of_data[$counter][7]["members"] = $lineal_array_of_courses_and_groups[$i]["members"];
			$array_of_data[$counter][7]["period"] = $lineal_array_of_courses_and_groups[$i]["timestamp"] - 1800;
			$counter++;
		}
		return $array_of_data;
	}
	
	public static function _getHourString()
	{
		$array_of_hours = array();
		$array_of_hours[0][0] = "00:00 - 00:30";
		$array_of_hours[1][0] = "00:30 - 01:00";
		$array_of_hours[2][0] = "01:00 - 01:30";
		$array_of_hours[3][0] = "01:30 - 02:00";
		$array_of_hours[4][0] = "02:00 - 02:30";
		$array_of_hours[5][0] = "02:30 - 03:00";
		$array_of_hours[6][0] = "03:00 - 03:30";
		$array_of_hours[7][0] = "03:30 - 04:00";
		$array_of_hours[8][0] = "04:00 - 04:30";
		$array_of_hours[9][0] = "04:30 - 05:00";
		$array_of_hours[10][0] = "05:00 - 05:30";
		$array_of_hours[11][0] = "05:30 - 06:00";
		$array_of_hours[12][0] = "06:00 - 06:30";
		$array_of_hours[13][0] = "06:30 - 07:00";
		$array_of_hours[14][0] = "07:00 - 07:30";
		$array_of_hours[15][0] = "07:30 - 08:00";
		$array_of_hours[16][0] = "08:00 - 08:30";
		$array_of_hours[17][0] = "08:30 - 09:00";
		$array_of_hours[18][0] = "09:00 - 09:30";
		$array_of_hours[19][0] = "09:30 - 10:00";
		$array_of_hours[20][0] = "10:00 - 10:30";
		$array_of_hours[21][0] = "10:30 - 11:00";
		$array_of_hours[22][0] = "11:00 - 11:30";
		$array_of_hours[23][0] = "11:30 - 12:00";
		$array_of_hours[24][0] = "12:00 - 12:30";
		$array_of_hours[25][0] = "12:30 - 13:00";
		$array_of_hours[26][0] = "13:00 - 13:30";
		$array_of_hours[27][0] = "13:30 - 14:00";
		$array_of_hours[28][0] = "14:00 - 14:30";
		$array_of_hours[29][0] = "14:30 - 15:00";
		$array_of_hours[30][0] = "15:00 - 15:30";
		$array_of_hours[31][0] = "15:30 - 16:00";
		$array_of_hours[32][0] = "16:00 - 16:30";
		$array_of_hours[33][0] = "16:30 - 17:00";
		$array_of_hours[34][0] = "17:00 - 17:30";
		$array_of_hours[35][0] = "17:30 - 18:00";
		$array_of_hours[36][0] = "18:00 - 18:30";
		$array_of_hours[37][0] = "18:30 - 19:00";
		$array_of_hours[38][0] = "19:00 - 19:30";
		$array_of_hours[39][0] = "19:30 - 20:00";
		$array_of_hours[40][0] = "20:00 - 20:30";
		$array_of_hours[41][0] = "20:30 - 21:00";
		$array_of_hours[42][0] = "21:00 - 21:30";
		$array_of_hours[43][0] = "21:30 - 22:00";
		$array_of_hours[44][0] = "22:00 - 22:30";
		$array_of_hours[45][0] = "22:30 - 23:00";
		$array_of_hours[46][0] = "23:00 - 23:30";
		$array_of_hours[47][0] = "23:30 - 00:00";
		return $array_of_hours;
	}

	public static function _getOverviewLink($a_time = 0)
	{
		global $lng;

		if (!is_int($a_time))
		{
			$a_time = self::_getTimestampFromDate($a_time);
		}

		$url = 'ilias.php?baseClass=ilRegistrationPeriodLimiterGUI';
		if ($a_time)
		{
			$url .= '&time=' . $a_time;
		}
		return '<a target="_blank" href="' . $url . '">' . $lng->txt('rpl_overview') . '</a>';
	}
}

?>
