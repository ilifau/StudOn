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
}