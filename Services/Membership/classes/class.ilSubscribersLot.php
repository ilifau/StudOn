<?php
/* fim: [memlot] new class. */

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* class for lot lists
* 
* @author Fred Neumann <sfred.neumann@fim.uni-erlangen.de>
* @version $Id$
*
* @ingroup ServicesMembership
*/
class ilSubscribersLot
{
	private $db = null;
	private $part_obj = null;
	private $obj_id = 0;
	private $type = '';
	private $user_ids = array();

	private $max_members = false;
	private $min_time = false;

	private $enabled = true;

	private $status = false;
	private $status_message = '(not checked)';

	/**
	 * Constructor
	 *
	 * @access public
	 * @param int obj_id 
	 */
	public function __construct($a_obj_id)
	{
		global $ilDB, $lng;

		$this->db = $ilDB;
		$this->lng = $lng;
		$this->obj_id = $a_obj_id;
		$this->type = ilObject::_lookupType($a_obj_id);
		
		$this->read();
	}

	/**
	* fim: [meminf] count unique subscribers for several objects
	*
	* @param    array   object ids
	* @return   array   user ids
	*/
	static function _countSubscribers($a_obj_ids = array())
	{
	    global $ilDB;

		$query = "SELECT COUNT(DISTINCT usr_id) users FROM il_subscribers_lot WHERE "
		. $ilDB->in('obj_id', $a_obj_ids, false, 'integer');

		$result = $ilDB->query($query);
		$row = $ilDB->fetchAssoc($result);

		return $row['users'];
	}
	// fim.

	/**
	 * get obj id
	 *
	 * @access public
	 * @return int obj_id
	 */
	public function getObjId()
	{
		return $this->obj_id;
	}

	
	/**
	 * set the participants object (needed for calculations and drawing lots)
	 *
	 * @access public
	 * @param object  course or group participants object
	 */
	public function setParticipantsObject($a_part_obj)
	{
		$this->part_obj = $a_part_obj;
	}
	
	/**
	 * set enabled/disabled status
	 * 
	 * @param boolean	enables status (true/false)
	 */
	public function setEnabled($a_enabled)
	{
		$this->enabled = $a_enabled;
	}
	
	
	/**
	 * set the maximum members
	*/
	public function setMaxMembers($a_max_members)
	{
		$this->max_members = $a_max_members;
	}
	
	/**
	 * set the minimum time (unix timestamp)
	*/
	public function setMinTime($a_mintime)
	{
		$this->min_time = $a_mintime;
	}
	
	/**
	 * get the free places
	*/
	public function getFreePlaces()
	{
		if (!$this->max_members  or !isset($this->part_obj))
		{
			return false;
		}
		
		return max($this->max_members - $this->part_obj->getCountMembers(), 0);
	}
	
	
	/**
	 * check the status for drawing lots
	*/
	public function checkStatus()
	{
		$this->status_message = "";
		
		// check if drawing lots is enabled
		if (!$this->enabled)
		{
			$this->status_message = $this->lng->txt('mem_lot_message_not_enabled');
			return false;
		}
		
		// check if time for drawing lot is reached
		if (time() < $this->min_time)
		{
			include_once('./Services/Calendar/classes/class.ilDate.php');
			include_once('./Services/Calendar/classes/class.ilDatePresentation.php');

			$this->status_message = sprintf($this->lng->txt('mem_lot_message_time'),
			    ilDatePresentation::formatDate(new ilDateTime($this->min_time,IL_CAL_UNIX)));
			return false;
		}
		
		// check if free places are available
		if (!$this->getFreePlaces())
		{
			$this->status_message = $this->lng->txt('mem_lot_message_no_places');
			return false;
		}
		
		// check if waiting subscription exist
		if ($this->part_obj->getCountSubscribers() > 0)
		{
			$this->status_message = $this->lng->txt('mem_lot_message_subscribers');
			return false;
		}
		
		// check if lot list has users
		if (!$this->getCountUsers())
		{
			$this->status_message = $this->lng->txt('mem_lot_message_no_candidates');
			return false;
		}
		
		// drawing lots is possible
		$this->status_message = $this->lng->txt('mem_lot_message_possible');
		return true;
	}
	
	
	/**
	 * check the status for drawing lots
	*/
	public function getStatusMessage()
	{
		return $this->status_message;
	}
	
	
	
	/**
	 * add  a list of course subscribers to the lot list
	 *
	 * @access public
	 * @param 	array 	user ids
	 * @return 	int     num of added users
	 */
	public function addSubscribersToLot($a_subscribers)
	{
		global $ilias;
		
		if (!is_object($this->part_obj))
		{
			$ilias->raiseError('participants object not initialized' ,$this->ilias->error_obj->MESSAGE);
		}
		
		$added = 0;
		foreach ($a_subscribers as $usr_id)
		{
			if ($this->addToList($usr_id))
			{
				$added++;
			}
			$this->part_obj->deleteSubscriber($usr_id);
		}

		return $added;
	}

	
	/**
	 * remove a list of subscribers from the lot list
	 *
	 * @access public
	 * @param 	array 	user ids
	 * @return  int     num of remoced users
	 */
	public function removeSubscribersFromLot($a_subscribers)
	{
		global $ilias;
		
		if (!is_object($this->part_obj))
		{
			$ilias->raiseError('participants object not initialized' ,$this->ilias->error_obj->MESSAGE);
		}
		
		$removed = 0;
		foreach ($a_subscribers as $usr_id)
		{
			if ($this->removeFromList($usr_id))
			{
				$removed++;
				
				// TODO: email notification to the user ??? 
			}
		}

		return $removed;
	}
	
	
	/**
	 * remove members of associated courses from the lot list
	 */
	public function cleanLots()
	{
		global $lng;
		
		if (!is_object($this->part_obj))
		{
			$ilias->raiseError('participants object not initialized' ,$this->ilias->error_obj->MESSAGE);
		}

		// get the grouping conditions to respect
		require_once("Modules/Course/classes/class.ilObjCourseGrouping.php");
		$conditions = ilObjCourseGrouping::_getGroupingConditions($this->obj_id, $this->type);
		
		// get list of candidates
		$candidates = $this->getUserIds();
		
		$removed = 0;
		foreach ($candidates as $user_id)
		{
 			// check if user is already member in course/group or one of the other groups/course
			if ($this->part_obj->isAssigned($user_id)
				or ilObjCourseGrouping::_findGroupingMembership($user_id, $this->type, $conditions))
			{				
				// remove user from lot lost
				self::_removeUser($this->obj_id, $user_id);
				$removed++;
				
				// take the user from the lot lists of the other groups/courses
				foreach ($conditions as $condition)
				{
					self::_removeUser($condition['target_obj_id'], $user_id);
				}
			}
		}
		
		$this->read();
		$this->status_message = sprintf($lng->txt('mem_lot_cleaned'), $removed);
		
		return $removed;
	}
	
	/**
	 * draw lots
	 *
	 * @return       added users
	 */
	public function drawLots()
	{
		global $lng;
		
		if (!is_object($this->part_obj))
		{
			$ilias->raiseError('participants object not initialized' ,$this->ilias->error_obj->MESSAGE);
		}

		// get the grouping conditions to respect
		require_once("Modules/Course/classes/class.ilObjCourseGrouping.php");
		$conditions = ilObjCourseGrouping::_getGroupingConditions($this->obj_id, $this->type);

		// get randomized list of candidates
		$free = $this->getFreePlaces();
		$candidates = $this->getUserIds();
		shuffle($candidates);
		
		$removed = 0;
		$succeeded = 0;
		while ($free > 0 and count($candidates) > 0)
		{
   			$user_id = array_pop($candidates);

			// check if user is already member in one of the other groups/course
			if (ilObjCourseGrouping::_findGroupingMembership($user_id, $this->type, $conditions))
			{
				// remove user from lot lost
				self::_removeUser($this->obj_id, $user_id);
				$removed++;
			}
			else
			{
				// add and notify user
				switch ($this->type)
				{
					case 'crs':
						$this->part_obj->add($user_id,IL_CRS_MEMBER);
						$this->part_obj->sendNotification($this->part_obj->NOTIFY_ACCEPT_USER,$user_id);
						break;

					case 'grp':
						include_once './Modules/Group/classes/class.ilGroupMembershipMailNotification.php';
						$this->part_obj->add($user_id,IL_GRP_MEMBER);
						$this->part_obj->sendNotification(ilGroupMembershipMailNotification::TYPE_ADMISSION_MEMBER,$user_id);
						break;
				}

				// remove user from lot lost
				self::_removeUser($this->obj_id, $user_id);
				$succeeded++;
				$free--;
			}

			// take the user from the lot lists of the other groups/courses
			foreach ($conditions as $condition)
			{
				self::_removeUser($condition['target_obj_id'], $user_id);
			}
		}

		$this->read();
		$this->status_message = sprintf($lng->txt('mem_lot_drawn'),$succeeded, $removed);
		
		return $succeeded;
	}


	/**
	 * add to list
	 *
	 * @access public
	 * @param int usr_id
	 */
	public function addToList($a_usr_id)
	{
		global $ilDB;

		if ($this->isOnList($a_usr_id))
		{
			return false;
		}
		$query = "INSERT INTO il_subscribers_lot ".
			"SET obj_id = ". $ilDB->quote($this->getObjId(), 'integer').", ".
			"usr_id = ". $ilDB->quote($a_usr_id, 'integer');

		$this->db->query($query);
		$this->user_ids[] = $a_usr_id;

		return true;
	}

	/**
	 * remove from list
	 *
	 * @access public
	 * @param int usr_id
	 */
	public function removeFromList($a_usr_id)
	{
		$found = array_search($a_usr_id, $this->user_ids);
		if ($found !== false)
  		{
			self::_removeUser($this->getObjId(), $a_usr_id);
			unset($this->user_ids[$found]);
			return true;
		}
		else
		{
			return false;
		}
	}


	/**
	 * check if is on lot list
	 *
	 * @access public
	 * @param int usr_id
	 * @return
	 */
	public function isOnList($a_usr_id)
	{
		return in_array($a_usr_id, $this->user_ids);
	}

	/**
	 * Check if a user on the lot list
	 * @return bool
	 * @param object $a_usr_id
	 * @param object $a_obj_id
	 * @access public
	 * @static
	 */
	public static function _isOnList($a_usr_id,$a_obj_id)
	{
		global $ilDB;

		$query = "SELECT usr_id ".
			"FROM il_subscribers_lot ".
			"WHERE obj_id = ".$ilDB->quote($a_obj_id, 'integer')." ".
			"AND usr_id = ".$ilDB->quote($a_usr_id, 'integer');
		$res = $ilDB->query($query);
		return $res->numRows() ? true : false;
	}


	/**
	 * get number of users
	 *
	 * @access public
	 * @return int number of users
	 */
	public function getCountUsers()
	{
		return count($this->user_ids);
	}
	
	
	/**
	 * Get all user ids of users on lot list
	 */
	public function getUserIds()
	{
	 	return $this->user_ids ? $this->user_ids : array();
	}


	/**
	 * Read lot list 
	 *
	 * @access private
	 * @param
	 * @return
	 */
	private function read()
	{
		global $ilDB;
		
		$this->user_ids = array();

		$query = "SELECT * FROM il_subscribers_lot ".
			"WHERE obj_id = ".$ilDB->quote($this->getObjId(), 'integer');

		$res = $this->db->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$this->user_ids[] = $row->usr_id;
		}
		return true;
	}
	
	
	/**
	 * get number of users
	 *
	 * @access public
	 * @return int number of users
	 */
	public function _getCountUsers($a_obj_id)
	{
		global $ilDB;

		$query = "SELECT count(usr_id) count_users FROM il_subscribers_lot ".
			"WHERE obj_id = ".$ilDB->quote($a_obj_id, 'integer');

		$res = $ilDB->query($query);
		$row = $res->fetchRow(DB_FETCHMODE_ASSOC);
		return $row['count_users'];
	}


	/**
	 * delete all
	 *
	 * @access public
	 * @param int obj_id
	 * @static
	 */
	public static function _deleteAll($a_obj_id)
	{
		global $ilDB;

		$query = "DELETE FROM il_subscribers_lot WHERE obj_id = ".$ilDB->quote($a_obj_id, 'integer');
		$ilDB->query($query);

		return true;
	}


	/**
	 * Delete user
	 *
	 * @access public
	 * @param int user_id
	 * @static
	 */
	public static function _deleteUser($a_usr_id)
	{
		global $ilDB;

		$query = "DELETE FROM il_subscribers_lot WHERE usr_id = ".$ilDB->quote($a_usr_id, 'integer');
		$ilDB->query($query);

		return true;
	}
	
	
	/**
	 * remove usr from list
	 *
	 * @access public
	 * @param 	int 		obj_id
	 * @param 	int 		usr_id
	 * @return
	 */
	public function _removeUser($a_obj_id, $a_usr_id)
	{
		global $ilDB;

		$query = "DELETE FROM il_subscribers_lot ".
			" WHERE obj_id = ".$ilDB->quote($a_obj_id, 'integer').
			" AND usr_id = ".$ilDB->quote($a_usr_id, 'integer');

		$ilDB->query($query);
		return true;
	}

}
?>
