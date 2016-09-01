<?php
/*
        +-----------------------------------------------------------------------------+
        | ILIAS open source                                                           |
        +-----------------------------------------------------------------------------+
        | Copyright (c) 1998-2006 ILIAS open source, University of Cologne            |
        |                                                                             |
        | This program is free software; you can redistribute it and/or               |
        | modify it under the terms of the GNU General Public License                 |
        | as published by the Free Software Foundation; either version 2              |
        | of the License, or (at your option) any later version.                      |
        |                                                                             |
        | This program is distributed in the hope that it will be useful,             |
        | but WITHOUT ANY WARRANTY; without even the implied warranty of              |
        | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
        | GNU General Public License for more details.                                |
        |                                                                             |
        | You should have received a copy of the GNU General Public License           |
        | along with this program; if not, write to the Free Software                 |
        | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
        +-----------------------------------------------------------------------------+
*/

include_once './Services/Object/classes/class.ilObjectAccess.php';

/**
*
* @author Stefan Meyer <smeyer.ilias@gmx.de>
* @version $Id$
*
* @ingroup ModulesSession
*/

class ilObjSessionAccess extends ilObjectAccess
{
	// fim: [memsess] initialize cache for registration settings
	protected static $registrations = array();
	// fim.
	protected static $registered = null;

	// fim: [memsess] cache checked course conditions
	// array($course_ref_id => true|false)
	protected static $course_checks = array();
	// fim.
	
	/**
	 * get list of command/permission combinations
	 *
	 * @access public
	 * @return array
	 * @static
	 */
	public function _getCommands()
	{
		$commands = array
		(
			array("permission" => "read", "cmd" => "infoScreen", "lang_var" => "info_short", "default" => true),
			array("permission" => "read", "cmd" => "register", "lang_var" => "join_session"),
			array("permission" => "read", "cmd" => "unregister", "lang_var" => "event_unregister"),
			array("permission" => "write", "cmd" => "edit", "lang_var" => "settings"),
			array("permission" => "write", "cmd" => "materials", "lang_var" => "crs_objective_add_mat"),
			array('permission' => 'write', 'cmd' => 'members', 'lang_var' => 'event_edit_members')
		);
		
		return $commands;
	}

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
	public function _checkAccess($a_cmd, $a_permission, $a_ref_id, $a_obj_id, $a_user_id = "")
	{
		global $ilUser, $lng, $rbacsystem, $ilAccess;
		
		$a_user_id = $a_user_id ? $a_user_id : $ilUser->getId();
		switch($a_cmd)
		{
			case 'register':
				// fim: [memsess] add ref_id as parameter
				if(self::_lookupRegistration($a_obj_id, $a_ref_id)&& $a_user_id != ANONYMOUS_USER_ID)
				// fim.
				{
					// fim: [memsess] extened check for session registration
					if (self::_lookupRegistered($a_user_id, $a_obj_id))
					{
						return false;
					}
					else
					{
						if ($max_participants = self::_lookupMaxParticipants($a_obj_id))
						{
							$registrations = self::_lookupRegisteredUsers($a_obj_id);
							if ($registrations >= $max_participants)
							{
								return false;
							}
						}
						return self::_checkCourseRegistrationSetting($a_ref_id, $a_user_id);
					}
					// fim.
				}
				return false;
				
			case 'unregister':
				// fim: [memsess] add ref_id as parameter
				if(self::_lookupRegistration($a_obj_id, $a_ref_id) && $a_user_id != ANONYMOUS_USER_ID)
				// fim.
				{
					return self::_lookupRegistered($a_user_id,$a_obj_id);
				}
				return false;
		}
		return true;
	}
	
	
	/**
	* check whether goto script will succeed
	*/
	public function _checkGoto($a_target)
	{
		global $ilAccess;
		
		$t_arr = explode("_", $a_target);

		if ($t_arr[0] != "sess" || ((int) $t_arr[1]) <= 0)
		{
			return false;
		}

		if($ilAccess->checkAccess("read", "", $t_arr[1]))
		{
			return true;
		}
		return false;
	}
	
	// fim: [memsess] check if registration is allowed by parent course
	public static function _checkCourseRegistrationSetting($a_ref_id, $a_usr_id)
	{
		global $tree;
		
		if (!$crs_ref_id = $tree->checkForParentType($a_ref_id, 'crs'))
		{
			// not in course -> registration allowed
			return true;
		}
		elseif (!isset(self::$course_checks[$crs_ref_id]))
		{
			include_once './Modules/Course/classes/class.ilObjCourse.php';
			$crs_obj = new ilObjCourse($crs_ref_id);

			if ($crs_obj->getSubscriptionWithEvents() == IL_CRS_SUBSCRIPTION_EVENTS_UNIQUE)
			{
				include_once './Modules/Session/classes/class.ilEventParticipants.php';
				$regs = ilEventParticipants::_getRegistrationsOfUserAndParent($a_usr_id, $crs_ref_id);
				self::$course_checks[$crs_ref_id] = (count($regs) == 0);
			}
			else
			{
				self::$course_checks[$crs_ref_id] = true;
			}
		}

		return self::$course_checks[$crs_ref_id];
	}
	// fim.

	// fim: [memsess] _lookupMaxParticipants()
	public static function _lookupMaxParticipants($a_obj_id)
	{
		global $ilDB;
		$query = "SELECT reg_limit_users FROM event "
				. "WHERE reg_limited > 0 and obj_id = ". $ilDB->quote($a_obj_id, 'integer');
		$res = $ilDB->query($query);
		if ($row = $ilDB->fetchObject($res))
		{
			return $row->reg_limit_users;
		}
	}
	// fim.

	// fim: [memsess] _lookupRegisteredUsers()
	public static function _lookupRegisteredUsers($a_obj_id)
	{
		global $ilDB;
		$query = "SELECT COUNT(usr_id) users FROM event_participants "
		        . "WHERE registered = 1 "
				. "AND event_id = ". $ilDB->quote($a_obj_id, 'integer');
		$res = $ilDB->query($query);
		if ($row = $ilDB->fetchObject($res))
		{
			return $row->users;
		}
	}
	// fim.

	//fim: [memsess] add parameter to look only for registrations at the same level
	/**
	 * lookup registrations
	 *
	 * @access public
	 * @param	int	object_id
	 * @oaram 	int ref_id (if given, lookup and cache all registration settings on the same level)
	 * @return
	 * @static
	 */
	public static function _lookupRegistration($a_obj_id, $a_ref_id = null)
	{
		global $tree, $ilDB;
				
		// cache check
		if (isset(self::$registrations[$a_obj_id]))
		{
			return self::$registrations[$a_obj_id];
		}
		
		if (isset($a_ref_id))
		{
			// if a ref id is given, all sessions on that level are searched
			$parent_ref_id = $tree->getParentId($a_ref_id);
			
			$query = "SELECT e.registration, e.obj_id". 
				" FROM tree t".
				" INNER JOIN object_reference r ON t.child = r.ref_id".
				" INNER JOIN event e ON e.obj_id = r.obj_id".
				" WHERE t.parent = " . $ilDB->quote($parent_ref_id, "integer");
		}
		else
		{
			// else look only for the object
			$query = "SELECT registration,obj_id FROM event WHERE obj_id =" . $ilDB->quote($a_obj_id, "integer");
		}
		//echo $query;
			
		// execude the query and add the result to the cache
		$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			self::$registrations[$row->obj_id] = (bool) $row->registration;
		}
		return self::$registrations[$a_obj_id];
	}
	// fim.
	
	
	/**
	 * lookup if user has registered
	 *
	 * @access public
	 * @param int usr_id
	 * @param int obj_id
	 * @return
	 * @static
	 */
	public static function _lookupRegistered($a_usr_id,$a_obj_id)
	{
		if(isset(self::$registered[$a_usr_id]))
		{
			return (bool) self::$registered[$a_usr_id][$a_obj_id];
		}
		
		global $ilDB,$ilUser;

		// fim: [bugfix] take the user parameter instead of ilUser
		$a_usr_id = $a_usr_id ? $a_usr_id : $ilUser->getId();
		$query = "SELECT event_id, registered FROM event_participants WHERE usr_id = ".$ilDB->quote($a_usr_id,'integer');
		// fim.

		$res = $ilDB->query($query);
		self::$registered[$a_usr_id] = array();
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			self::$registered[$a_usr_id][$row->event_id] = (bool) $row->registered;
		}
		return (bool) self::$registered[$a_usr_id][$a_obj_id];
	}
	
}
?>