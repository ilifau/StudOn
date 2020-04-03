<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

//TODO: function getRoleId($groupRole) returns the object-id of grouprole

require_once "./Services/Container/classes/class.ilContainer.php";
include_once('./Services/Calendar/classes/class.ilDateTime.php');
include_once './Services/Membership/interfaces/interface.ilMembershipRegistrationCodes.php';

define('GRP_REGISTRATION_DEACTIVATED', -1);
define('GRP_REGISTRATION_DIRECT', 0);
define('GRP_REGISTRATION_REQUEST', 1);
define('GRP_REGISTRATION_PASSWORD', 2);

// fau: objectSub - add constant for subscription via object
define('GRP_REGISTRATION_OBJECT', 11);
// fau.

define('GRP_REGISTRATION_LIMITED', 1);
define('GRP_REGISTRATION_UNLIMITED', 2);

define('GRP_TYPE_UNKNOWN', 0);
define('GRP_TYPE_CLOSED', 1);
define('GRP_TYPE_OPEN', 2);
define('GRP_TYPE_PUBLIC', 3);

/**
* Class ilObjGroup
*
* @author Stefan Meyer <smeyer.ilias@gmx.de>
* @author Sascha Hofmann <saschahofmann@gmx.de>
*
* @version $Id$
*
* @extends ilObject
*/
class ilObjGroup extends ilContainer implements ilMembershipRegistrationCodes
{
    const CAL_REG_START = 1;
    const CAL_REG_END = 2;
    const CAL_START = 3;
    const CAL_END = 4;
    
    const GRP_MEMBER = 1;
    const GRP_ADMIN = 2;
    
    const ERR_MISSING_TITLE = 'msg_no_title';
    const ERR_MISSING_GROUP_TYPE = 'grp_missing_grp_type';
    const ERR_MISSING_PASSWORD = 'grp_missing_password';
    const ERR_WRONG_MAX_MEMBERS = 'grp_wrong_max_members';
    const ERR_WRONG_REG_TIME_LIMIT = 'grp_wrong_reg_time_limit';
    const ERR_MISSING_MIN_MAX_MEMBERS = 'grp_wrong_min_max_members';
    const ERR_WRONG_MIN_MAX_MEMBERS = 'grp_max_and_min_members_invalid';
    const ERR_WRONG_REGISTRATION_LIMITED = 'grp_err_registration_limited';
    
    const MAIL_ALLOWED_ALL = 1;
    const MAIL_ALLOWED_TUTORS = 2;

    public $SHOW_MEMBERS_ENABLED = 1;
    public $SHOW_MEMBERS_DISABLED = 0;
    
    protected $information;
    protected $group_type = null;
    protected $reg_type = GRP_REGISTRATION_DIRECT;
    // fau: objectSub - class variable
    protected $reg_ref_id = null;
    // fau.
    protected $reg_enabled = true;
    protected $reg_unlimited = true;
    protected $reg_start = null;
    protected $reg_end = null;
    protected $reg_password = '';
    protected $reg_membership_limitation = false;
    protected $reg_min_members = 0;
    protected $reg_max_members = 0;
    // fau: fairSub - change default setting for waiting list
    protected $waiting_list = true;
    // fau.
    protected $auto_fill_from_waiting; // [bool]
    protected $leave_end; // [ilDate]
    protected $show_members = 1;
    
    
    protected $start = null;
    protected $end = null;

    // fim: [meminf] class variable for showimg mem limit
    protected $show_mem_limit = true;
    // fim.

    // Map
    private $latitude = '';
    private $longitude = '';
    private $locationzoom = 0;
    private $enablemap = 0;
    
    private $reg_access_code = '';
    private $reg_access_code_enabled = false;

    private $view_mode = ilContainer::VIEW_DEFAULT;

    // fau: mailToMembers - change default setting for mail to members
    private $mail_members = self::MAIL_ALLOWED_TUTORS;
    // fau.

    // fau: fairSub - new class variables
    protected $subscription_fair;
    protected $subscription_auto_fill = true;
    protected $subscription_last_fill;
    // fau.
    
    public $members_obj;


    /**
    * Group file object for handling of export files
    */
    public $file_obj = null;

    public $m_grpStatus;

    public $m_roleMemberId;

    public $m_roleAdminId;

    /**
    * Constructor
    * @access	public
    * @param	integer	reference_id or object_id
    * @param	boolean	treat the id as reference_id (true) or object_id (false)
    */
    public function __construct($a_id = 0, $a_call_by_reference = true)
    {
        global $DIC;

        $tree = $DIC['tree'];

        $this->tree = &$tree;

        $this->type = "grp";
        parent::__construct($a_id, $a_call_by_reference);
        $this->setRegisterMode(true); // ???
    }
    
    /**
     * Lookup group type
     * @param object $a_id
     * @return
     */
    public static function lookupGroupTye($a_id)
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];
        
        $query = "SELECT grp_type FROM grp_settings " .
            "WHERE obj_id = " . $ilDB->quote($a_id, 'integer');
        $res = $ilDB->query($query);
        while ($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT)) {
            return $row->grp_type;
        }
        return GRP_TYPE_UNKNOWN;
    }
    
    // Setter/Getter
    /**
     * set information
     *
     * @access public
     * @param string information
     * @return
     */
    public function setInformation($a_information)
    {
        $this->information = $a_information;
    }
    
    /**
     * get Information
     *
     * @access public
     * @param
     * @return string information
     */
    public function getInformation()
    {
        return $this->information;
    }
    
    /**
     * set group type
     *
     * @access public
     * @param int type
     */
    public function setGroupType($a_type)
    {
        $this->group_type = $a_type;
    }
    
    /**
     * get group type
     *
     * @access public
     * @return int group type
     */
    public function getGroupType()
    {
        return $this->group_type;
    }
    
    /**
     * set registration type
     *
     * @access public
     * @param int registration type
     * @return
     */
    public function setRegistrationType($a_type)
    {
        $this->reg_type = $a_type;
    }
    
    /**
     * get registration type
     *
     * @access public
     * @return int registration type
     */
    public function getRegistrationType()
    {
        return $this->reg_type;
    }

    // fau: objectSub - getter / setter
    public function getRegistrationRefId()
    {
        return $this->reg_ref_id;
    }
    public function setRegistrationRefId($a_ref_id)
    {
        $this->reg_ref_id = $a_ref_id;
    }
    // fau.

    /**
     * is registration enabled
     *
     * @access public
     * @return bool
     */
    public function isRegistrationEnabled()
    {
        return $this->getRegistrationType() != GRP_REGISTRATION_DEACTIVATED;
    }
    
    /**
     * enable unlimited registration
     *
     * @access public
     * @param bool
     * @return
     */
    public function enableUnlimitedRegistration($a_status)
    {
        $this->reg_unlimited = $a_status;
    }
    
    /**
     * is registration unlimited
     *
     * @access public
     * @return bool
     */
    public function isRegistrationUnlimited()
    {
        return $this->reg_unlimited;
    }
    
    /**
     * set registration start
     *
     * @access public
     * @param object ilDateTime
     * @return
     */
    public function setRegistrationStart($a_start)
    {
        $this->reg_start = $a_start;
    }
    
    /**
     * get registration start
     *
     * @access public
     * @return int registration start
     */
    public function getRegistrationStart()
    {
        return $this->reg_start;
    }
    

    /**
     * set registration end
     *
     * @access public
     * @param int unix time
     * @return
     */
    public function setRegistrationEnd($a_end)
    {
        $this->reg_end = $a_end;
    }
    
    /**
     * get registration end
     *
     * @access public
     * @return int registration end
     */
    public function getRegistrationEnd()
    {
        return $this->reg_end;
    }

    // fau: fairSub - getter / setter
    public function getSubscriptionFair()
    {
        return (int) $this->subscription_fair;
    }
    public function setSubscriptionFair($a_value)
    {
        $this->subscription_fair = $a_value;
    }
    public function getSubscriptionAutoFill()
    {
        return (bool) $this->subscription_auto_fill;
    }
    public function setSubscriptionAutoFill($a_value)
    {
        $this->subscription_auto_fill = (bool) $a_value;
    }
    public function getSubscriptionLastFill()
    {
        return $this->subscription_last_fill;
    }
    public function setSubscriptionLastFill($a_value)
    {
        $this->subscription_last_fill = $a_value;
    }
    public function saveSubscriptionLastFill($a_value = null)
    {
        global $ilDB;
        $ilDB->update(
            'grp_settings',
            array('sub_last_fill' => array('integer', $a_value)),
            array('obj_id' => array('integer', $this->getId()))
        );
        $this->subscription_last_fill = $a_value;
    }

    public function getSubscriptionMinFairSeconds()
    {
        global $ilSetting;
        return $ilSetting->get('SubscriptionMinFairSeconds', 3600);
    }

    public function getSubscriptionFairDisplay($a_relative)
    {
        require_once('Services/Calendar/classes/class.ilDatePresentation.php');
        $relative = ilDatePresentation::useRelativeDates();
        ilDatePresentation::setUseRelativeDates($a_relative);
        $fairdate = ilDatePresentation::formatDate(new ilDateTime($this->getSubscriptionFair(), IL_CAL_UNIX));
        ilDatePresentation::setUseRelativeDates($relative);
        return $fairdate;
    }
    // fau.

    // fau: fairSub - check if current time is in fair time span
    public function inSubscriptionFairTime($a_time = null)
    {
        if (!isset($a_time)) {
            $a_time = time();
        }

        if (!$this->isMembershipLimited()) {
            return false;
        } elseif (empty($this->getMaxMembers())) {
            return false;
        } elseif ($a_time > $this->getSubscriptionFair()) {
            return false;
        } elseif (empty($this->getRegistrationStart()) || $this->getRegistrationStart()->isNull()) {
            return false;
        } elseif ($a_time < $this->getRegistrationStart()->get(IL_CAL_UNIX)) {
            return false;
        } else {
            return true;
        }
    }
    // fau.

    /**
     * set password
     *
     * @access public
     * @param string password
     */
    public function setPassword($a_pass)
    {
        $this->reg_password = $a_pass;
    }
    
    /**
     * get password
     *
     * @access public
     * @return string password
     */
    public function getPassword()
    {
        return $this->reg_password;
    }
    
    /**
     * enable max member limitation
     *
     * @access public
     * @param bool status
     * @return
     */
    public function enableMembershipLimitation($a_status)
    {
        $this->reg_membership_limitation = $a_status;
    }
    
    /**
     * is max member limited
     *
     * @access public
     * @return
     */
    public function isMembershipLimited()
    {
        return (bool) $this->reg_membership_limitation;
    }

    /**
     * set min members
     *
     * @access public
     * @param int min members
     */
    public function setMinMembers($a_max)
    {
        $this->reg_min_members = $a_max;
    }
    
    /**
     * get min members
     *
     * @access public
     * @return
     */
    public function getMinMembers()
    {
        return $this->reg_min_members;
    }

    /**
     * set max members
     *
     * @access public
     * @param int max members
     */
    public function setMaxMembers($a_max)
    {
        $this->reg_max_members = $a_max;
    }
    
    /**
     * get max members
     *
     * @access public
     * @return
     */
    public function getMaxMembers()
    {
        return $this->reg_max_members;
    }
    
    /**
     * enable waiting list
     *
     * @access public
     * @param bool
     * @return
     */
    public function enableWaitingList($a_status)
    {
        $this->waiting_list = $a_status;
    }
    
    /**
     * is waiting list enabled
     *
     * @access public
     * @param
     * @return bool
     */
    public function isWaitingListEnabled()
    {
        return $this->waiting_list;
    }
    
    public function setWaitingListAutoFill($a_value)
    {
        $this->auto_fill_from_waiting = (bool) $a_value;
    }
    
    public function hasWaitingListAutoFill()
    {
        return (bool) $this->auto_fill_from_waiting;
    }

    // fim: [meminf] new functions get/setShowMemLimit()
    public function getShowMemLimit()
    {
        return $this->show_mem_limit;
    }
    public function setShowMemLimit($a_value)
    {
        $this->show_mem_limit = $a_value;
    }
    // fim.

    /**
    * Set Latitude.
    *
    * @param	string	$a_latitude	Latitude
    */
    public function setLatitude($a_latitude)
    {
        $this->latitude = $a_latitude;
    }

    /**
    * Get Latitude.
    *
    * @return	string	Latitude
    */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
    * Set Longitude.
    *
    * @param	string	$a_longitude	Longitude
    */
    public function setLongitude($a_longitude)
    {
        $this->longitude = $a_longitude;
    }

    /**
    * Get Longitude.
    *
    * @return	string	Longitude
    */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
    * Set LocationZoom.
    *
    * @param	int	$a_locationzoom	LocationZoom
    */
    public function setLocationZoom($a_locationzoom)
    {
        $this->locationzoom = $a_locationzoom;
    }

    /**
    * Get LocationZoom.
    *
    * @return	int	LocationZoom
    */
    public function getLocationZoom()
    {
        return $this->locationzoom;
    }

    /**
    * Set Enable Group Map.
    *
    * @param	boolean	$a_enablemap	Enable Group Map
    */
    public function setEnableGroupMap($a_enablemap)
    {
        $this->enablemap = $a_enablemap;
    }

    /**
     * Type independent wrapper
     * @return type
     */
    public function getEnableMap()
    {
        return $this->getEnableGroupMap();
    }
    
    /**
    * Get Enable Group Map.
    *
    * @return	boolean	Enable Group Map
    */
    public function getEnableGroupMap()
    {
        return (bool) $this->enablemap;
    }
    
    /**
     * get access code
     * @return
     */
    public function getRegistrationAccessCode()
    {
        return $this->reg_access_code;
    }
    
    /**
     * Set refistration access code
     * @param string $a_code
     * @return
     */
    public function setRegistrationAccessCode($a_code)
    {
        $this->reg_access_code = $a_code;
    }
    
    /**
     * Check if access code is enabled
     * @return
     */
    public function isRegistrationAccessCodeEnabled()
    {
        return (bool) $this->reg_access_code_enabled;
    }
    
    /**
     * En/disable registration access code
     * @param object $a_status
     * @return
     */
    public function enableRegistrationAccessCode($a_status)
    {
        $this->reg_access_code_enabled = $a_status;
    }
    
    /**
     * Set mail to members type
     * @see ilCourseConstants
     * @param type $a_type
     */
    public function setMailToMembersType($a_type)
    {
        $this->mail_members = $a_type;
    }
    
    /**
     * Get mail to members type
     * @return int
     */
    public function getMailToMembersType()
    {
        return $this->mail_members;
    }
    
    public function setCancellationEnd(ilDate $a_value = null)
    {
        $this->leave_end = $a_value;
    }
    
    public function getCancellationEnd()
    {
        return $this->leave_end;
    }

    public function setShowMembers($a_status)
    {
        $this->show_members = $a_status;
    }
    public function getShowMembers()
    {
        return $this->show_members;
    }
    
    /**
     * Get group start
     * @return ilDate
     */
    public function getStart()
    {
        return $this->start;
    }
    
    /**
     * Set start
     * @param ilDate $start
     */
    public function setStart(ilDate $start = null)
    {
        $this->start = $start;
    }
    
    /**
     * Get end
     * @return ilDate
     */
    public function getEnd()
    {
        return $this->end;
    }
    
    /**
     * Set end
     * @param ilDate $end
     */
    public function setEnd(ilDate $end = null)
    {
        $this->end = $end;
    }
    
    /**
     * validate group settings
     *
     * @access public
     * @return bool
     */
    public function validate()
    {
        global $DIC;

        $ilErr = $DIC['ilErr'];
        
        if (!$this->getTitle()) {
            $this->title = '';
            $ilErr->appendMessage($this->lng->txt(self::ERR_MISSING_TITLE));
        }
        if ($this->getRegistrationType() == GRP_REGISTRATION_PASSWORD and !strlen($this->getPassword())) {
            $ilErr->appendMessage($this->lng->txt(self::ERR_MISSING_PASSWORD));
        }
        /*
        if(ilDateTime::_before($this->getRegistrationEnd(),$this->getRegistrationStart()))
        {
            $ilErr->appendMessage($this->lng->txt(self::ERR_WRONG_REG_TIME_LIMIT));
        }
        */


        // fau: fairSub - validate subscription times
        if ($this->isMembershipLimited() && $this->getMaxMembers() > 0
            && !empty($this->getRegistrationStart()) && !$this->getRegistrationStart()->isNull()
            && !empty($this->getRegistrationEnd()) && !$this->getRegistrationEnd()->isNull()
            && $this->getRegistrationEnd()->get(IL_CAL_UNIX) < $this->getRegistrationStart()->get(IL_CAL_UNIX) + $this->getSubscriptionMinFairSeconds()) {
            $ilErr->appendMessage(sprintf($this->lng->txt("sub_fair_subscription_min_minutes"), ceil($this->getSubscriptionMinFairSeconds() / 60)));
        }
        // fau.

        // fau: regPeriod - check deny time for registration
        if (!$this->isRegistrationUnlimited()) {
            $deny_regstart_from = ilCust::get('ilias_deny_regstart_from');
            $deny_regstart_to = ilCust::get('ilias_deny_regstart_to');
            if ($deny_regstart_from and $deny_regstart_to) {
                $deny_regstart_from = new ilDateTime($deny_regstart_from, IL_CAL_DATETIME);
                $deny_regstart_to = new ilDateTime($deny_regstart_to, IL_CAL_DATETIME);

                if (!empty($this->getRegistrationStart()) && !$this->getRegistrationStart()->isNull()
                    && !empty($this->getRegistrationEnd()) && !$this->getRegistrationEnd()->isNull()
                    && ilDateTime::_before($deny_regstart_from, $this->getRegistrationStart())
                    && ilDateTime::_after($deny_regstart_to, $this->getRegistrationStart())) {
                    $ilErr->appendMessage(sprintf(
                        $this->lng->txt('deny_regstart_message'),
                        ilDatePresentation::formatDate($deny_regstart_from),
                        ilDatePresentation::formatDate($deny_regstart_to)
                    ));
                }
            }
        }
        // fau.
        if ($this->isMembershipLimited()) {
            if ($this->getMinMembers() <= 0 && $this->getMaxMembers() <= 0) {
                $ilErr->appendMessage($this->lng->txt(self::ERR_MISSING_MIN_MAX_MEMBERS));
            }
            if ($this->getMaxMembers() <= 0 && $this->isWaitingListEnabled()) {
                $ilErr->appendMessage($this->lng->txt(self::ERR_WRONG_MAX_MEMBERS));
            }
            if ($this->getMaxMembers() > 0 && $this->getMinMembers() > $this->getMaxMembers()) {
                $ilErr->appendMessage($this->lng->txt(self::ERR_WRONG_MIN_MAX_MEMBERS));
            }
        }
        // fau: fairSub - fixed check for wrong registration period
        $has_regstart = $this->getRegistrationStart() instanceof ilDateTime && !$this->getRegistrationStart()->isNull();
        $has_regend = $this->getRegistrationEnd() instanceof ilDateTime && !$this->getRegistrationEnd()->isNull();

        if (
            ($has_regstart && !$has_regend) ||
            (!$has_regstart && $has_regend) ||
            ($has_regstart && $has_regend && ilDateTime::_before($this->getRegistrationEnd(), $this->getRegistrationStart()))
        ) {
            // fau.
            $ilErr->appendMessage($this->lng->txt((self::ERR_WRONG_REGISTRATION_LIMITED)));
        }
        
        return strlen($ilErr->getMessage()) == 0;
    }
    
    
    

    /**
    * Create group
    */
    public function create()
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];
        $ilAppEventHandler = $DIC['ilAppEventHandler'];

        if (!parent::create()) {
            return false;
        }

        if (!$a_upload) {
            $this->createMetaData();
        }


        // fau: objectSub - add sub_ref_id
        // fau: fairSub - add sub_fair, sub_auto_fill, sub_last_fill
        // fim: [meminf] add show_mem_limit to create
        $query = "INSERT INTO grp_settings (obj_id,information,grp_type,registration_type,sub_ref_id,registration_enabled," .
            "registration_unlimited,registration_start,registration_end,sub_fair,sub_auto_fill,sub_last_fill,registration_password,registration_mem_limit," .
            "registration_max_members,waiting_list,show_mem_limit,latitude,longitude,location_zoom,enablemap,reg_ac_enabled,reg_ac,view_mode,mail_members_type," .
            "leave_end,registration_min_members,auto_wait, grp_start, grp_end) " .
            "VALUES(" .
            $ilDB->quote($this->getId(), 'integer') . ", " .
            $ilDB->quote($this->getInformation(), 'text') . ", " .
            $ilDB->quote((int) $this->getGroupType(), 'integer') . ", " .
            $ilDB->quote($this->getRegistrationType(), 'integer') . ", " .
            $ilDB->quote($this->getRegistrationRefId(), 'integer') . ", " .
            $ilDB->quote(($this->isRegistrationEnabled() ? 1 : 0), 'integer') . ", " .
            $ilDB->quote(($this->isRegistrationUnlimited() ? 1 : 0), 'integer') . ", " .
            $ilDB->quote(($this->getRegistrationStart() && !$this->getRegistrationStart()->isNull()) ? $this->getRegistrationStart()->get(IL_CAL_DATETIME, '') : null, 'timestamp') . ", " .
            $ilDB->quote(($this->getRegistrationEnd() && !$this->getRegistrationEnd()->isNull()) ? $this->getRegistrationEnd()->get(IL_CAL_DATETIME, '') : null, 'timestamp') . ", " .
            $ilDB->quote($this->getSubscriptionFair(), 'integer') . ", " .
            $ilDB->quote((int) $this->getSubscriptionLastFill(), 'integer') . ", " .
            $ilDB->quote($this->getSubscriptionLastFill(), 'integer') . ", " .
            $ilDB->quote($this->getPassword(), 'text') . ", " .
            $ilDB->quote((int) $this->isMembershipLimited(), 'integer') . ", " .
            $ilDB->quote($this->getMaxMembers(), 'integer') . ", " .
            $ilDB->quote($this->isWaitingListEnabled() ? 1 : 0, 'integer') . ", " .
            $ilDB->quote($this->getShowMemLimit() ? 1 : 0, 'integer') . ", " .
            $ilDB->quote($this->getLatitude(), 'text') . ", " .
            $ilDB->quote($this->getLongitude(), 'text') . ", " .
            $ilDB->quote($this->getLocationZoom(), 'integer') . ", " .
            $ilDB->quote((int) $this->getEnableGroupMap(), 'integer') . ", " .
            $ilDB->quote($this->isRegistrationAccessCodeEnabled(), 'integer') . ', ' .
            $ilDB->quote($this->getRegistrationAccessCode(), 'text') . ', ' .
            $ilDB->quote($this->view_mode, 'integer') . ', ' .
            $ilDB->quote($this->getMailToMembersType(), 'integer') . ', ' .
            $ilDB->quote(($this->getCancellationEnd() && !$this->getCancellationEnd()->isNull()) ? $this->getCancellationEnd()->get(IL_CAL_UNIX) : null, 'integer') . ', ' .
            $ilDB->quote($this->getMinMembers(), 'integer') . ', ' .
            $ilDB->quote($this->hasWaitingListAutoFill(), 'integer') . ', ' .
            $ilDB->quote($this->getStart() instanceof ilDate ? $this->getStart()->get(IL_CAL_UNIX) : null, 'integer') . ', ' .
            $ilDB->quote($this->getEnd() instanceof ilDate ? $this->getEnd()->get(IL_CAL_UNIX) : null, 'integer') . ' ' .
            ")";
        // fim.
        // fau.
        $res = $ilDB->manipulate($query);

        $ilAppEventHandler->raise(
            'Modules/Group',
            'create',
            array('object' => $this,
                'obj_id' => $this->getId(),
                'appointments' => $this->prepareAppointments('create'))
        );
        
        return $this->getId();
    }

    /**
    * Update group
    */
    public function update()
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];
        $ilAppEventHandler = $DIC['ilAppEventHandler'];

        if (!parent::update()) {
            return false;
        }

        // fim: [meminf] update show_mem_limit
        $query = "UPDATE grp_settings " .
            "SET information = " . $ilDB->quote($this->getInformation(), 'text') . ", " .
            "grp_type = " . $ilDB->quote((int) $this->getGroupType(), 'integer') . ", " .
            "registration_type = " . $ilDB->quote($this->getRegistrationType(), 'integer') . ", " .
// fau: objectSub - save sub_ref_id
            "sub_ref_id = " . $ilDB->quote($this->getRegistrationRefId(), 'integer') . ", " .
// fau.
// fau: fairSub - save sub_fair, sub_auto_fill and sub_last_fill
            "sub_fair = " . $ilDB->quote($this->getSubscriptionFair(), 'integer') . ", " .
            "sub_auto_fill = " . $ilDB->quote((int) $this->getSubscriptionAutoFill(), 'integer') . ", " .
            "sub_last_fill = " . $ilDB->quote($this->getSubscriptionLastFill(), 'integer') . ", " .
// fau.
            "registration_enabled = " . $ilDB->quote($this->isRegistrationEnabled() ? 1 : 0, 'integer') . ", " .
            "registration_unlimited = " . $ilDB->quote($this->isRegistrationUnlimited() ? 1 : 0, 'integer') . ", " .
            "registration_start = " . $ilDB->quote(($this->getRegistrationStart() && !$this->getRegistrationStart()->isNull()) ? $this->getRegistrationStart()->get(IL_CAL_DATETIME, '') : null, 'timestamp') . ", " .
            "registration_end = " . $ilDB->quote(($this->getRegistrationEnd() && !$this->getRegistrationEnd()->isNull()) ? $this->getRegistrationEnd()->get(IL_CAL_DATETIME, '') : null, 'timestamp') . ", " .
            "registration_password = " . $ilDB->quote($this->getPassword(), 'text') . ", " .
//			"registration_membership_limited = ".$ilDB->quote((int) $this->isMembershipLimited() ,'integer').", ".
            "registration_mem_limit = " . $ilDB->quote((int) $this->isMembershipLimited(), 'integer') . ", " .
            "registration_max_members = " . $ilDB->quote($this->getMaxMembers(), 'integer') . ", " .
            "waiting_list = " . $ilDB->quote($this->isWaitingListEnabled() ? 1 : 0, 'integer') . ", " .
            "show_mem_limit = " . $ilDB->quote($this->getShowMemLimit() ? 1 : 0, 'integer') . ", " .
            "latitude = " . $ilDB->quote($this->getLatitude(), 'text') . ", " .
            "longitude = " . $ilDB->quote($this->getLongitude(), 'text') . ", " .
            "location_zoom = " . $ilDB->quote($this->getLocationZoom(), 'integer') . ", " .
            "enablemap = " . $ilDB->quote((int) $this->getEnableGroupMap(), 'integer') . ", " .
            'reg_ac_enabled = ' . $ilDB->quote($this->isRegistrationAccessCodeEnabled(), 'integer') . ', ' .
            'reg_ac = ' . $ilDB->quote($this->getRegistrationAccessCode(), 'text') . ', ' .
            'view_mode = ' . $ilDB->quote($this->view_mode, 'integer') . ', ' .
            'mail_members_type = ' . $ilDB->quote($this->getMailToMembersType(), 'integer') . ', ' .
            'leave_end = ' . $ilDB->quote(($this->getCancellationEnd() && !$this->getCancellationEnd()->isNull()) ? $this->getCancellationEnd()->get(IL_CAL_UNIX) : null, 'integer') . ', ' .
            "registration_min_members = " . $ilDB->quote($this->getMinMembers(), 'integer') . ", " .
            "auto_wait = " . $ilDB->quote($this->hasWaitingListAutoFill(), 'integer') . ", " .
            "show_members = " . $ilDB->quote((int) $this->getShowMembers(), 'integer') . ", " .
            'grp_start = ' . $ilDB->quote($this->getStart() instanceof ilDate ? $this->getStart()->get(IL_CAL_UNIX) : null) . ', ' .
            'grp_end = ' . $ilDB->quote($this->getEnd() instanceof ilDate ? $this->getEnd()->get(IL_CAL_UNIX) : null) . ' ' .
            "WHERE obj_id = " . $ilDB->quote($this->getId(), 'integer');
        // fim.
        $res = $ilDB->manipulate($query);

        // fau: fixGroupDescription - update metadata
        $this->updateMetaData();
        // fau.

        $ilAppEventHandler->raise(
            'Modules/Group',
            'update',
            array('object' => $this,
                'obj_id' => $this->getId(),
                'appointments' => $this->prepareAppointments('update'))
        );
                
        
        return true;
    }
    
    /**
    * delete group and all related data
    *
    * @access	public
    * @return	boolean	true if all object data were removed; false if only a references were removed
    */
    public function delete()
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];
        $ilAppEventHandler = $DIC['ilAppEventHandler'];

        // always call parent delete function first!!
        if (!parent::delete()) {
            return false;
        }

        $query = "DELETE FROM grp_settings " .
            "WHERE obj_id = " . $ilDB->quote($this->getId(), 'integer');
        $res = $ilDB->manipulate($query);
        
        include_once('./Modules/Group/classes/class.ilGroupParticipants.php');
        ilGroupParticipants::_deleteAllEntries($this->getId());

        $ilAppEventHandler->raise(
            'Modules/Group',
            'delete',
            array('object' => $this,
                'obj_id' => $this->getId(),
                'appointments' => $this->prepareAppointments('delete'))
        );
        
        
        return true;
    }
    

    /**
    * Read group
    */
    public function read()
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];

        parent::read();

        $query = "SELECT * FROM grp_settings " .
            "WHERE obj_id = " . $ilDB->quote($this->getId(), 'integer');
        
        $res = $ilDB->query($query);
        while ($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT)) {
            $this->setInformation($row->information);
            $this->setGroupType($row->grp_type);
            $this->setRegistrationType($row->registration_type);
            // fau: objectSub - read sub_ref_id
            $this->setRegistrationRefId($row->sub_ref_id);
            // fau.
            // fau: fairSub - read sub_fair and sub_last_fill
            $this->setSubscriptionFair($row->sub_fair);
            $this->setSubscriptionAutoFill($row->sub_auto_fill);
            $this->setSubscriptionLastFill($row->sub_last_fill);
            // fau.
            $this->enableUnlimitedRegistration($row->registration_unlimited);
            $this->setRegistrationStart(new ilDateTime($row->registration_start, IL_CAL_DATETIME));
            $this->setRegistrationEnd(new ilDateTime($row->registration_end, IL_CAL_DATETIME));
            $this->setPassword($row->registration_password);
            $this->enableMembershipLimitation((bool) $row->registration_mem_limit);
            $this->setMaxMembers($row->registration_max_members);
            $this->enableWaitingList($row->waiting_list);
            // fim: [meminf] read show_mem_limit
            $this->setShowMemLimit($row->show_mem_limit);
            // fim.
            $this->setLatitude($row->latitude);
            $this->setLongitude($row->longitude);
            $this->setLocationZoom($row->location_zoom);
            $this->setEnableGroupMap($row->enablemap);
            $this->enableRegistrationAccessCode($row->reg_ac_enabled);
            $this->setRegistrationAccessCode($row->reg_ac);
            $this->setViewMode($row->view_mode);
            $this->setMailToMembersType($row->mail_members_type);
            $this->setCancellationEnd($row->leave_end ? new ilDate($row->leave_end, IL_CAL_UNIX) : null);
            $this->setMinMembers($row->registration_min_members);
            $this->setWaitingListAutoFill($row->auto_wait);
            $this->setShowMembers($row->show_members);
            $this->setStart($row->grp_start ? new ilDate($row->grp_start, IL_CAL_UNIX) : null);
            $this->setEnd($row->grp_end ? new ilDate($row->grp_end, IL_CAL_UNIX) : null);
        }
        $this->initParticipants();
        
        // Inherit order type from parent course (if exists)
        include_once('./Services/Container/classes/class.ilContainerSortingSettings.php');
        $this->setOrderType(ilContainerSortingSettings::_lookupSortMode($this->getId()));
    }

    /**
     * Clone group (no member data)
     *
     * @access public
     * @param int target ref_id
     * @param int copy id
     *
     */
    public function cloneObject($a_target_id, $a_copy_id = 0, $a_omit_tree = false)
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];
        $ilUser = $DIC['ilUser'];
        $ilSetting = $DIC['ilSetting'];

        $new_obj = parent::cloneObject($a_target_id, $a_copy_id, $a_omit_tree);

        // current template
        $current_template = ilDidacticTemplateObjSettings::lookupTemplateId($this->getRefId());
        $new_obj->applyDidacticTemplate($current_template);
        $this->cloneAutoGeneratedRoles($new_obj);
        $this->cloneMetaData($new_obj);

        $new_obj->setRegistrationType($this->getRegistrationType());
        $new_obj->setInformation($this->getInformation());
        $new_obj->setRegistrationStart($this->getRegistrationStart());
        $new_obj->setRegistrationEnd($this->getRegistrationEnd());
        $new_obj->enableUnlimitedRegistration($this->isRegistrationUnlimited());
        $new_obj->setPassword($this->getPassword());
        $new_obj->enableMembershipLimitation($this->isMembershipLimited());
        $new_obj->setMaxMembers($this->getMaxMembers());
        $new_obj->enableWaitingList($this->isWaitingListEnabled());
        $new_obj->setShowMembers($this->getShowMembers());

        // fim: [meminf] clone showMemLimit
        $new_obj->setShowMemLimit($this->getShowMemLimit());
        // fim.
        // fau: objectSub - clone sub_ref_id
        $new_obj->setRegistrationRefId($this->getRegistrationRefId());
        // fau.
        // fau: fairSub - clone sub_fair and reset sub_last_fill
        $new_obj->setSubscriptionFair($this->getSubscriptionFair());
        $new_obj->setSubscriptionAutoFill($this->getSubscriptionAutoFill());
        $new_obj->setSubscriptionLastFill(null);
        // fau.
        // map
        $new_obj->setLatitude($this->getLatitude());
        $new_obj->setLongitude($this->getLongitude());
        $new_obj->setLocationZoom($this->getLocationZoom());
        $new_obj->setEnableGroupMap($this->getEnableGroupMap());
        $new_obj->enableRegistrationAccessCode($this->isRegistrationAccessCodeEnabled());
        include_once './Services/Membership/classes/class.ilMembershipRegistrationCodeUtils.php';
        $new_obj->setRegistrationAccessCode(ilMembershipRegistrationCodeUtils::generateCode());

        $new_obj->setViewMode($this->view_mode);
        $new_obj->setMailToMembersType($this->getMailToMembersType());
        
        $new_obj->setCancellationEnd($this->getCancellationEnd());
        $new_obj->setMinMembers($this->getMinMembers());
        $new_obj->setWaitingListAutoFill($this->hasWaitingListAutoFill());
        
        $new_obj->setStart($this->getStart());
        $new_obj->setEnd($this->getEnd());
        
        $new_obj->update();
        
        // #13008 - Group Defined Fields
        include_once('Modules/Course/classes/Export/class.ilCourseDefinedFieldDefinition.php');
        ilCourseDefinedFieldDefinition::_clone($this->getId(), $new_obj->getId());
        
        // Assign user as admin
        include_once('./Modules/Group/classes/class.ilGroupParticipants.php');
        $part = ilGroupParticipants::_getInstanceByObjId($new_obj->getId());
        $part->add($ilUser->getId(), IL_GRP_ADMIN);
        $part->updateNotification($ilUser->getId(), $ilSetting->get('mail_grp_admin_notification', true));
        $part->updateContact($ilUser->getId(), true);

        // Copy learning progress settings
        include_once('Services/Tracking/classes/class.ilLPObjSettings.php');
        $obj_settings = new ilLPObjSettings($this->getId());
        $obj_settings->cloneSettings($new_obj->getId());
        unset($obj_settings);
        
        return $new_obj;
    }

    /**
     * Clone object dependencies (crs items, preconditions)
     *
     * @access public
     * @param int target ref id of new course
     * @param int copy id
     *
     */
    public function cloneDependencies($a_target_id, $a_copy_id)
    {
        global $DIC;

        $tree = $DIC['tree'];

        parent::cloneDependencies($a_target_id, $a_copy_id);

        include_once('Services/Object/classes/class.ilObjectActivation.php');
        ilObjectActivation::cloneDependencies($this->getRefId(), $a_target_id, $a_copy_id);

        // clone membership limitation
        foreach (\ilObjCourseGrouping::_getGroupings($this->getId()) as $grouping_id) {
            \ilLoggerFactory::getLogger('grp')->info('Handling grouping id: ' . $grouping_id);
            $grouping = new \ilObjCourseGrouping($grouping_id);
            $grouping->cloneGrouping($a_target_id, $a_copy_id);
        }

        return true;
    }

    /**
     * Clone group admin and member role permissions
     *
     * @access public
     * @param object new group object
     *
     */
    public function cloneAutoGeneratedRoles($new_obj)
    {
        global $DIC;

        $rbacadmin = $DIC['rbacadmin'];
        $rbacreview = $DIC['rbacreview'];

        $admin = $this->getDefaultAdminRole();
        $new_admin = $new_obj->getDefaultAdminRole();
        if (!$admin || !$new_admin || !$this->getRefId() || !$new_obj->getRefId()) {
            ilLoggerFactory::getLogger('grp')->warning('Error cloning auto generated rol: il_grp_admin');
        }
        $rbacadmin->copyRolePermissions($admin, $this->getRefId(), $new_obj->getRefId(), $new_admin, true);
        ilLoggerFactory::getLogger('grp')->info('Finished copying of role il_grp_admin.');

        $member = $this->getDefaultMemberRole();
        $new_member = $new_obj->getDefaultMemberRole();
        if (!$member || !$new_member) {
            ilLoggerFactory::getLogger('grp')->warning('Error cloning auto generated rol: il_grp_member');
        }
        $rbacadmin->copyRolePermissions($member, $this->getRefId(), $new_obj->getRefId(), $new_member, true);
        ilLoggerFactory::getLogger('grp')->info('Finished copying of role il_grp_member.');
    }


    /**
    * join Group, assigns user to role
    * @access	private
    * @param	integer	member status = obj_id of local_group_role
    */
    public function join($a_user_id, $a_mem_role = "")
    {
        global $DIC;

        $rbacadmin = $DIC['rbacadmin'];

        if (is_array($a_mem_role)) {
            foreach ($a_mem_role as $role) {
                $rbacadmin->assignUser($role, $a_user_id, false);
            }
        } else {
            $rbacadmin->assignUser($a_mem_role, $a_user_id, false);
        }

        return true;
    }

    /**
    * returns object id of created default member role
    * @access	public
    */
    public function getDefaultMemberRole()
    {
        $local_group_Roles = $this->getLocalGroupRoles();

        return $local_group_Roles["il_grp_member_" . $this->getRefId()];
    }

    /**
    * returns object id of created default adminstrator role
    * @access	public
    */
    public function getDefaultAdminRole()
    {
        $local_group_Roles = $this->getLocalGroupRoles();

        return $local_group_Roles["il_grp_admin_" . $this->getRefId()];
    }

    /**
    * add Member to Group
    * @access	public
    * @param	integer	user_id
    * @param	integer	member role_id of local group_role
    */
    public function addMember($a_user_id, $a_mem_role)
    {
        global $DIC;

        $rbacadmin = $DIC['rbacadmin'];

        if (isset($a_user_id) && isset($a_mem_role)) {
            $this->join($a_user_id, $a_mem_role);
            return true;
        } else {
            $this->ilias->raiseError(get_class($this) . "::addMember(): Missing parameters !", $this->ilias->error_obj->WARNING);
            return false;
        }
    }


    /**
    * is called when a member decides to leave group
    * @access	public
    * @param	integer	user-Id
    * @param	integer group-Id
    */
    public function leaveGroup()
    {
        global $DIC;

        $rbacadmin = $DIC['rbacadmin'];
        $rbacreview = $DIC['rbacreview'];

        $member_ids = $this->getGroupMemberIds();

        if (count($member_ids) <= 1 || !in_array($this->ilias->account->getId(), $member_ids)) {
            return 2;
        } else {
            if (!$this->isAdmin($this->ilias->account->getId())) {
                $this->leave($this->ilias->account->getId());
                $member = new ilObjUser($this->ilias->account->getId());
                $member->dropDesktopItem($this->getRefId(), "grp");

                return 0;
            } elseif (count($this->getGroupAdminIds()) == 1) {
                return 1;
            }
        }
    }

    /**
    * deassign member from group role
    * @access	private
    */
    public function leave($a_user_id)
    {
        global $DIC;

        $rbacadmin = $DIC['rbacadmin'];

        $arr_groupRoles = $this->getMemberRoles($a_user_id);

        if (is_array($arr_groupRoles)) {
            foreach ($arr_groupRoles as $groupRole) {
                $rbacadmin->deassignUser($groupRole, $a_user_id);
            }
        } else {
            $rbacadmin->deassignUser($arr_groupRoles, $a_user_id);
        }

        return true;
    }

    /**
    * get all group Member ids regardless of role
    * @access	public
    * @return	return array of users (obj_ids) that are assigned to
    * the groupspecific roles (grp_member,grp_admin)
    */
    public function getGroupMemberIds()
    {
        global $DIC;

        $rbacadmin = $DIC['rbacadmin'];
        $rbacreview = $DIC['rbacreview'];

        $usr_arr = array();

        $rol = $this->getLocalGroupRoles();

        foreach ($rol as $value) {
            foreach ($rbacreview->assignedUsers($value) as $member_id) {
                array_push($usr_arr, $member_id);
            }
        }

        $mem_arr = array_unique($usr_arr);

        return $mem_arr ? $mem_arr : array();
    }

    /**
    * get all group Members regardless of group role.
    * fetch all users data in one shot to improve performance
    * @access	public
    * @param	array	of user ids
    * @return	return array of userdata
    */
    public function getGroupMemberData($a_mem_ids, $active = 1)
    {
        global $DIC;

        $rbacadmin = $DIC['rbacadmin'];
        $rbacreview = $DIC['rbacreview'];
        $ilBench = $DIC['ilBench'];
        $ilDB = $DIC['ilDB'];

        $usr_arr = array();

        $q = "SELECT login,firstname,lastname,title,usr_id,last_login " .
             "FROM usr_data " .
             "WHERE usr_id IN (" . implode(',', ilUtil::quoteArray($a_mem_ids)) . ") ";

        if (is_numeric($active) && $active > -1) {
            $q .= "AND active = '$active'";
        }
            
        $q .= 'ORDER BY lastname,firstname';

        $r = $ilDB->query($q);
        
        while ($row = $r->fetchRow(ilDBConstants::FETCHMODE_OBJECT)) {
            $mem_arr[] = array("id" => $row->usr_id,
                                "login" => $row->login,
                                "firstname" => $row->firstname,
                                "lastname" => $row->lastname,
                                "last_login" => $row->last_login
                                );
        }

        return $mem_arr ? $mem_arr : array();
    }

    public function getCountMembers()
    {
        return count($this->getGroupMemberIds());
    }

    /**
    * get Group Admin Id
    * @access	public
    * @param	integer	group id
    * @param	returns userids that are assigned to a group administrator! role
    */
    public function getGroupAdminIds($a_grpId = "")
    {
        global $DIC;

        $rbacreview = $DIC['rbacreview'];

        if (!empty($a_grpId)) {
            $grp_id = $a_grpId;
        } else {
            $grp_id = $this->getRefId();
        }

        $usr_arr = array();
        $roles = $this->getDefaultGroupRoles($this->getRefId());

        foreach ($rbacreview->assignedUsers($this->getDefaultAdminRole()) as $member_id) {
            array_push($usr_arr, $member_id);
        }

        return $usr_arr;
    }

    /**
    * get default group roles, returns the defaultlike create roles il_grp_member, il_grp_admin
    * @access	public
    * @param 	returns the obj_ids of group specific roles(il_grp_member,il_grp_admin)
    */
    public function getDefaultGroupRoles($a_grp_id = "")
    {
        global $DIC;

        $rbacadmin = $DIC['rbacadmin'];
        $rbacreview = $DIC['rbacreview'];

        if (strlen($a_grp_id) > 0) {
            $grp_id = $a_grp_id;
        } else {
            $grp_id = $this->getRefId();
        }

        $role_arr = $rbacreview->getRolesOfRoleFolder($grp_id);

        foreach ($role_arr as $role_id) {
            $role_Obj = &$this->ilias->obj_factory->getInstanceByObjId($role_id);

            $grp_Member = "il_grp_member_" . $grp_id;
            $grp_Admin = "il_grp_admin_" . $grp_id;

            if (strcmp($role_Obj->getTitle(), $grp_Member) == 0) {
                $arr_grpDefaultRoles["grp_member_role"] = $role_Obj->getId();
            }

            if (strcmp($role_Obj->getTitle(), $grp_Admin) == 0) {
                $arr_grpDefaultRoles["grp_admin_role"] = $role_Obj->getId();
            }
        }

        return $arr_grpDefaultRoles;
    }

    /**
    * get ALL local roles of group, also those created and defined afterwards
    * only fetch data once from database. info is stored in object variable
    * @access	public
    * @return	return array [title|id] of roles...
    */
    public function getLocalGroupRoles($a_translate = false)
    {
        global $DIC;

        $rbacadmin = $DIC['rbacadmin'];
        $rbacreview = $DIC['rbacreview'];

        if (empty($this->local_roles)) {
            $this->local_roles = array();
            $role_arr = $rbacreview->getRolesOfRoleFolder($this->getRefId());

            foreach ($role_arr as $role_id) {
                if ($rbacreview->isAssignable($role_id, $this->getRefId()) == true) {
                    $role_Obj = &$this->ilias->obj_factory->getInstanceByObjId($role_id);

                    if ($a_translate) {
                        $role_name = ilObjRole::_getTranslation($role_Obj->getTitle());
                    } else {
                        $role_name = $role_Obj->getTitle();
                    }

                    $this->local_roles[$role_name] = $role_Obj->getId();
                }
            }
        }

        return $this->local_roles;
    }

    /**
    * get group status closed template
    * @access	public
    * @param	return obj_id of roletemplate containing permissionsettings for a closed group
    */
    public function getGrpStatusClosedTemplateId()
    {
        $q = "SELECT obj_id FROM object_data WHERE type='rolt' AND title='il_grp_status_closed'";
        $res = $this->ilias->db->query($q);
        $row = $res->fetchRow(ilDBConstants::FETCHMODE_ASSOC);

        return $row["obj_id"];
    }

    /**
    * get group status open template
    * @access	public
    * @param	return obj_id of roletemplate containing permissionsettings for an open group
    */
    public function getGrpStatusOpenTemplateId()
    {
        $q = "SELECT obj_id FROM object_data WHERE type='rolt' AND title='il_grp_status_open'";
        $res = $this->ilias->db->query($q);
        $row = $res->fetchRow(ilDBConstants::FETCHMODE_ASSOC);

        return $row["obj_id"];
    }
    
    /**
     *
     * @global $ilDB $ilDB
     * @param int $a_obj_id
     * @return int
     */
    public static function lookupGroupStatusTemplateId($a_obj_id)
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];
        
        $type = self::lookupGroupTye($a_obj_id);
        if ($type == GRP_TYPE_CLOSED) {
            $query = 'SELECT obj_id FROM object_data WHERE type = ' . $ilDB->quote('rolt', 'text') . ' AND title = ' . $ilDB->quote('il_grp_status_closed', 'text');
        } else {
            $query = 'SELECT obj_id FROM object_data WHERE type = ' . $ilDB->quote('rolt', 'text') . ' AND title = ' . $ilDB->quote('il_grp_status_open', 'text');
        }
        $res = $ilDB->query($query);
        $row = $res->fetchRow(ilDBConstants::FETCHMODE_ASSOC);
        
        return isset($row['obj_id']) ? $row['obj_id'] : 0;
    }


    
    /**
     * Change group type
     *
     * Revokes permissions of all parent non-protected roles
     * and initiates these roles with the according il_grp_(open|closed) template.
     *
     * @access public
     * @return
     */
    public function updateGroupType($a_group_type = GRP_TYPE_OPEN)
    {
        global $DIC;

        $logger = $DIC->logger()->grp();
        
        if ($a_group_type == GRP_TYPE_OPEN) {
            $this->applyDidacticTemplate(0);
            return;
        }
        
        $templates = ilDidacticTemplateSettings::getInstanceByObjectType($this->getType())->getTemplates();
        foreach ($templates as $template) {
            // the closed template
            if ($template->isAutoGenerated()) {
                $logger->info('Appying default closed template');
                $this->applyDidacticTemplate($template->getId());
                return;
            }
        }
        $logger->warning('No closed didactic template available.');
    }

    /**
    * set group status
    *
    * Grants permissions on the group object for all parent roles.
    * Each permission is granted by computing the intersection of the role
    * template il_grp_status_open/_closed and the permission template of
    * the parent role.
    *
    * Creates linked roles in the local role folder object for all
    * parent roles and initializes their permission templates.
    * Each permission template is initialized by computing the intersection
    * of the role template il_grp_status_open/_closed and the permission
    * template of the parent role.
    *
    * @access	public
    * @param	integer group status GRP_TYPE_PUBLIC or GRP_TYPE_CLOSED
     *
     * @deprecated with 5.3 use ilObjGroup->updateGroupType
    */
    public function initGroupStatus($a_grpStatus = GRP_TYPE_PUBLIC)
    {
        global $DIC;

        $rbacadmin = $DIC['rbacadmin'];
        $rbacreview = $DIC['rbacreview'];
        $rbacsystem = $DIC['rbacsystem'];

        //define all relevant roles that rights are needed to be changed
        $arr_parentRoles = $rbacreview->getParentRoleIds($this->getRefId());

        $real_local_roles = $rbacreview->getRolesOfRoleFolder($this->getRefId(), false);
        $arr_relevantParentRoleIds = array_diff(array_keys($arr_parentRoles), $real_local_roles);

        //group status open (aka public) or group status closed
        if ($a_grpStatus == GRP_TYPE_PUBLIC || $a_grpStatus == GRP_TYPE_CLOSED) {
            if ($a_grpStatus == GRP_TYPE_PUBLIC) {
                $template_id = $this->getGrpStatusOpenTemplateId();
            } else {
                $template_id = $this->getGrpStatusClosedTemplateId();
            }
            //get defined operations from template
            $template_ops = $rbacreview->getOperationsOfRole($template_id, 'grp', ROLE_FOLDER_ID);

            foreach ($arr_relevantParentRoleIds as $parentRole) {
                if ($rbacreview->isProtected($arr_parentRoles[$parentRole]['parent'], $parentRole)) {
                    continue;
                }

                $granted_permissions = array();

                // Delete the linked role for the parent role
                // (just in case if it already exists).
                
                // Added additional check, since this operation is very dangerous.
                // If there is no role folder ALL parent roles are deleted.

                // @todo refactor rolf
                $rbacadmin->deleteLocalRole($parentRole, $this->getRefId());

                // Grant permissions on the group object for
                // the parent role. In the foreach loop we
                // compute the intersection of the role
                // template il_grp_status_open/_closed and the
                // permission template of the parent role.
                $current_ops = $rbacreview->getRoleOperationsOnObject($parentRole, $this->getRefId());
                $rbacadmin->revokePermission($this->getRefId(), $parentRole);
                foreach ($template_ops as $template_op) {
                    if (in_array($template_op, $current_ops)) {
                        array_push($granted_permissions, $template_op);
                    }
                }
                if (!empty($granted_permissions)) {
                    $rbacadmin->grantPermission($parentRole, $granted_permissions, $this->getRefId());
                }

                // Create a linked role for the parent role and
                // initialize it with the intersection of
                // il_grp_status_open/_closed and the permission
                // template of the parent role
                
                $rbacadmin->copyRolePermissionIntersection(
                    $template_id,
                    ROLE_FOLDER_ID,
                    $parentRole,
                    $arr_parentRoles[$parentRole]['parent'],
                    $this->getRefId(),
                    $parentRole
                );
                $rbacadmin->assignRoleToFolder($parentRole, $this->getRefId(), "false");
            }//END foreach
        }
    }

    /**
     * Set group status
     *
     * @access public
     * @param int group status[0=public|2=closed]
     *
     */
    public function setGroupStatus($a_status)
    {
        $this->group_status = $a_status;
    }

    /**
     * get group status
     *
     * @access public
     * @param int group status
     *
     */
    public function getGroupStatus()
    {
        return $this->group_status;
    }

    /**
    * get group status, redundant method because
    * @access	public
    * @param	return group status[0=public|2=closed]
    */
    public function readGroupStatus()
    {
        global $DIC;

        $rbacsystem = $DIC['rbacsystem'];
        $rbacreview = $DIC['rbacreview'];

        $local_roles = $rbacreview->getRolesOfRoleFolder($this->getRefId());

        //get all relevant roles
        $arr_globalRoles = array_diff($local_roles, $this->getDefaultGroupRoles());

        //if one global role has no permission to join the group is officially closed !
        foreach ($arr_globalRoles as $globalRole) {
            if ($rbacsystem->checkPermission($this->getRefId(), $globalRole, "join")) {
                return $this->group_status = GRP_TYPE_PUBLIC;
            }
        }

        return $this->group_status = GRP_TYPE_CLOSED;
    }

    /**
    * get group member status
    * @access	public
    * @param	integer	user_id
    * @return	returns array of obj_ids of assigned local roles
    */
    public function getMemberRoles($a_user_id)
    {
        global $DIC;

        $rbacadmin = $DIC['rbacadmin'];
        $rbacreview = $DIC['rbacreview'];
        $ilBench = $DIC['ilBench'];

        $ilBench->start("Group", "getMemberRoles");

        $arr_assignedRoles = array();

        $arr_assignedRoles = array_intersect($rbacreview->assignedRoles($a_user_id), $this->getLocalGroupRoles());

        $ilBench->stop("Group", "getMemberRoles");

        return $arr_assignedRoles;
    }

    /**
    * is Admin
    * @access	public
    * @param	integer	user_id
    * @param	boolean, true if user is group administrator
    */
    public function isAdmin($a_userId)
    {
        global $DIC;

        $rbacreview = $DIC['rbacreview'];

        $grp_Roles = $this->getDefaultGroupRoles();

        if (in_array($a_userId, $rbacreview->assignedUsers($grp_Roles["grp_admin_role"]))) {
            return true;
        } else {
            return false;
        }
    }
    /**
    * init default roles settings
    * @access	public
    * @return	array	object IDs of created local roles.
    */
    public function initDefaultRoles()
    {
        include_once './Services/AccessControl/classes/class.ilObjRole.php';
        $role = ilObjRole::createDefaultRole(
            'il_grp_admin_' . $this->getRefId(),
            "Groupadmin group obj_no." . $this->getId(),
            'il_grp_admin',
            $this->getRefId()
        );
        $this->m_roleAdminId = $role->getId();
        
        $role = ilObjRole::createDefaultRole(
            'il_grp_member_' . $this->getRefId(),
            "Groupmember of group obj_no." . $this->getId(),
            'il_grp_member',
            $this->getRefId()
        );
        $this->m_roleMemberId = $role->getId();
        
        return array();
    }
    
    /**
     * This method is called before "initDefaultRoles".
     * Therefore no local group roles are created.
     *
     * Grants permissions on the group object for all parent roles.
     * Each permission is granted by computing the intersection of the
     * template il_grp_status and the permission template of the parent role.
     * @param int parent ref id
     */
    public function setParentRolePermissions($a_parent_ref)
    {
        $rbacadmin = $GLOBALS['DIC']->rbac()->admin();
        $rbacreview = $GLOBALS['DIC']->rbac()->review();
        
        $parent_roles = $rbacreview->getParentRoleIds($a_parent_ref);
        foreach ((array) $parent_roles as $parent_role) {
            if ($parent_role['parent'] == $this->getRefId()) {
                continue;
            }
            if ($rbacreview->isProtected($parent_role['parent'], $parent_role['rol_id'])) {
                $operations = $rbacreview->getOperationsOfRole(
                    $parent_role['obj_id'],
                    $this->getType(),
                    $parent_role['parent']
                );
                $rbacadmin->grantPermission(
                    $parent_role['obj_id'],
                    $operations,
                    $this->getRefId()
                );
                continue;
            }

            $rbacadmin->initIntersectionPermissions(
                $this->getRefId(),
                $parent_role['obj_id'],
                $parent_role['parent'],
                $this->getGrpStatusOpenTemplateId(),
                ROLE_FOLDER_ID
            );
        }
    }
    
    
    /**
     * Apply template
     * @param int $a_tpl_id
     */
    public function applyDidacticTemplate($a_tpl_id)
    {
        parent::applyDidacticTemplate($a_tpl_id);
        
        if (!$a_tpl_id) {
            // init default type
            $this->setParentRolePermissions($this->getRefId());
        }
    }
    

    public static function _lookupIdByTitle($a_title)
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];

        $query = "SELECT * FROM object_data WHERE title = " .
            $ilDB->quote($a_title, 'text') . " AND type = 'grp'";
        $res = $ilDB->query($query);
        while ($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT)) {
            return $row->obj_id;
        }
        return 0;
    }


    public function _isMember($a_user_id, $a_ref_id, $a_field = '')
    {
        global $DIC;

        $rbacreview = $DIC['rbacreview'];
        $ilObjDataCache = $DIC['ilObjDataCache'];
        $ilDB = $DIC['ilDB'];

        $local_roles = $rbacreview->getRolesOfRoleFolder($a_ref_id, false);
        $user_roles = $rbacreview->assignedRoles($a_user_id);

        // Used for membership limitations -> check membership by given field
        if ($a_field) {
            include_once './Services/User/classes/class.ilObjUser.php';

            $tmp_user = &ilObjectFactory::getInstanceByObjId($a_user_id);
            switch ($a_field) {
                case 'login':
                    $and = "AND login = '" . $tmp_user->getLogin() . "' ";
                    break;
                case 'email':
                    $and = "AND email = '" . $tmp_user->getEmail() . "' ";
                    break;
                case 'matriculation':
                    $and = "AND matriculation = '" . $tmp_user->getMatriculation() . "' ";
                    break;

                default:
                    $and = "AND usr_id = '" . $a_user_id . "'";
                    break;
            }
            if (!$members = ilObjGroup::_getMembers($ilObjDataCache->lookupObjId($a_ref_id))) {
                return false;
            }
            $query = "SELECT * FROM usr_data as ud " .
                "WHERE usr_id IN (" . implode(",", ilUtil::quoteArray($members)) . ") " .
                $and;
            $res = $ilDB->query($query);

            return $res->numRows() ? true : false;
        }

        if (!array_intersect($local_roles, $user_roles)) {
            return false;
        }

        return true;
    }

    public function _getMembers($a_obj_id)
    {
        global $DIC;

        $rbacreview = $DIC['rbacreview'];

        // get reference
        $ref_ids = ilObject::_getAllReferences($a_obj_id);
        $ref_id = current($ref_ids);

        $local_roles = $rbacreview->getRolesOfRoleFolder($ref_id, false);

        $users = array();
        foreach ($local_roles as $role_id) {
            $users = array_merge($users, $rbacreview->assignedUsers($role_id));
        }

        return array_unique($users);
    }
    
    /**
     * Get effective container view mode
     * @return int
     */
    public function getViewMode()
    {
        $tree = $this->tree;

        // default: by type
        $view = self::lookupViewMode($this->getId());

        if ($view != ilContainer::VIEW_INHERIT) {
            return $view;
        }

        $container_ref_id = $tree->checkForParentType($this->ref_id, 'crs');
        if ($container_ref_id) {
            $view_mode = ilObjCourseAccess::_lookupViewMode(ilObject::_lookupObjId($container_ref_id));
            // these three are available...
            if (
                $view_mode == ilContainer::VIEW_SESSIONS ||
                $view_mode == ilContainer::VIEW_BY_TYPE ||
                $view_mode == ilContainer::VIEW_SIMPLE) {
                return $view_mode;
            }
        }
        return ilContainer::VIEW_DEFAULT;
    }


    /**
     * Set group view mode
     * @param int $a_view_mode
     */
    public function setViewMode($a_view_mode)
    {
        $this->view_mode = $a_view_mode;
    }

    /**
     * lookup view mode
     * @global $ilDB
     */
    public static function lookupViewMode($a_obj_id)
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];

        $query = 'SELECT view_mode FROM grp_settings ' .
            'WHERE obj_id = ' . $ilDB->quote($a_obj_id, 'integer');
        $res = $ilDB->query($query);

        $view_mode = null;
        while ($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT)) {
            $view_mode = $row->view_mode;
        }
        return $view_mode;
    }
    
    /**
     * translate view mode
     * @param int $a_obj_id
     * @param int $a_view_mode
     * @param int $a_ref_id
     * @return int
     */
    public static function translateViewMode($a_obj_id, $a_view_mode, $a_ref_id = null)
    {
        global $DIC;

        $tree = $DIC['tree'];
        
        if (!$a_view_mode) {
            $a_view_mode = ilContainer::VIEW_DEFAULT;
        }
        
        // view mode is inherit => check for parent course
        if ($a_view_mode == ilContainer::VIEW_INHERIT) {
            if (!$a_ref_id) {
                $ref = ilObject::_getAllReferences($a_obj_id);
                $a_ref_id = end($ref);
            }

            $crs_ref = $tree->checkForParentType($a_ref_id, 'crs');
            if (!$crs_ref) {
                return ilContainer::VIEW_DEFAULT;
            }

            include_once './Modules/Course/classes/class.ilObjCourse.php';
            $view_mode = ilObjCourse::_lookupViewMode(ilObject::_lookupObjId($crs_ref));
            
            // validate course view mode
            if (!in_array($view_mode, array(ilContainer::VIEW_SESSIONS,
                ilContainer::VIEW_BY_TYPE, ilContainer::VIEW_SIMPLE))) {
                return ilContainer::VIEW_DEFAULT;
            }
            
            return $view_mode;
        }
                        
        return $a_view_mode;
    }

    /**
    * Add additional information to sub item, e.g. used in
    * courses for timings information etc.
    */
    public function addAdditionalSubItemInformation(&$a_item_data)
    {
        include_once './Services/Object/classes/class.ilObjectActivation.php';
        ilObjectActivation::addAdditionalSubItemInformation($a_item_data);
    }

    public function getMessage()
    {
        return $this->message;
    }
    public function setMessage($a_message)
    {
        $this->message = $a_message;
    }
    public function appendMessage($a_message)
    {
        if ($this->getMessage()) {
            $this->message .= "<br /> ";
        }
        $this->message .= $a_message;
    }
    
    /**
     * Prepare calendar appointments
     *
     * @access protected
     * @param string mode UPDATE|CREATE|DELETE
     * @return
     */
    protected function prepareAppointments($a_mode = 'create')
    {
        include_once('./Services/Calendar/classes/class.ilCalendarAppointmentTemplate.php');
        
        switch ($a_mode) {
            case 'create':
            case 'update':
                
                $apps = array();
                if ($this->getStart() && $this->getEnd()) {
                    $app = new ilCalendarAppointmentTemplate(self::CAL_START);
                    $app->setTitle($this->getTitle());
                    $app->setSubtitle('grp_cal_start');
                    $app->setTranslationType(IL_CAL_TRANSLATION_SYSTEM);
                    $app->setDescription($this->getLongDescription());
                    $app->setStart($this->getStart());
                    $app->setFullday(true);
                    $apps[] = $app;

                    $app = new ilCalendarAppointmentTemplate(self::CAL_END);
                    $app->setTitle($this->getTitle());
                    $app->setSubtitle('grp_cal_end');
                    $app->setTranslationType(IL_CAL_TRANSLATION_SYSTEM);
                    $app->setDescription($this->getLongDescription());
                    $app->setStart($this->getEnd());
                    $app->setFullday(true);
                    $apps[] = $app;
                }
                if ($this->isRegistrationUnlimited()) {
                    return $apps;
                }
                
                $app = new ilCalendarAppointmentTemplate(self::CAL_REG_START);
                $app->setTitle($this->getTitle());
                $app->setSubtitle('grp_cal_reg_start');
                $app->setTranslationType(IL_CAL_TRANSLATION_SYSTEM);
                $app->setDescription($this->getLongDescription());
                $app->setStart($this->getRegistrationStart());
                $apps[] = $app;

                $app = new ilCalendarAppointmentTemplate(self::CAL_REG_END);
                $app->setTitle($this->getTitle());
                $app->setSubtitle('grp_cal_reg_end');
                $app->setTranslationType(IL_CAL_TRANSLATION_SYSTEM);
                $app->setDescription($this->getLongDescription());
                $app->setStart($this->getRegistrationEnd());
                $apps[] = $app;
                
                
                return $apps;
                
            case 'delete':
                // Nothing to do: The category and all assigned appointments will be deleted.
                return array();
        }
    }
    
    /**
     * init participants object
     *
     *
     * @access protected
     * @return
     */
    protected function initParticipants()
    {
        include_once('./Modules/Group/classes/class.ilGroupParticipants.php');
        $this->members_obj = ilGroupParticipants::_getInstanceByObjId($this->getId());
    }
    
    /**
     * Get members objects
     *
     * @return ilGroupParticipants
     */
    public function getMembersObject()
    {
        // #17886
        if (!$this->members_obj instanceof ilGroupParticipants) {
            $this->initParticipants();
        }
        return $this->members_obj;
    }
    
    /**
     * @see interface.ilMembershipRegistrationCodes
     * @return array obj ids
     */
    public static function lookupObjectsByCode($a_code)
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];
        
        $query = "SELECT obj_id FROM grp_settings " .
            "WHERE reg_ac_enabled = " . $ilDB->quote(1, 'integer') . " " .
            "AND reg_ac = " . $ilDB->quote($a_code, 'text');
        $res = $ilDB->query($query);
        
        $obj_ids = array();
        while ($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT)) {
            $obj_ids[] = $row->obj_id;
        }
        return $obj_ids;
    }
    
    /**
     * @see ilMembershipRegistrationCodes::register()
     * @param int user_id
     * @param int role
     * @param bool force registration and do not check registration constraints.
     */
    public function register($a_user_id, $a_role = IL_GRP_MEMBER, $a_force_registration = false)
    {
        include_once './Services/Membership/exceptions/class.ilMembershipRegistrationException.php';
        include_once "./Modules/Group/classes/class.ilGroupParticipants.php";
        $part = ilGroupParticipants::_getInstanceByObjId($this->getId());

        if ($part->isAssigned($a_user_id)) {
            return true;
        }
        
        if (!$a_force_registration) {
            // Availability
            if (!$this->isRegistrationEnabled()) {
                include_once './Modules/Group/classes/class.ilObjGroupAccess.php';

                if (!ilObjGroupAccess::_usingRegistrationCode()) {
                    throw new ilMembershipRegistrationException('Cannot registrate to group ' . $this->getId() .
                        ', group subscription is deactivated.', ilMembershipRegistrationException::REGISTRATION_CODE_DISABLED);
                }
            }

            // Time Limitation
            if (!$this->isRegistrationUnlimited()) {
                $start = $this->getRegistrationStart();
                $end = $this->getRegistrationEnd();
                $time = new ilDateTime(time(), IL_CAL_UNIX);

                if (!(ilDateTime::_after($time, $start) and ilDateTime::_before($time, $end))) {
                    throw new ilMembershipRegistrationException('Cannot registrate to group ' . $this->getId() .
                    ', group is out of registration time.', ilMembershipRegistrationException::OUT_OF_REGISTRATION_PERIOD);
                }
            }

            // Max members
            if ($this->isMembershipLimited()) {
                $free = max(0, $this->getMaxMembers() - $part->getCountMembers());
                include_once('./Modules/Group/classes/class.ilGroupWaitingList.php');
                $waiting_list = new ilGroupWaitingList($this->getId());
                if ($this->isWaitingListEnabled() and (!$free or $waiting_list->getCountUsers())) {
                    $this->lng->loadLanguageModule("grp");
                    $waiting_list->addToList($a_user_id);

                    $info = sprintf(
                        $this->lng->txt('grp_added_to_list'),
                        $this->getTitle(),
                        $waiting_list->getPosition($a_user_id)
                    );

                    include_once('./Modules/Group/classes/class.ilGroupParticipants.php');
                    include_once('./Modules/Group/classes/class.ilGroupMembershipMailNotification.php');
                    $participants = ilGroupParticipants::_getInstanceByObjId($this->getId());
                    $participants->sendNotification(ilGroupMembershipMailNotification::TYPE_WAITING_LIST_MEMBER, $a_user_id);

                    throw new ilMembershipRegistrationException($info, ilMembershipRegistrationException::ADDED_TO_WAITINGLIST);
                }

                if (!$free or $waiting_list->getCountUsers()) {
                    throw new ilMembershipRegistrationException('Cannot registrate to group ' . $this->getId() .
                        ', membership is limited.', ilMembershipRegistrationException::OBJECT_IS_FULL);
                }
            }
        }
        
        $part->add($a_user_id, $a_role);
        $part->sendNotification(ilGroupMembershipMailNotification::TYPE_ADMISSION_MEMBER, $a_user_id);
        $part->sendNotification(ilGroupMembershipMailNotification::TYPE_NOTIFICATION_REGISTRATION, $a_user_id);
        return true;
    }

    // fau: fairSub - new function findFairAutoFill
    /**
     * Find groups that can be auto filled after the fair subscription time
     * @return int[]	object ids
     */
    public static function findFairAutoFill()
    {
        global $ilDB;

        // find all groups with a finished fair period in the last month
        // that are not filled or last filled before the fair period
        $query = "
			SELECT s.obj_id
			FROM grp_settings s
			INNER JOIN object_reference r ON r.obj_id = s.obj_id
			WHERE r.deleted IS NULL
			AND s.grp_type <> 1
			AND registration_mem_limit > 0
			AND registration_max_members > 0
			AND s.sub_auto_fill > 0
			AND s.sub_fair > (UNIX_TIMESTAMP() - 3600 * 24 * 30)
			AND sub_fair < UNIX_TIMESTAMP()
			AND (s.sub_last_fill IS NULL OR s.sub_last_fill < s.sub_fair)
		";

        $obj_ids = array();
        $result = $ilDB->query($query);
        while ($row = $ilDB->fetchAssoc($result)) {
            $obj_ids[] = $row['obj_id'];
        }
        return $obj_ids;
    }
    // fau.

    // fau: fairSub - fill only assignable users, treat manual fill, return filled users
    /**
     * Auto fill free places in the course from the waiting list
     * @param bool 		$manual		called manually by admin
     * @param bool 		$initial	called initially by cron job after fair time
     * @return int[]	added user ids
     */
    public function handleAutoFill($manual = false, $initial = false)
    {
        $added_users = array();
        $last_fill = $this->getSubscriptionLastFill();

        // never fill if subscriptions are still fairly collected, even if manual call (should not happen)
        if ($this->inSubscriptionFairTime()) {
            return array();
        }

        // check the conditions for autofill
        if ($manual
            || $initial
            || ($this->isWaitingListEnabled() && $this->hasWaitingListAutoFill())
        ) {
            include_once('./Modules/Group/classes/class.ilGroupWaitingList.php');
            include_once('./Modules/Group/classes/class.ilGroupParticipants.php');
            include_once('./Modules/Course/classes/class.ilObjCourseGrouping.php');

            $max = (int) $this->getMaxMembers();
            $now = ilGroupParticipants::lookupNumberOfMembers($this->getRefId());

            if ($max == 0 || $max > $now) {
                // see assignFromWaitingListObject()
                $waiting_list = new ilGroupWaitingList($this->getId());
                $members_obj = ilGroupParticipants::_getInstanceByObjId($this->getId());
                $grouping_ref_ids = (array) ilObjCourseGrouping::_getGroupingItems($this);

                foreach ($waiting_list->getAssignableUserIds($max == 0 ? null :  $max - $now) as $user_id) {
                    // check conditions for adding the member
                    if (
                        // user does not longer exist
                        ilObjectFactory::getInstanceByObjId($user_id, false) == false
                        // user is already assigned to the course
                        || $members_obj->isAssigned($user_id) == true
                        // user is already assigned to a grouped course
                        || ilObjCourseGrouping::_checkGroupingDependencies($this, $user_id) == false
                    ) {
                        $waiting_list->removeFromList($user_id);
                        continue;
                    }

                    // avoid race condition
                    if ($members_obj->addLimited($user_id, IL_GRP_MEMBER, $max)) {
                        // user is now member
                        $added_users[] = $user_id;

                        // delete user from this and grouped waiting lists
                        $waiting_list->removeFromList($user_id);
                        foreach ($grouping_ref_ids as $ref_id) {
                            ilWaitingList::deleteUserEntry($user_id, ilObject::_lookupObjId($ref_id));
                        }
                    } else {
                        // last free places are taken by parallel requests, don't try further
                        break;
                    }

                    $now++;
                    if ($max > 0 && $now >= $max) {
                        break;
                    }
                }

                // get the user that remain on the waiting list
                $waiting_users = $waiting_list->getUserIds();

                // prepare notifications
                // the waiting list object is injected to allow the inclusion of the waiting list position
                include_once('./Modules/Group/classes/class.ilGroupMembershipMailNotification.php');
                $mail = new ilGroupMembershipMailNotification();
                $mail->setRefId($this->ref_id);
                $mail->setWaitingList($waiting_list);

                // send notifications to added users
                if (!empty($added_users)) {
                    $mail->setType(ilGroupMembershipMailNotification::TYPE_ADMISSION_MEMBER);
                    $mail->setRecipients($added_users);
                    $mail->send();
                }

                // send notifications to waiting users if waiting list is automatically filled for the first time
                // the distinction between requests and subscriptions is done in the send() function
                if (empty($last_fill) && !empty($waiting_users)) {
                    $mail->setType(ilGroupMembershipMailNotification::TYPE_AUTOFILL_STILL_WAITING);
                    $mail->setRecipients($waiting_users);
                    $mail->send();
                }

                // send notification to course admins if waiting users have to be confirmed and places are free
                // this should be done only once after the end of the fair time
                if ($initial
                    && $waiting_list->getCountToConfirm() > 0
                    && ($max == 0 || $max > $now)) {
                    $mail->setType(ilGroupMembershipMailNotification::TYPE_NOTIFICATION_AUTOFILL_TO_CONFIRM);
                    $mail->setRecipients($members_obj->getNotificationRecipients());
                    $mail->send();
                }
            }
        }

        // remember the fill date
        // this prevents further calls from the cron job
        $this->saveSubscriptionLastFill(time());

        return $added_users;
    }
    // fau.

    public static function mayLeave($a_group_id, $a_user_id = null, &$a_date = null)
    {
        global $DIC;

        $ilUser = $DIC['ilUser'];
        $ilDB = $DIC['ilDB'];
        
        if (!$a_user_id) {
            $a_user_id = $ilUser->getId();
        }
        
        $set = $ilDB->query("SELECT leave_end" .
            " FROM grp_settings" .
            " WHERE obj_id = " . $ilDB->quote($a_group_id, "integer"));
        $row = $ilDB->fetchAssoc($set);
        if ($row && $row["leave_end"]) {
            // timestamp to date
            $limit = date("Ymd", $row["leave_end"]);
            if ($limit < date("Ymd")) {
                $a_date = new ilDate(date("Y-m-d", $row["leave_end"]), IL_CAL_DATE);
                return false;
            }
        }
        return true;
    }
    
    /**
     * Minimum members check
     * @global $ilDB $ilDB
     * @return array
     */
    public static function findGroupsWithNotEnoughMembers()
    {
        $ilDB = $GLOBALS['DIC']->database();
        $tree = $GLOBALS['DIC']->repositoryTree();
        
        $res = array();
        
        $now = date("Y-m-d H:i:s");

        $before = new ilDateTime(time(), IL_CAL_UNIX);
        $before->increment(IL_CAL_DAY, -1);
        $now_date = $before->get(IL_CAL_DATETIME);
        $now = $before->get(IL_CAL_UNIX);

        $set = $ilDB->query($q = "SELECT obj_id, registration_min_members" .
            " FROM grp_settings" .
            " WHERE registration_min_members > " . $ilDB->quote(0, "integer") .
            " AND registration_mem_limit = " . $ilDB->quote(1, "integer") . // #17206
            " AND ((leave_end IS NOT NULL" .
                " AND leave_end < " . $ilDB->quote($now, "integer") . ")" .
                " OR (leave_end IS NULL" .
                " AND registration_end IS NOT NULL" .
                " AND registration_end < " . $ilDB->quote($now_date, "text") . "))" .
            " AND (grp_start IS NULL OR grp_start > " . $ilDB->quote($now, "integer") . ")");
        while ($row = $ilDB->fetchAssoc($set)) {
            $refs = ilObject::_getAllReferences($row['obj_id']);
            $ref = end($refs);
            
            if ($tree->isDeleted($ref)) {
                continue;
            }
            
            $part = new ilGroupParticipants($row["obj_id"]);
            $reci = $part->getNotificationRecipients();
            if (sizeof($reci)) {
                $missing = (int) $row["registration_min_members"] - $part->getCountMembers();
                if ($missing > 0) {
                    $res[$row["obj_id"]] = array($missing, $reci);
                }
            }
        }
        
        return $res;
    }
} //END class.ilObjGroup
