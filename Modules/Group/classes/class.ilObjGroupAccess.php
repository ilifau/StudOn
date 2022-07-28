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
    public function _checkAccess($a_cmd, $a_permission, $a_ref_id, $a_obj_id, $a_user_id = "")
    {
        global $DIC;

        $ilUser = $DIC['ilUser'];
        $lng = $DIC['lng'];
        $rbacsystem = $DIC['rbacsystem'];
        $ilAccess = $DIC['ilAccess'];

        if ($a_user_id == "") {
            $a_user_id = $ilUser->getId();
        }
        
        switch ($a_cmd) {
            case "info":
            
                include_once './Modules/Group/classes/class.ilGroupParticipants.php';
                if (ilGroupParticipants::_isParticipant($a_ref_id, $a_user_id)) {
                    $ilAccess->addInfoItem(ilAccessInfo::IL_STATUS_INFO, $lng->txt("info_is_member"));
                } else {
                    $ilAccess->addInfoItem(ilAccessInfo::IL_STATUS_INFO, $lng->txt("info_is_not_member"));
                }
                break;
                
            case "join":
            
                if (!self::_registrationEnabled($a_obj_id)) {
                    return false;
                }

                include_once './Modules/Group/classes/class.ilGroupWaitingList.php';
                // fau: changeSub - use $a_user_id parameter to query waiting list
                if (ilGroupWaitingList::_isOnList($a_user_id, $a_obj_id)) {
                    // fau.
                    return false;
                }

                include_once './Modules/Group/classes/class.ilGroupParticipants.php';
                if (ilGroupParticipants::_isParticipant($a_ref_id, $a_user_id)) {
                    return false;
                }
                break;
                
            case 'leave':

                // Regular member
                if ($a_permission == 'leave') {
                    include_once './Modules/Group/classes/class.ilObjGroup.php';
                    $limit = null;
                    if (!ilObjGroup::mayLeave($a_obj_id, $a_user_id, $limit)) {
                        $ilAccess->addInfoItem(
                            ilAccessInfo::IL_STATUS_INFO,
                            sprintf($lng->txt("grp_cancellation_end_rbac_info"), ilDatePresentation::formatDate($limit))
                        );
                        return false;
                    }
                    
                    include_once './Modules/Group/classes/class.ilGroupParticipants.php';
                    if (!ilGroupParticipants::_isParticipant($a_ref_id, $a_user_id)) {
                        return false;
                    }
                }
                // Waiting list
                if ($a_permission == 'join') {
                    include_once './Modules/Group/classes/class.ilGroupWaitingList.php';
                    // fau: changeSub - use $a_user_id parameter to query waiting list
                    if (!ilGroupWaitingList::_isOnList($a_user_id, $a_obj_id)) {
                        // fau.
                        return false;
                    }
                }
                break;

            // fau: joinAsGuest - check rights for guest accounts to request a join
            case 'joinAsGuest':

                include_once './Modules/Group/classes/class.ilGroupParticipants.php';

                // don't show join_as_guest command if user is already assigned
                if (ilGroupParticipants::_isParticipant($a_ref_id, $a_user_id)) {
                    return false;
                }

                // don't show join_as_guest command if user can join
                if ($rbacsystem->checkAccessOfUser($a_user_id, 'join', $a_ref_id)) {
                    return false;
                }
                break;
            // fau.


        }

        switch ($a_permission) {
            // fau: preventCampoDelete - check if group can be deleted
            // unlike courses, groups for campo should also not be moved from their parent course
            // so the command does not need to be checked to distinct cut from delete
            case 'delete':
                if (!$DIC->fau()->user()->canDeleteObjectsForCourses((int) $a_user_id)
                    && $DIC->fau()->study()->isObjectForCampo((int) $a_obj_id)
                ) {
                    $ilAccess->addInfoItem(IL_NO_OBJECT_ACCESS, $lng->txt("fau_delete_group_blocked"));
                    return false;
                }
                break;
            // fau.
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
    public static function _getCommands()
    {
        $commands = array();
        $commands[] = array("permission" => "grp_linked", "cmd" => "", "lang_var" => "show", "default" => true);

        $commands[] = array("permission" => "join", "cmd" => "join", "lang_var" => "join");

        // on waiting list
        // fau: fairSub - general command for editing requests
        $commands[] = array('permission' => "join", "cmd" => "leave", "lang_var" => "mem_edit_request");
        // fau.

        // fau: joinAsGuest - add command for guest accounts to request a join
        include_once('Services/User/classes/class.ilUserUtil.php');
        if (ilUserUtil::_isGuestHearer()) {
            $commands[] = array('permission' => "visible", "cmd" => "joinAsGuest", "lang_var" => "join_as_guest");
        }
        // fau.
        
        // regualar users
        $commands[] = array('permission' => "leave", "cmd" => "leave", "lang_var" => "grp_btn_unsubscribe");
        
        include_once('Services/WebDAV/classes/class.ilDAVActivationChecker.php');
        if (ilDAVActivationChecker::_isActive()) {
            include_once './Services/WebDAV/classes/class.ilWebDAVUtil.php';
            if (ilWebDAVUtil::getInstance()->isLocalPasswordInstructionRequired()) {
                $commands[] = array('permission' => 'read', 'cmd' => 'showPasswordInstruction', 'lang_var' => 'mount_webfolder', 'enable_anonymous' => 'false');
            } else {
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
    public static function _checkGoto($a_target)
    {
        global $DIC;

        $ilAccess = $DIC['ilAccess'];
        $ilUser = $DIC['ilUser'];

        $t_arr = explode("_", $a_target);
        // registration codes
        if (substr($t_arr[2], 0, 5) == 'rcode' and $ilUser->getId() != ANONYMOUS_USER_ID) {
            self::$using_code = true;
            return true;
        }

        if ($t_arr[0] != "grp" || ((int) $t_arr[1]) <= 0) {
            return false;
        }

        // fau: joinLink - don't allow 'join' command for anonymous users
        if ($t_arr[2] == 'join' && $ilUser->getId() == ANONYMOUS_USER_ID) {
            global $lng;

            // ugly fix: $tpl used by ilUtil may not be initialized
            //ilUtil::sendInfo($lng->txt('join_grp_needs_login'), true);

            //ilTemplate::MESSAGE_TYPE_INFO
            $_SESSION['info'] = $lng->txt('join_grp_needs_login', true);
            ilUtil::redirect(ilUtil::_getLoginLink($a_target), true);
        }
        // fau.

        if ($ilAccess->checkAccess("read", "", $t_arr[1]) ||
            $ilAccess->checkAccess("visible", "", $t_arr[1])) {
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
        global $DIC;

        $ilDB = $DIC['ilDB'];

        $query = "SELECT * FROM grp_settings " .
            "WHERE obj_id = " . $ilDB->quote($a_obj_id, 'integer') . " ";

        $res = $ilDB->query($query);
        
        $enabled = $unlimited = false;
        while ($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT)) {
            $enabled = $row->registration_enabled;
            $unlimited = $row->registration_unlimited;
            $start = $row->registration_start;
            $end = $row->registration_end;
        }

        if (!$enabled) {
            return false;
        }
        if ($unlimited) {
            return true;
        }
        
        if (!$unlimited) {
            $start = new ilDateTime($start, IL_CAL_DATETIME);
            $end = new ilDateTime($end, IL_CAL_DATETIME);
            $time = new ilDateTime(time(), IL_CAL_UNIX);
            
            return ilDateTime::_after($time, $start) and ilDateTime::_before($time, $end);
        }
        return false;
    }
    

    /**
     * Preload data
     *
     * @param array $a_obj_ids array of object ids
     */
    public static function _preloadData($a_obj_ids, $a_ref_ids)
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];
        $ilUser = $DIC['ilUser'];
        
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
    // fau: showMemLimit - add ref_id as parameter for checking write access
    public static function lookupRegistrationInfo($a_obj_id, $a_ref_id = 0)
    // fau.
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];
        $ilUser = $DIC['ilUser'];
        $lng = $DIC['lng'];

        // fau: fairSub - query for fair period
        // fau: paraSub - query for waiting list
        $query = 'SELECT registration_type, registration_enabled, registration_unlimited,  registration_start, ' .
            'registration_end, registration_mem_limit, registration_max_members, sub_fair, waiting_list FROM grp_settings ' .
            'WHERE obj_id = ' . $ilDB->quote($a_obj_id);
        $res = $ilDB->query($query);
        
        $info = array();
        while ($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT)) {
            $info['reg_info_start'] = new ilDateTime($row->registration_start, IL_CAL_DATETIME);
            $info['reg_info_end'] = new ilDateTime($row->registration_end, IL_CAL_DATETIME);
            $info['reg_info_type'] = $row->registration_type;
            $info['reg_info_max_members'] = $row->registration_max_members;
            $info['reg_info_mem_limit'] = $row->registration_mem_limit;
            $info['reg_info_unlimited'] = $row->registration_unlimited;
            
            $info['reg_info_max_members'] = 0;
            if ($info['reg_info_mem_limit']) {
                $info['reg_info_max_members'] = $row->registration_max_members;
            }
            
            $info['reg_info_enabled'] = $row->registration_enabled;
            $info['reg_info_sub_fair'] = $row->sub_fair;
            $info['reg_info_waiting_list'] = $row->waiting_list;
        }
        // fau.

        $registration_possible = $info['reg_info_enabled'];

        // fau: paraSub - show info about registration via course
        // fau: fairSub - add info about fair period
        if ($DIC->fau()->study()->isObjectForCampo($a_obj_id)) {
            $info['reg_info_list_prop']['property'] = $lng->txt('grp_list_reg');
            $info['reg_info_list_prop']['value'] = $lng->txt('fau_sub_group_by_course_list_info');
        }
        // Limited registration (added $registration_possible, see bug 0010157)
        elseif (!$info['reg_info_unlimited'] && $registration_possible) {
            $fair_suffix = '';
            if ($info['reg_info_mem_limit'] > 0 && $info['reg_info_max_members'] > 0) {
                if ($info['reg_info_sub_fair'] < 0) {
                    $fair_suffix = " - <b>" . $lng->txt('sub_fair_inactive_short') . "</b>";
                }
            }

            $dt = new ilDateTime(time(), IL_CAL_UNIX);
            if (ilDateTime::_before($dt, $info['reg_info_start'])) {
                $info['reg_info_list_prop']['property'] = $lng->txt('grp_list_reg_start');
                $info['reg_info_list_prop']['value'] = ilDatePresentation::formatDate($info['reg_info_start']) . $fair_suffix;
            } elseif (ilDateTime::_before($dt, $info['reg_info_end'])) {
                $info['reg_info_list_prop']['property'] = $lng->txt('grp_list_reg_end');
                $info['reg_info_list_prop']['value'] = ilDatePresentation::formatDate($info['reg_info_end']) . $fair_suffix;
            } else {
                $registration_possible = false;
                $info['reg_info_list_prop']['property'] = $lng->txt('grp_list_reg_period');
                $info['reg_info_list_prop']['value'] = $lng->txt('grp_list_reg_noreg');
            }

        } else {
            // added !$registration_possible, see bug 0010157
            if (!$registration_possible) {
                $registration_possible = false;
                $info['reg_info_list_prop']['property'] = $lng->txt('grp_list_reg');
                $info['reg_info_list_prop']['value'] = $lng->txt('grp_list_reg_noreg');
            }
        }
        // fau.

        // fau: showMemLimit - get info about membership limitations and subscription status
        // fau: fairSub - always query for the free places - info is also used on subscription page
        global $ilAccess;
        include_once './Modules/Group/classes/class.ilGroupParticipant.php';
        include_once './Modules/Group/classes/class.ilGroupWaitingList.php';

        $partObj = ilGroupParticipant::_getInstanceByObjId($a_obj_id, $ilUser->getId());

        if ($info['reg_info_mem_limit'] && $registration_possible) {
            $show_mem_limit = true;
            $show_hidden_notice = false;
        } elseif ($info['reg_info_mem_limit'] && $ilAccess->checkAccess('write', '', $a_ref_id, 'crs', $a_obj_id)) {
            $show_mem_limit = true;
            $show_hidden_notice = true;
        } else {
            $show_mem_limit = false;
            $show_hidden_notice = false;
        }

        $max_members = $info['reg_info_max_members'];
        $members = $partObj->getNumberOfMembers();
        // fau: paraSub - add members as info
        $info['reg_info_members'] = $members;
        // fau.
        $free_places = max($max_members - $members, 0);
        $info['reg_info_free_places'] = $free_places;
        $waiting = ilGroupWaitingList::lookupListSize($a_obj_id);
        $info['reg_info_sbscribers'] = $waiting;

        if ($show_mem_limit) {
            $limits = array();
            if ($show_hidden_notice) {
                $limits[] = $lng->txt("mem_max_users_hidden");
            }
            $limits[] = $lng->txt("mem_max_users") . $max_members;
            $limits[] = $lng->txt("mem_free_places") . ': ' . $free_places;
            if ($waiting > 0) {
                $limits[] = $lng->txt("subscribers_or_waiting_list") . ': ' . (string) ($waiting);
            }
            $info['reg_info_list_prop_limit']['property'] = '';
            $info['reg_info_list_prop_limit']['value'] = implode(' &nbsp; ', $limits);
        }

        // registration status
        $info['reg_info_waiting_status'] = ilGroupWaitingList::_getStatus($ilUser->getId(), $a_obj_id);
        switch ($info['reg_info_waiting_status'] ) {
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
        if ($status) {
            $info['reg_info_list_prop_status']['property'] = $lng->txt('member_status');
            $info['reg_info_list_prop_status']['value'] = $status;
        }
        // fau.

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
        global $DIC;

        $ilDB = $DIC['ilDB'];
        $lng = $DIC['lng'];

        $start = $end = null;
        $query = 'SELECT period_start, period_end, period_time_indication FROM grp_settings ' .
            'WHERE obj_id = ' . $ilDB->quote($a_obj_id);

        $res = $ilDB->query($query);
        while ($row = $res->fetchRow(\ilDBConstants::FETCHMODE_OBJECT)) {
            if (!$row->period_time_indication) {
                $start = ($row->period_start
                    ? new \ilDate($row->period_start, IL_CAL_DATETIME)
                    : null);
                $end = ($row->period_end
                    ? new \ilDate($row->period_end, IL_CAL_DATETIME)
                    : null);
            } else {
                $start = ($row->period_start
                    ? new \ilDateTime($row->period_start, IL_CAL_DATETIME, \ilTimeZone::UTC)
                    : null);
                $end = ($row->period_end
                    ? new \ilDateTime($row->period_end, IL_CAL_DATETIME, \ilTimeZone::UTC)
                    : null);
            }
        }
        if ($start && $end) {
            $lng->loadLanguageModule('grp');

            return
                [
                    'property' => $lng->txt('grp_period'),
                    'value' => ilDatePresentation::formatPeriod($start, $end)
                ];
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
