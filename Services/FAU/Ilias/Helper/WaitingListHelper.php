<?php

namespace FAU\Ilias\Helper;

/**
 * trait for providing additional ilWaitingList methods
 */
trait WaitingListHelper 
{
    // fau: fairSub#45 - class variable for users to confirm
    private array $to_confirm_ids = [];
    // fau.

    // fau: fairSub#46 - class variable for first blocled places
    private int $first_blocked_places = 0;
    // fau.


    // fau: fairSub#47 - class variable for users on a waiting list position	(position => user_id[])
    private array $position_ids = [];
    // fau.

    public static array $is_on_list = [];

    // fau: fairSub#48 - static array variable for confirmation status
    public static array $to_confirm = [];
    // fau.

    // fau: fairSub#49 - new function getCountToConfirm()
    /**
     * get number of users that need a confirmation
     *
     * @access public
     * @return int number of users
     */
    public function getCountToConfirm(): int
    {
        return count($this->to_confirm_ids);
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
    public function addWithChecks($a_usr_id, $a_rol_id, $a_subject = '', $a_to_confirm = WaitingListConstantsHelper::REQUEST_NOT_TO_CONFIRM, $a_sub_time = null)
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
        $count_first_blocked = true;
        $this->user_ids = array();
        $this->position_ids = array();
        $this->to_confirm_ids = array();
        $this->first_blocked_places = 0;

        // calculate
        foreach ($sort as $sub_time => $user_ids) {
            $position++;
            $pos_has_addable = false;
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
                if ($this->users[$user_id]['to_confirm'] == WaitingListConstantsHelper::REQUEST_TO_CONFIRM) {
                    $this->to_confirm_ids[] = $user_id;
                    if ($count_first_blocked) {
                        $this->first_blocked_places++;
                    }
                } else {
                    $pos_has_addable = true;
                }
            }
            // stop counting the first blocked places if position has at least one user without confirmation meed
            if ($pos_has_addable) {
                $count_first_blocked = false;
            }
        }
    }
    // fau.

        // fau: campoSub - new functions getModuleId, updateModuleId
    /**
     * Get the module id
     * @param int $a_usr_id
     * @return	int
     */
    public function getModuleId($a_usr_id)
    {
        return $this->users[$a_usr_id]['module_id'] ?? null;
    }

    /**
     * Update the module id
     * @param int $a_usr_id
     * @param int|null $a_module_id
     */
    public function updateModuleId($a_usr_id, $a_module_id)
    {
        global $ilDB;

        $query = "UPDATE crs_waiting_list " .
            "SET module_id = " . $ilDB->quote((int) $a_module_id, 'integer') . " " .
            "WHERE usr_id = " . $ilDB->quote($a_usr_id, 'integer') . " " .
            "AND obj_id = " . $ilDB->quote($this->getObjId(), 'integer') . " ";
        $ilDB->manipulate($query);

        $this->users[$a_usr_id]['module_id'] = $a_module_id;
    }
    // fau.



    // fau: fairSub - new function updateSubject(), acceptOnList()
    /**
     * update subject
     * @param int $a_usr_id
     * @param string $a_subject
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
            "SET to_confirm = " . $ilDB->quote(WaitingListConstantsHelper::REQUEST_CONFIRMED, 'integer') . " " .
            "WHERE usr_id = " . $ilDB->quote($a_usr_id, 'integer') . " " .
            "AND obj_id = " . $ilDB->quote($this->getObjId(), 'integer');
        $res = $ilDB->manipulate($query);

        $this->users[$a_usr_id]['to_confirm'] = WaitingListConstantsHelper::REQUEST_CONFIRMED;
        $this->recalculate();
        return true;
    }

    // fau.

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
            return WaitingListConstantsHelper::REQUEST_NOT_ON_LIST;
        }
    }
    // fau.    

    // fau: fairSub - new function getFirstBlockedPlaces()
    /**
     * Get the number of places that must be kept free before the first user can be added from the list
     * This is the number of pending confirmation with earlier or equal submision time than the first user without
     * Is can be compared with the free places to decide if at least one free place can be filled
     *
     * Don't use this to calculate the whole amount of places that can be filled!
     * After the first user is added, other pending confirmations may block further users
     * So the recalculate() function has to be called after adding a user from the list
     *
     * @see self::recalculate()
     */
    public function getFirstBlockedPlaces() : int
    {
        return $this->first_blocked_places;
    }
    // fau.


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
}