<?php
/* Copyright (c) 1998-2011 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* fim: [memad] new class for waiting list administration
* 
* @author Fred Neumann <sfred.neumann@fim.uni-erlangen.de>
* @version $Id$
*
* @ingroup ServicesMembership
*/
class ilWaitingListAdministration
{
	/**
	 * list of object ids to be concerned
	 * 
	 * @var array
	 */
	protected $obj_ids = array();
	
	/**
	 * course / group objects 
	 * 
	 * @var array obj_id => object
	 */
	protected $base_objects = array();
	
	
	/**
	 * waiting list objects 
	 * 
	 * @var array	obj_id => waiting list
	 */
	protected $wait_objects = array();
	
	
	/**
	 * participant objects 
	 * 
	 * @var array	obj_id => participants object
	 */
	protected $part_objects = array();
	
	
	
	/**
	 * info messages to be shown at the end
	 * 
	 * @var array
	 */
	protected $infos = array();
	
	
	/**
	 * Constructor
	 * 
	 *  var		array	ob_ids of courses /groups
	 */
	function __construct($a_obj_ids = array())
	{
		require_once('Modules/Course/classes/class.ilObjCourse.php');
		require_once('Modules/Course/classes/class.ilCourseWaitingList.php');
		require_once('Modules/Course/classes/class.ilCourseParticipants.php');
		require_once('Modules/Group/classes/class.ilObjGroup.php');
		require_once('Modules/Group/classes/class.ilGroupWaitingList.php');
		require_once('Modules/Group/classes/class.ilGroupParticipants.php');
		
		$this->obj_ids = $a_obj_ids;
		
		foreach ($a_obj_ids as $obj_id)
		{
			switch(ilObject::_lookupType($obj_id))
			{
				case 'crs':
					$base_obj = new ilObjCourse($obj_id, false);
					$wait_obj = new ilCourseWaitingList($obj_id);
					$part_obj = ilCourseParticipants::_getInstanceByObjId($obj_id);
					break;
					
				case 'grp':
					$base_obj = new ilObjGroup($obj_id, false);
					$wait_obj = new ilGroupWaitingList($obj_id);
					$part_obj = ilGroupParticipants::_getInstanceByObjId($obj_id);
					break;
			}

			$this->base_objects[$obj_id] = $base_obj;
			$this->part_objects[$obj_id] = $part_obj;
			$this->wait_objects[$obj_id] = $wait_obj;
		}
	}
	

	
	/**
	 * fill the free places in courses/groups from the waiting lists 
	 * - in order of their subscription (accross different courses / groups)
	 * - respecting already existing memberships 
	 */
	function fillMembers()
	{
		global $ilDB;
		
		// get the waiting list entries of all objects ordered by submission time
		$query = "SELECT * FROM crs_waiting_list"
			. " WHERE ". $ilDB->in('obj_id', $this->obj_ids, false, 'integer')
			. " ORDER BY sub_time";
		$result = $ilDB->query($query);
		
		while ($row = $ilDB->fetchObject($result))
		{
			$user_id = $row->usr_id;
			$obj_id = $row->obj_id;
			$type = $this->base_objects[$obj_id]->getType();		

			// check if user is already removed from list (e.g. by grouping condition)
			if (!$this->wait_objects[$obj_id]->isOnList($user_id))
			{
				continue;
			}
			
			// get the grouping conditions to respect
			require_once("Modules/Course/classes/class.ilObjCourseGrouping.php");
			$conditions = ilObjCourseGrouping::_getGroupingConditions($obj_id, $type);

			// check if user is already member in one of the groups/courses
			if (ilObjCourseGrouping::_findGroupingMembership($user_id, $type, $conditions))
			{
				$this->wait_objects[$obj_id]->removeFromList($user_id);	
				$this->addInfo('removed', $obj_id, $user_id);
				$this->removeByConditions($conditions, $user_id);
			}
			elseif ($this->checkFreePlaces($obj_id))
			{
				// add and notify user
				switch ($type)
				{
					case 'crs':
						$this->part_objects[$obj_id]->add($user_id,IL_CRS_MEMBER);
						$this->part_objects[$obj_id]->sendNotification($this->part_obj->NOTIFY_ACCEPT_USER,$user_id);
						break;

					case 'grp':
						include_once './Modules/Group/classes/class.ilGroupMembershipMailNotification.php';
						$this->part_objects[$obj_id]->add($user_id,IL_GRP_MEMBER);
						$this->part_objects[$obj_id]->sendNotification(ilGroupMembershipMailNotification::TYPE_ADMISSION_MEMBER,$user_id);
						break;
				}
				
				$this->addInfo('added', $obj_id, $user_id);
				
				$this->wait_objects[$obj_id]->removeFromList($user_id);	
				$this->removeByConditions($conditions, $user_id);
			}
		} 
	}
	
	
	/** 
	 * remove a user from all waiting lists of grouped courses/groups
	 */
	function removeByConditions($a_conditions, $a_user_id)
	{
		foreach ($a_conditions as $condition)
		{
			$obj_id = $condition['target_obj_id'];
			
			// get the corrresponding waiting list
			if (!isset($this->wait_objects[$obj_id]))
			{
				switch(ilObject::_lookupType($obj_id))
				{
					case 'crs':
						$this->wait_objects[$obj_id] = new ilCourseWaitingList($obj_id);
						break;
						
					case 'grp':
						$this->wait_objects[$obj_id] = new ilGroupWaitingList($obj_id);
						break;
				}
			}
			
			// remove the user
			$this->wait_objects[$obj_id]->removeFromList($a_user_id);
		}
	}
	
	
	/**
	 * check if a the course/group has  free places
	 *
	 * @param 	int	obj_id
	 */
	function checkFreePlaces($a_obj_id)
	{
		$object = $this->base_objects[$a_obj_id];
		
		switch ($object->getType())
		{
			case 'crs':
				if (!$object->isSubscriptionMembershipLimited())
				{	
					return true;
				}
				else
				{
					$max_members = $object->getSubscriptionMaxMembers();
				}
				break;

			case 'grp':
				if (!$object->isMembershipLimited())
				{
					return true;
				}
				else
				{
					$max_members = $object->getMaxMembers();
				}
				break;
		}
		
		$participants = $this->part_objects[$a_obj_id]->getCountMembers();
		
		if ($max_members > $participants)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	
	function addInfo($a_info, $a_obj_id, $a_user_id)
	{
		$this->infos[] = $a_info . ", " . ilObjUser::_lookupLogin($a_user_id) . ", ". ilObject::_lookupTitle($a_obj_id);
	}
	
	
	function getInfos()
	{
		return implode("<br/>\n", $this->infos);
	}
}
 ?>