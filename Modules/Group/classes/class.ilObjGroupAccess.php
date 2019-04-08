<?php

/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/Object/classes/class.ilObjectAccess.php");

/**
* Class ilObjGroupAccess
*
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
*/
class ilObjGroupAccess extends ilObjectAccess
{
	protected static $using_code = false;
	/**
	* checks wether a user may invoke a command or not
	* (this method is called by ilAccessHandler::checkAccess)
	*
	* @param	string		$a_cmd		command (not permission!)
	* @param	string		$a_permission	permission
	* @param	int			$a_ref_id	reference id
	* @param	int			$a_obj_id	object id
	* @param	int			$a_user_id	user id (if not provided, current user is taken)
	*
	* @return	boolean		true, if everything is ok
	*/
	function _checkAccess($a_cmd, $a_permission, $a_ref_id, $a_obj_id, $a_user_id = "")
	{

		global $ilUser, $lng, $rbacsystem, $ilAccess;

		if ($a_user_id == "")
		{
			$a_user_id = $ilUser->getId();
		}
		
		switch ($a_cmd)
		{
			case "info":
			
				include_once './Modules/Group/classes/class.ilGroupParticipants.php';
				if(ilGroupParticipants::_isParticipant($a_ref_id,$a_user_id))
				{
					$ilAccess->addInfoItem(ilAccessInfo::IL_STATUS_INFO, $lng->txt("info_is_member"));
				}
				else
				{
					$ilAccess->addInfoItem(ilAccessInfo::IL_STATUS_INFO, $lng->txt("info_is_not_member"));
				}						
				break;
				
			case "join":
			
				if(!self::_registrationEnabled($a_obj_id))
				{
					return false;
				}

				include_once './Modules/Group/classes/class.ilGroupWaitingList.php';
				// fim: [memfix] use $a_user_id parameter to query waiting list
				if(ilGroupWaitingList::_isOnList($a_user_id, $a_obj_id))
				// fim.
				{
					return false;
				}

				include_once './Modules/Group/classes/class.ilGroupParticipants.php';
				if(ilGroupParticipants::_isParticipant($a_ref_id,$a_user_id))
				{
					return false;
				}
				break;
				
			case 'leave':

				// Regular member
				if($a_permission == 'leave')
				{
					include_once './Modules/Group/classes/class.ilObjGroup.php';
					$limit = null;
					if(!ilObjGroup::mayLeave($a_obj_id, $a_user_id, $limit))
					{						
						$ilAccess->addInfoItem(ilAccessInfo::IL_STATUS_INFO,
							sprintf($lng->txt("grp_cancellation_end_rbac_info"), ilDatePresentation::formatDate($limit)));
						return false;
					}		
					
					include_once './Modules/Group/classes/class.ilGroupParticipants.php';
					if(!ilGroupParticipants::_isParticipant($a_ref_id, $a_user_id))
					{
						return false;
					}
				}
				// Waiting list
				if($a_permission == 'join')
				{
					include_once './Modules/Group/classes/class.ilGroupWaitingList.php';
					// fim: [memfix] use $a_user_id parameter to query waiting list
					if(!ilGroupWaitingList::_isOnList($a_user_id, $a_obj_id))
					// fim.
					{
						return false;
					}
				}
				break;

			// fim: [memad] check rights for guest accounts to request a join
			case 'joinAsGuest':

				include_once './Modules/Group/classes/class.ilGroupParticipants.php';

				// don't show join_as_guest command if user is already assigned
				if(ilGroupParticipants::_isParticipant($a_ref_id, $a_user_id))
				{
					return false;
				}

				// don't show join_as_guest command if user can join
				if ($rbacsystem->checkAccessOfUser($a_user_id,'join',$a_ref_id))
				{
					return false;
				}

				break;
			// fim.


		}

		switch ($a_permission)
		{
			case 'leave':
				include_once './Modules/Group/classes/class.ilObjGroup.php';
				return ilObjGroup::mayLeave($a_obj_id, $a_user_id);
		}
		return true;
	}

	/**
	 * get commands
	 * 
	 * this method returns an array of all possible commands/permission combinations
	 * 
	 * example:	
	 * $commands = array
	 *	(
	 *		array("permission" => "read", "cmd" => "view", "lang_var" => "show"),
	 *		array("permission" => "write", "cmd" => "edit", "lang_var" => "edit"),
	 *	);
	 */
	static function _getCommands()
	{
		$commands = array();
		$commands[] = array("permission" => "grp_linked", "cmd" => "", "lang_var" => "show", "default" => true);

		include_once './Services/WebServices/FileManager/classes/class.ilFMSettings.php';
		if(ilFMSettings::getInstance()->isEnabled())
		{
			$commands[] = array(
				'permission' => 'read',
				'cmd' => 'fileManagerLaunch',
				'lang_var' => 'fm_start',
				'enable_anonymous' => false
			);
		}

		$commands[] = array("permission" => "join", "cmd" => "join", "lang_var" => "join");

		// on waiting list
// fau: fairSub - general command for editing requests
		$commands[]	= array('permission' => "join", "cmd" => "leave", "lang_var" => "mem_edit_request");
// fau.

		// fim: [memad] add command for guest accounts to request a join
		include_once('Services/User/classes/class.ilUserUtil.php');
		if (ilUserUtil::_isGuestHearer())
		{
			$commands[]	= array('permission' => "visible", "cmd" => "joinAsGuest", "lang_var" => "join_as_guest");
		}
		// fim.
		
		// regualar users
		$commands[]	= array('permission' => "leave", "cmd" => "leave", "lang_var" => "grp_btn_unsubscribe");
		
		include_once ('Services/WebDAV/classes/class.ilDAVActivationChecker.php');
		if (ilDAVActivationChecker::_isActive())
		{
			include_once './Services/WebDAV/classes/class.ilDAVUtils.php';
			if(ilDAVUtils::getInstance()->isLocalPasswordInstructionRequired())
			{
				$commands[] = array('permission' => 'read', 'cmd' => 'showPasswordInstruction', 'lang_var' => 'mount_webfolder', 'enable_anonymous' => 'false');
			}
			else
			{
				$commands[] = array("permission" => "read", "cmd" => "mount_webfolder", "lang_var" => "mount_webfolder", "enable_anonymous" => "false");
			}
		}

		$commands[] = array("permission" => "write", "cmd" => "enableAdministrationPanel", "lang_var" => "edit_content");
		$commands[] = array("permission" => "write", "cmd" => "edit", "lang_var" => "settings");
		
		return $commands;
	}
	
	/**
	* check whether goto script will succeed
	*/
	static function _checkGoto($a_target)
	{
		global $ilAccess,$ilUser;

		$t_arr = explode("_", $a_target);
		// registration codes
		if(substr($t_arr[2],0,5) == 'rcode' and $ilUser->getId() != ANONYMOUS_USER_ID)
		{
			self::$using_code = true;
			return true;
		}

		if ($t_arr[0] != "grp" || ((int) $t_arr[1]) <= 0)
		{
			return false;
		}

		// fim: [univis] don't allow 'join' command for anonymous users
		if ($t_arr[2] == 'join' && $ilUser->getId() == ANONYMOUS_USER_ID)
		{
	        global $lng;

			// ugly fix: $tpl used by ilUtil may not be initialized
	        //ilUtil::sendInfo($lng->txt('join_grp_needs_login'), true);
			$_SESSION['info'] = $lng->txt('join_grp_needs_login', true);
			ilUtil::redirect(ilUtil::_getRootLoginLink($a_target), true);
		}
		// fim.

		// fim: [bugfix] omit checking read access (bug 5323)(see ilObjCourseAccess::_checkGoto)
		if ($ilAccess->checkAccess("visible", "", $t_arr[1]))
		//		if ($ilAccess->checkAccess("read", "", $t_arr[1]) ||
		//		$ilAccess->checkAccess("visible", "", $t_arr[1]))
		// fim.
		{
			return true;
		}
		return false;
	}
	
	/**
	 * 
	 * @return 
	 * @param object $a_obj_id
	 */
	public static function _registrationEnabled($a_obj_id)
	{
		global $ilDB;

		$query = "SELECT * FROM grp_settings ".
			"WHERE obj_id = ".$ilDB->quote($a_obj_id ,'integer')." ";

		$res = $ilDB->query($query);
		
		$enabled = $unlimited = false;
		while($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT))
		{
			$enabled = $row->registration_enabled;
			$unlimited = $row->registration_unlimited;
			$start = $row->registration_start;
			$end = $row->registration_end;
		}

		if(!$enabled)
		{
			return false;
		}
		if($unlimited)
		{
			return true;
		}
		
		if(!$unlimited)
		{
			$start = new ilDateTime($start,IL_CAL_DATETIME);
			$end = new ilDateTime($end,IL_CAL_DATETIME);
			$time = new ilDateTime(time(),IL_CAL_UNIX);
			
			return ilDateTime::_after($time, $start) and ilDateTime::_before($time,$end); 
		}
		return false;
	}
	

	/**
	 * Preload data
	 *
	 * @param array $a_obj_ids array of object ids
	 */
	static function _preloadData($a_obj_ids, $a_ref_ids)
	{
		global $ilDB, $ilUser;
		
		include_once("./Modules/Group/classes/class.ilGroupWaitingList.php");
		ilGroupWaitingList::_preloadOnListInfo($ilUser->getId(), $a_obj_ids);
	}
	
	/**
	 * Lookup registration info
	 * @global ilDB $ilDB
	 * @global ilObjUser $ilUser
	 * @global ilLanguage $lng
	 * @param int $a_obj_id
	 * @return array
	 */
	// fim: [meminf] add ref_id as parameter for checking write access
	public static function lookupRegistrationInfo($a_obj_id, $a_ref_id = 0)
	// fim.
	{
		global $ilDB, $ilUser, $lng;

		// fim: [meminf] query for showing memmbership limitation
// fau: fairSub - query for fair period
		$query = 'SELECT registration_type, registration_enabled, registration_unlimited,  registration_start, '.
			'registration_end, registration_mem_limit, registration_max_members, show_mem_limit, sub_fair FROM grp_settings '.
			'WHERE obj_id = '.$ilDB->quote($a_obj_id);
		$res = $ilDB->query($query);
		
		$info = array();
		while($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT))
		{
			$info['reg_info_start'] = new ilDateTime($row->registration_start, IL_CAL_DATETIME);
			$info['reg_info_end'] = new ilDateTime($row->registration_end, IL_CAL_DATETIME);
			$info['reg_info_type'] = $row->registration_type;
			$info['reg_info_max_members'] = $row->registration_max_members;
			$info['reg_info_mem_limit'] = $row->registration_mem_limit;
			$info['reg_info_unlimited'] = $row->registration_unlimited;
			
			$info['reg_info_max_members'] = 0;
			if($info['reg_info_mem_limit'])
			{
				$info['reg_info_max_members'] = $row->registration_max_members;
			}
			
			$info['reg_info_enabled'] = $row->registration_enabled;
			$info['reg_info_show_mem_limit'] = $row->show_mem_limit;
			$info['reg_info_sub_fair'] = $row->sub_fair;
		}
// fau.
		// fim.

		$registration_possible = $info['reg_info_enabled'];

		// Limited registration (added $registration_possible, see bug 0010157)
		if(!$info['reg_info_unlimited'] && $registration_possible)
		{
// fau: fairSub - add info about fair period
			$fair_suffix = '';
			if ($info['reg_info_mem_limit'] > 0 && $info['reg_info_max_members'] > 0)
			{
				if ($info['reg_info_sub_fair'] < 0 )
				{
					$fair_suffix = " - <b>".$lng->txt('sub_fair_inactive_short')."</b>";
				}
//				elseif (time() < $info['reg_info_sub_fair'])
//				{
//					$fair_suffix = " <br />".$lng->txt('sub_fair_date'). ': '
//						. ilDatePresentation::formatDate(new ilDateTime($info['reg_info_sub_fair'],IL_CAL_UNIX));
//				}
			}

			$dt = new ilDateTime(time(),IL_CAL_UNIX);
			if(ilDateTime::_before($dt, $info['reg_info_start']))
			{
				$info['reg_info_list_prop']['property'] = $lng->txt('grp_list_reg_start');
				$info['reg_info_list_prop']['value'] = ilDatePresentation::formatDate($info['reg_info_start']) . $fair_suffix;
			}
			elseif(ilDateTime::_before($dt, $info['reg_info_end']))
			{
				$info['reg_info_list_prop']['property'] = $lng->txt('grp_list_reg_end');
				$info['reg_info_list_prop']['value'] = ilDatePresentation::formatDate($info['reg_info_end']) . $fair_suffix;
			}
			else
			{
				$registration_possible = false;
				$info['reg_info_list_prop']['property'] = $lng->txt('grp_list_reg_period');
				$info['reg_info_list_prop']['value'] = $lng->txt('grp_list_reg_noreg');
			}
// fau.
		}
		else
		{
			// added !$registration_possible, see bug 0010157
			if (!$registration_possible)
			{
				$registration_possible = false;
				$info['reg_info_list_prop']['property'] = $lng->txt('grp_list_reg');
				$info['reg_info_list_prop']['value'] = $lng->txt('grp_list_reg_noreg');
			}
		}

		// fim: [meminf] get info about membership limitations and subscription status
		global $ilAccess;
		include_once './Modules/Group/classes/class.ilGroupParticipant.php';
		include_once './Modules/Group/classes/class.ilGroupWaitingList.php';

		$partObj = ilGroupParticipant::_getInstanceByObjId($a_obj_id, $ilUser->getId());

		if($info['reg_info_mem_limit'] && $info['reg_info_show_mem_limit'] && $registration_possible)
		{
			$show_mem_limit = true;
			$show_hidden_notice = false;
		}
		elseif ($info['reg_info_mem_limit'] && $ilAccess->checkAccess('write', '', $a_ref_id, 'crs', $a_obj_id))
		{
			$show_mem_limit = true;
			$show_hidden_notice = true;
		}
		else
		{
			$show_mem_limit = false;
			$show_hidden_notice = false;
		}


		if($show_mem_limit)
		{
			$max_members = $info['reg_info_max_members'];
			$members = $partObj->getNumberOfMembers();
			$free_places = max($max_members - $members, 0);
			$info['reg_info_free_places'] = $free_places;

			$waiting = ilGroupWaitingList::lookupListSize($a_obj_id);

			$limits = array();
			if ($show_hidden_notice)
			{
				$limits[] = $lng->txt("mem_max_users_hidden");
			}
			$limits[] =  $lng->txt("mem_max_users"). $max_members;
			$limits[] =  $lng->txt("mem_free_places"). ': '. $free_places;
			if ($waiting > 0)
			{
				$limits[] =  $lng->txt("subscribers_or_waiting_list"). ': '. (string) ($waiting);
			}
			$info['reg_info_list_prop_limit']['property'] = '';
			$info['reg_info_list_prop_limit']['value'] = implode(' &nbsp; ', $limits);
		}

		// registration status
		switch(ilGroupWaitingList::_getStatus($ilUser->getId(), $a_obj_id))
		{
			case ilWaitingList::REQUEST_NOT_TO_CONFIRM:
				$status = $lng->txt('on_waiting_list');
				break;
			case ilWaitingList::REQUEST_TO_CONFIRM:
				$status = $lng->txt('sub_status_pending');
				break;
			case ilWaitingList::REQUEST_CONFIRMED:
				$status = $lng->txt('sub_status_confirmed');
				break;
			default:
				$status = '';

		}
		if ($status)
		{
			$info['reg_info_list_prop_status']['property'] = $lng->txt('member_status');
			$info['reg_info_list_prop_status']['value'] = $status;
		}
		// fim.

		return $info;
	}
	
	/**
	 * Lookup course period info
	 * 
	 * @param int $a_obj_id
	 * @return array
	 */
	public static function lookupPeriodInfo($a_obj_id)
	{
		global $ilDB, $lng;
		
		$start = $end = null;
		
		$query = 'SELECT grp_start, grp_end FROM grp_settings'.
			' WHERE obj_id = '.$ilDB->quote($a_obj_id);
		$set = $ilDB->query($query);		
		while($row = $ilDB->fetchAssoc($set))
		{			
			$start = $row['grp_start'] 
				? new ilDate($row['grp_start'], IL_CAL_UNIX)
				: null;
			$end = $row['grp_end'] 
				? new ilDate($row['grp_end'], IL_CAL_UNIX)
				: null;
		}
		
		if($start && $end)
		{
			$lng->loadLanguageModule('grp');
			
			return array(
				'property' => $lng->txt('grp_period'),
				'value' => ilDatePresentation::formatPeriod($start, $end)
			);
		}
	}
	

	/**
	 * Using Registration code
	 *
	 * @return bool
	 */
	public static function _usingRegistrationCode()
	{
		return self::$using_code;
	}
	

}
?>
