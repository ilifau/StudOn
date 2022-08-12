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

/**
* Base class for course and group waiting lists
*
* @author Stefan Meyer <smeyer.ilias@gmx.de>
* @version $Id$
*
* @ingroup ServicesMembership
*/

abstract class ilWaitingList
{
    // fau: fairSub - class constants for confirmation status
    const REQUEST_NOT_ON_LIST = -1;
    const REQUEST_NOT_TO_CONFIRM = 0;
    const REQUEST_TO_CONFIRM = 1;
    const REQUEST_CONFIRMED = 2;
    // fau.

    private $db = null;
    private $obj_id = 0;
    private $user_ids = array();
    private $users = array();
    
    // fau: fairSub - class variable for users to confirm
    private $to_confirm_ids = array();
    // fau.

    // fau: fairSub - class variable for users on a waiting list position	(position => user_id[])
    private $position_ids = array();
    // fau.

    public static $is_on_list = array();

    // fau: fairSub - static array variable for confirmation status
    public static $to_confirm = array();
    // fau.

    /**
     * Constructor
     *
     * @access public
     * @param int obj_id
     */
    public function __construct($a_obj_id)
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];

        $this->db = $ilDB;
        $this->obj_id = $a_obj_id;

        $this->read();
    }

    // fau: limitSub - new function _countUniqueSubscribers()
    /**
    * Count unique subscribers for several objects
    *
    * @param    array   object ids
    * @return   array   user ids
    */
    public static function _countUniqueSubscribers($a_obj_ids = array())
    {
        global $ilDB;

        $query = "SELECT COUNT(DISTINCT usr_id) users FROM crs_waiting_list WHERE "
        . $ilDB->in('obj_id', $a_obj_ids, false, 'integer');

        $result = $ilDB->query($query);
        $row = $ilDB->fetchAssoc($result);

        return $row['users'];
    }
    // fau.


    /**
     * Lookup waiting lit size
     * @param int $a_obj_id
     */
    public static function lookupListSize($a_obj_id)
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];
        
        $query = 'SELECT count(usr_id) num from crs_waiting_list WHERE obj_id = ' . $ilDB->quote($a_obj_id, 'integer');
        $res = $ilDB->query($query);
        
        while ($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT)) {
            return (int) $row->num;
        }
        return 0;
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
        global $DIC;

        $ilDB = $DIC['ilDB'];

        $query = "DELETE FROM crs_waiting_list WHERE obj_id = " . $ilDB->quote($a_obj_id, 'integer') . " ";
        $res = $ilDB->manipulate($query);

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
        global $DIC;

        $ilDB = $DIC['ilDB'];

        $query = "DELETE FROM crs_waiting_list WHERE usr_id = " . $ilDB->quote($a_usr_id, 'integer');
        $res = $ilDB->manipulate($query);

        return true;
    }
    
    /**
     * Delete one user entry
     * @param int $a_usr_id
     * @param int $a_obj_id
     * @return
     */
    public static function deleteUserEntry($a_usr_id, $a_obj_id)
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];
        
        $query = "DELETE FROM crs_waiting_list " .
            "WHERE usr_id = " . $ilDB->quote($a_usr_id, 'integer') . ' ' .
            "AND obj_id = " . $ilDB->quote($a_obj_id, 'integer');
        $ilDB->query($query);
        return true;
    }
    

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

    // fau: fairSub - add subject, to_confirm and sub_time as parameter, avoid re-reading
    /**
     * add to list
     *
     * @param 	int 		$a_usr_id
     * @param 	string		$a_subject
     * @param	int 		$a_to_confirm
     * @param	int			$a_sub_time
     * @return bool
     */
    public function addToList($a_usr_id, $a_subject = '', $a_to_confirm = self::REQUEST_NOT_TO_CONFIRM, $a_sub_time = null)
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];
        
        if ($this->isOnList($a_usr_id)) {
            return false;
        }

        $a_sub_time = empty($a_sub_time) ? time() : $a_sub_time;

        $query = "INSERT INTO crs_waiting_list (obj_id,usr_id,sub_time, subject, to_confirm) " .
            "VALUES (" .
            $ilDB->quote($this->getObjId(), 'integer') . ", " .
            $ilDB->quote($a_usr_id, 'integer') . ", " .
            $ilDB->quote($a_sub_time, 'integer') . ", " .
            $ilDB->quote($a_subject, 'text') . ", " .
            $ilDB->quote($a_to_confirm, 'integer') . " " .
            ")";
        $res = $ilDB->manipulate($query);


        if ($res == 0) {
            return false;
        } else {
            $this->users[$a_usr_id]['time'] = $a_sub_time;
            $this->users[$a_usr_id]['usr_id'] = $a_usr_id;
            $this->users[$a_usr_id]['subject'] = $a_subject;
            $this->users[$a_usr_id]['to_confirm'] = $a_to_confirm;
            $this->recalculate();
            return true;
        }
    }
    // fau.

    // fau: fairSub - new function addWithChecks
    /**
     * adds a user to the waiting list with check for membership
     *
     * @access public
     * @param 	int 	$a_usr_id
     * @param 	int		$a_rol_id
     * @param 	string	$a_subject
     * @param	int 	$a_to_confirm
     * @param	int		$a_sub_time
     * @return bool
     */
    public function addWithChecks($a_usr_id, $a_rol_id, $a_subject = '', $a_to_confirm = self::REQUEST_NOT_TO_CONFIRM, $a_sub_time = null)
    {
        global $ilDB;

        if ($this->isOnList($a_usr_id)) {
            return false;
        }

        $a_sub_time = empty($a_sub_time) ? time() : $a_sub_time;

        // insert user only on the waiting list if not in member role and not on list
        $query = "INSERT INTO crs_waiting_list (obj_id, usr_id, sub_time, subject, to_confirm) "
                . " SELECT %s obj_id, %s usr_id, %s sub_time, %s subject, %s to_confirm FROM DUAL "
                . " WHERE NOT EXISTS (SELECT 1 FROM rbac_ua WHERE usr_id = %s AND rol_id = %s) "
                . " AND NOT EXISTS (SELECT 1 FROM crs_waiting_list WHERE obj_id = %s AND usr_id = %s)";

        $res = $ilDB->manipulateF(
            $query,
            array(	'integer', 'integer', 'integer', 'text', 'integer',
                                'integer', 'integer',
                                'integer', 'integer'),
            array(	$this->getObjId(), $a_usr_id, $a_sub_time, $a_subject, $a_to_confirm,
                                $a_usr_id, $a_rol_id,
                                $this->getObjId(), $a_usr_id)
        );

        if ($res == 0) {
            return false;
        } else {
            $this->users[$a_usr_id]['time'] = $a_sub_time;
            $this->users[$a_usr_id]['usr_id'] = $a_usr_id;
            $this->users[$a_usr_id]['subject'] = $a_subject;
            $this->users[$a_usr_id]['to_confirm'] = $a_to_confirm;
            $this->recalculate();
            return true;
        }
    }
    // fau.


    /**
     * update subscription time
     *
     * @access public
     * @param int usr_id
     * @param int subsctription time
     */
    public function updateSubscriptionTime($a_usr_id, $a_subtime)
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];
        
        $query = "UPDATE crs_waiting_list " .
            "SET sub_time = " . $ilDB->quote($a_subtime, 'integer') . " " .
            "WHERE usr_id = " . $ilDB->quote($a_usr_id, 'integer') . " " .
            "AND obj_id = " . $ilDB->quote($this->getObjId(), 'integer') . " ";
        $res = $ilDB->manipulate($query);

        // fau: fairSub - recalculate after updating time
        $this->users[$a_usr_id]['time'] = (int) $a_subtime;
        $this->recalculate();
        // fau.

        return true;
    }

    // fau: fairSub - new function updateSubject(), acceptOnList()
    /**
     * update subject
     * @param int $a_usr_id
     * @param int $a_subject
     * @return true
     */
    public function updateSubject($a_usr_id, $a_subject)
    {
        global $ilDB;

        $query = "UPDATE crs_waiting_list " .
            "SET subject = " . $ilDB->quote($a_subject, 'text') . " " .
            "WHERE usr_id = " . $ilDB->quote($a_usr_id, 'integer') . " " .
            "AND obj_id = " . $ilDB->quote($this->getObjId(), 'integer') . " ";
        $res = $ilDB->manipulate($query);

        $this->users[$a_usr_id]['subject'] = $a_subject;
        return true;
    }


    /**
     * Accept a subscription request on the list
     * @param int $a_usr_id
     * @return bool
     */
    public function acceptOnList($a_usr_id)
    {
        global $ilDB;

        $query = "UPDATE crs_waiting_list " .
            "SET to_confirm = " . $ilDB->quote(self::REQUEST_CONFIRMED, 'integer') . " " .
            "WHERE usr_id = " . $ilDB->quote($a_usr_id, 'integer') . " " .
            "AND obj_id = " . $ilDB->quote($this->getObjId(), 'integer');
        $res = $ilDB->manipulate($query);

        $this->users[$a_usr_id]['to_confirm'] = self::REQUEST_CONFIRMED;
        $this->recalculate();
        return true;
    }

    // fau.

    /**
     * remove usr from list
     *
     * @access public
     * @param int usr_id
     * @return
     */
    public function removeFromList($a_usr_id)
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];
        
        $query = "DELETE FROM crs_waiting_list " .
            " WHERE obj_id = " . $ilDB->quote($this->getObjId(), 'integer') . " " .
            " AND usr_id = " . $ilDB->quote($a_usr_id, 'integer') . " ";
        $res = $ilDB->manipulate($query);

        // fau: fairSub - avoid multiple reading
        unset($this->users[$a_usr_id]);
        $this->recalculate();
        // fau.
        return true;
    }

    /**
     * check if is on waiting list
     *
     * @access public
     * @param int usr_id
     * @return
     */
    public function isOnList($a_usr_id)
    {
        return isset($this->users[$a_usr_id]) ? true : false;
    }
    
    /**
     * Check if a user on the waiting list
     * @return bool
     * @param int $a_usr_id
     * @param int $a_obj_id
     * @access public
     * @static
     */
    public static function _isOnList($a_usr_id, $a_obj_id)
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];
        
        if (isset(self::$is_on_list[$a_usr_id][$a_obj_id])) {
            return self::$is_on_list[$a_usr_id][$a_obj_id];
        }
        
        $query = "SELECT usr_id " .
            "FROM crs_waiting_list " .
            "WHERE obj_id = " . $ilDB->quote($a_obj_id, 'integer') . " " .
            "AND usr_id = " . $ilDB->quote($a_usr_id, 'integer');
        $res = $ilDB->query($query);
        return $res->numRows() ? true : false;
    }

    // fau: fairSub - new static function _getStatus()
    /**
     * Get the status of a user
     * @return bool
     * @param int $a_usr_id
     * @param int $a_obj_id
     * @access public
     * @static
     */
    public static function _getStatus($a_usr_id, $a_obj_id)
    {
        global $ilDB;

        if (isset(self::$to_confirm[$a_usr_id][$a_obj_id])) {
            return self::$to_confirm[$a_usr_id][$a_obj_id];
        }

        $query = "SELECT to_confirm " .
            "FROM crs_waiting_list " .
            "WHERE obj_id = " . $ilDB->quote($a_obj_id, 'integer') . " " .
            "AND usr_id = " . $ilDB->quote($a_usr_id, 'integer');
        $res = $ilDB->query($query);
        if ($res->numRows()) {
            $row = $ilDB->fetchAssoc($res);
            return $row['to_confirm'];
        } else {
            return self::REQUEST_NOT_ON_LIST;
        }
    }
    // fau.


    /**
     * Preload on list info. This is used, e.g. in the repository
     * to prevent multiple reads on the waiting list table.
     * The function is triggered in the preload functions of ilObjCourseAccess
     * and ilObjGroupAccess.
     *
     * @param array $a_usr_ids array of user ids
     * @param array $a_obj_ids array of object ids
     */
    public static function _preloadOnListInfo($a_usr_ids, $a_obj_ids)
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];
        
        if (!is_array($a_usr_ids)) {
            $a_usr_ids = array($a_usr_ids);
        }
        if (!is_array($a_obj_ids)) {
            $a_obj_ids = array($a_obj_ids);
        }
        // fau: fairSub - fill also to_confirm info at preload
        foreach ($a_usr_ids as $usr_id) {
            foreach ($a_obj_ids as $obj_id) {
                self::$is_on_list[$usr_id][$obj_id] = false;
                self::$to_confirm[$usr_id][$obj_id] = self::REQUEST_NOT_ON_LIST;
            }
        }
        $query = "SELECT usr_id, obj_id, to_confirm " .
            "FROM crs_waiting_list " .
            "WHERE " .
            $ilDB->in("obj_id", $a_obj_ids, false, "integer") . " AND " .
            $ilDB->in("usr_id", $a_usr_ids, false, "integer");
        $res = $ilDB->query($query);
        while ($rec = $ilDB->fetchAssoc($res)) {
            self::$is_on_list[$rec["usr_id"]][$rec["obj_id"]] = true;
            self::$to_confirm[$rec["usr_id"]][$rec["obj_id"]] = $rec['to_confirm'];
        }
        // fau.
    }
    

    /**
     * get number of users
     *
     * @access public
     * @return int number of users
     */
    public function getCountUsers()
    {
        return count($this->users);
    }

    // fau: fairSub - new function getCountToConfirm()
    /**
     * get number of users that need a confirmation
     *
     * @access public
     * @return int number of users
     */
    public function getCountToConfirm()
    {
        return count($this->to_confirm_ids);
    }
    // fau.

    /**
     * get position
     *
     * @access public
     * @param int usr_id
     * @return position of user otherwise -1
     */
    public function getPosition($a_usr_id)
    {
        return isset($this->users[$a_usr_id]) ? $this->users[$a_usr_id]['position'] : -1;
    }


    // fau: fairSub - new function getPositionUsers(), getEffectivePosition(), getPositionOthers()

    /**
     * Get all position numbers
     */
    public function getAllPositions()
    {
        return array_keys($this->position_ids);
    }


    /**
     * get the count of users sharing a waiting list position
     * @param int $a_position	waiting list position
     * @return array 			user_id[]
     */
    public function getPositionUsers($a_position)
    {
        return (array) $this->position_ids[$a_position];
    }

    /**
     * Get the effective waiting list position
     * This counts all users sharing lower positions
     * @param int		$a_usr_id
     * @return int
     */
    public function getEffectivePosition($a_usr_id)
    {
        return isset($this->users[$a_usr_id]) ? $this->users[$a_usr_id]['effective'] : -1;
    }

    /**
     * Get information about waiting list position
     * @param int			$a_usr_id	user id
     * @param ilLanguage 	$a_lng		defaults to current user language, but may be be other for email notification
     * @return string					the effective position and info about others sharing it
     */
    public function getPositionInfo($a_usr_id, $a_lng = null)
    {
        global $lng;

        if (!isset($a_lng)) {
            $a_lng = $lng;
        }

        if (!isset($this->users[$a_usr_id])) {
            return $a_lng->txt('sub_fair_not_on_list');
        }

        $effective = $this->getEffectivePosition($a_usr_id);
        $others = count($this->getPositionUsers((int) $this->getPosition($a_usr_id))) - 1;

        if ($others == 0) {
            return (string) $effective;
        } else {
            return sprintf($a_lng->txt($others == 1 ? 'sub_fair_position_with_other' : 'sub_fair_position_with_others'), $effective, $others);
        }
    }
    // fau.

    // fau: fairSub - new functions getSubject(), isToConfirm(), getStatus()
    /**
     * Get the message of the entry
     * @param int $a_usr_id
     * @return	string	subject
     */
    public function getSubject($a_usr_id)
    {
        return isset($this->users[$a_usr_id]) ? $this->users[$a_usr_id]['subject'] : '';
    }


    /**
     * Get if a user needs a confirmation
     * @param int $a_usr_id
     * @return	boolean
     */
    public function isToConfirm($a_usr_id)
    {
        return isset($this->users[$a_usr_id]) ? ($this->users[$a_usr_id]['to_confirm'] == self::REQUEST_TO_CONFIRM) : false;
    }

    /**
     * Get the status of a user on the list
     */
    public function getStatus($a_usr_id)
    {
        return isset($this->users[$a_usr_id]) ? $this->users[$a_usr_id]['to_confirm'] : self::REQUEST_NOT_ON_LIST;
    }
    // fau.

    /**
     * get all users on waiting list
     *
     * @access public
     * @return array array(position,time,usr_id)
     */
    public function getAllUsers()
    {
        return $this->users ? $this->users : array();
    }
    
    /**
     * get user
     *
     * @access public
     * @param int usr_id
     * @return
     */
    public function getUser($a_usr_id)
    {
        return isset($this->users[$a_usr_id]) ? $this->users[$a_usr_id] : false;
    }
    
    /**
     * Get all user ids of users on waiting list
     *
     *
     */
    public function getUserIds()
    {
        return $this->user_ids ? $this->user_ids : array();
    }

    /**
     * Read waiting list
     *
     * @access private
     * @param
     * @return
     */
    private function read()
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];
        
        $this->users = array();

        // fau: fairSub - get subject and to_confirm
        // fau: fairSub - recalculate after reading, sorting is done there

        $query = "SELECT * FROM crs_waiting_list " .
            "WHERE obj_id = " . $ilDB->quote($this->getObjId(), 'integer');

        $res = $this->db->query($query);

        while ($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT)) {
            $this->users[$row->usr_id]['time'] = $row->sub_time;
            $this->users[$row->usr_id]['usr_id'] = $row->usr_id;
            $this->users[$row->usr_id]['subject'] = $row->subject;
            $this->users[$row->usr_id]['to_confirm'] = $row->to_confirm;
        }
        // fau: fairSub - recalculate data when list is read
        $this->recalculate();
        // fau.
        return true;
    }

    // fau: fairSub - new function recalculate()
    /**
     * Re-calculated additional data based on the raw data
     * This can ce called after manipulating the users array
     *  - shared waiting position
     *  - effective waiting position
     *  - list of all user_ids
     *  - list of user_ids on a shared position
     *  - list of users to be confirmed
     */
    private function recalculate()
    {
        // sort the users by subscription time
        $sort = array();
        foreach ($this->users as $user_id => $user) {
            $sort[$user['time']][] = $user_id;
        }
        ksort($sort, SORT_NUMERIC);

        // init calculated data
        $counter = 0;
        $position = 0;
        $previous = 0;
        $effective = 0;
        $this->user_ids = array();
        $this->position_ids = array();
        $this->to_confirm_ids = array();

        // calculate
        foreach ($sort as $sub_time => $user_ids) {
            $position++;
            foreach ($user_ids as $user_id) {
                $counter++;
                if ($position > $previous) {
                    $effective = $counter;
                    $previous = $position;
                }

                $this->users[$user_id]['position'] = $position; 	// shared waiting list position
                $this->users[$user_id]['effective'] = $effective;	// effective waiting list position (counting all users of lower positions)

                $this->user_ids[] = $user_id;
                $this->position_ids[$position][] = $user_id;
                if ($this->users[$user_id]['to_confirm'] == self::REQUEST_TO_CONFIRM) {
                    $this->to_confirm_ids[] = $user_id;
                }
            }
        }
    }
    // fau.

    /**
     * Check if the fair subscription period can be changed
     * This is not allowed if gegistrations are affected by a reduced period
     * @param integer $a_obj_id
     * @param integer $a_old_time
     * @param integer $a_new_time
     * @return bool
     */
    public static function _changeFairTimeAllowed($a_obj_id, $a_old_time, $a_new_time)
    {
        global $ilDB;

        if ($a_new_time < $a_old_time) {
            $query = "SELECT count(*) affected FROM crs_waiting_list " .
                " WHERE obj_id = " . $ilDB->quote($a_obj_id, 'integer') .
                " AND sub_time <= " . $ilDB->quote($a_old_time, 'integer') .
                " AND sub_time > " . $ilDB->quote($a_new_time, 'integer');

            $result = $ilDB->query($query);
            $row = $ilDB->fetchAssoc($result);

            if ($row['affected'] > 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Change the period of fair subscriptions
     * This will set the date of all registrations before to the new end time
     * @param integer $a_obj_id
     * @param integer $a_old_time
     * @param integer $a_new_time
     */
    public static function _changeFairTime($a_obj_id, $a_old_time, $a_new_time)
    {
        global $ilDB;

        $query = "UPDATE crs_waiting_list " .
            " SET sub_time = " . $ilDB->quote($a_new_time, 'integer') .
            " WHERE obj_id = " . $ilDB->quote($a_obj_id, 'integer') .
            " AND sub_time < " . $ilDB->quote($a_new_time, 'integer');

        $ilDB->manipulate($query);
    }
}
