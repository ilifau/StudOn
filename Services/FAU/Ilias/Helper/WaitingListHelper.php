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
}