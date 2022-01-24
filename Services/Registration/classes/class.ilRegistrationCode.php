<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* Class ilRegistrationCode
*
* @author Stefan Meyer <smeyer.ilias@gmx.de>
* @version $Id: class.ilRegistrationSettingsGUI.php 23797 2010-05-07 15:54:03Z jluetzen $
*
* @ingroup ServicesRegistration
*/
class ilRegistrationCode
{
    const DB_TABLE = 'reg_registration_codes';
    const CODE_LENGTH = 10;

    // fau: regCodes - define object properties

    /**
     * @var integer $code_id
     */
    public $code_id;

    /**
     * @var string $code
     */
    public $code;

    /**
     * @var ilDateTime $generated
     */
    public $generated;

    /**
     * @var string	$title
     */
    public $title;

    /**
     * @var string	$description
     */
    public $description;

    /**
     * @var integer	$use_limit
     */
    public $use_limit;

    /**
     * @var	integer	$use_count
     */
    public $use_count;

    /**
     * @var ilDateTime $last_use
     */
    public $last_use;

    /**
     * @var integer	$global_role
     */
    public $global_role;

    /**
     * @var array[integer]	$local_roles
     */
    public $local_roles;

    /**
     * @var string	$limit_type
     */
    public $limit_type;

    /**
     * @var ilDateTime	$limit_date
     */
    public $limit_date;

    /**
     * @var	array $limit_duration;
     */
    public $limit_duration;

    /**
     * @var	bool	$reg_enabled
     */
    public $reg_enabled;

    /**
     * @var bool	$ext_enabled
     */
    public $ext_enabled;

    /**
     * @var	string	$login_generation_type
     */
    public $login_generation_type;

    /**
     * @var bool	$password_generation
     */
    public $password_generation;

    /**
     * @var	bool	$captcha_required
     */
    public $captcha_required;


    /**
     * @var bool	$email_verification
     */
    public $email_verification;

    /**
     * @var integer $email_verification_time
     */
    public $email_verification_time;


    /**
     * @var array	$notification_users
     */
    public $notification_users;

    // fau.

    public static function create($role, $stamp, $local_roles, $limit, $limit_date, $reg_type, $ext_type)
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];
        
        $id = $ilDB->nextId(self::DB_TABLE);
        
        // create unique code
        $found = true;
        while ($found) {
            $code = self::generateRandomCode();
            $chk = $ilDB->queryF("SELECT code_id FROM " . self::DB_TABLE . " WHERE code = %s", array("text"), array($code));
            $found = (bool) $ilDB->numRows($chk);
        }
        
        if (is_array($local_roles)) {
            $local_roles = implode(";", $local_roles);
        }
        if ($limit == "relative" && is_array($limit_date)) {
            $limit_date = serialize($limit_date);
        }
        
        $data = array(
            'code_id' => array('integer', $id),
            'code' => array('text', $code),
            'generated_on' => array('integer', $stamp),
            'role' => array('integer', $role),
            'role_local' => array('text', $local_roles),
            'alimit' => array('text', $limit),
            'alimitdt' => array('text', $limit_date),
            'reg_enabled' => array('integer',$reg_type),
            'ext_enabled' => array('integer',$ext_type)
            );

        $ilDB->insert(self::DB_TABLE, $data);
        return $id;
    }
    
    protected static function generateRandomCode()
    {
        // missing : 01iloO
        $map = "23456789abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ";
        
        $code = "";
        $max = strlen($map) - 1;
        // fau: regCodes - customize the length of generated registration codes
        for ($loop = 1; $loop <= ilCust::get('reg_code_length'); $loop++) {
            $code .= $map[mt_rand(0, $max)];
        }
        // fau.
        return $code;
    }
    
    public static function getCodesData($order_field, $order_direction, $offset, $limit, $filter_code, $filter_role, $filter_generated, $filter_access_limitation)
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];
        
        // filter
        $where = self::filterToSQL($filter_code, $filter_role, $filter_generated, $filter_access_limitation);

        // count query
        $set = $ilDB->query("SELECT COUNT(*) AS cnt FROM " . self::DB_TABLE . $where);
        $cnt = 0;
        if ($rec = $ilDB->fetchAssoc($set)) {
            $cnt = $rec["cnt"];
        }
        
        $sql = "SELECT * FROM " . self::DB_TABLE . $where;
        if ($order_field) {
            if ($order_field == 'generated') {
                $order_field = 'generated_on';
            }
            $sql .= " ORDER BY " . $order_field . " " . $order_direction;
        }
        
        // set query
        $ilDB->setLimit((int) $limit, (int) $offset);
        $set = $ilDB->query($sql);
        $result = array();
        while ($rec = $ilDB->fetchAssoc($set)) {
            $rec['generated'] = $rec['generated_on'];
            $result[] = $rec;
        }
        return array("cnt" => $cnt, "set" => $result);
    }
    
    public static function loadCodesByIds(array $ids)
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];

        $set = $ilDB->query("SELECT * FROM " . self::DB_TABLE . " WHERE " . $ilDB->in("code_id", $ids, false, "integer"));
        $result = array();
        while ($rec = $ilDB->fetchAssoc($set)) {
            $result[] = $rec;
        }
        return $result;
    }
    
    public static function deleteCodes(array $ids)
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];

        if (sizeof($ids)) {
            return $ilDB->manipulate("DELETE FROM " . self::DB_TABLE . " WHERE " . $ilDB->in("code_id", $ids, false, "integer"));
        }
        return false;
    }
    
    public static function getGenerationDates()
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];
        
        $set = $ilDB->query("SELECT DISTINCT(generated_on) genr FROM " . self::DB_TABLE . " ORDER BY genr");
        $result = array();
        while ($rec = $ilDB->fetchAssoc($set)) {
            $result[] = $rec["genr"];
        }
        return $result;
    }
    
    protected static function filterToSQL($filter_code, $filter_role, $filter_generated, $filter_access_limitation)
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];

        $where = array();
        if ($filter_code) {
            $where[] = $ilDB->like("code", "text", "%" . $filter_code . "%");
        }
        if ($filter_role) {
            $where[] = "role = " . $ilDB->quote($filter_role, "integer");
        }
        if ($filter_generated) {
            $where[] = "generated_on = " . $ilDB->quote($filter_generated, "text");
        }
        if ($filter_access_limitation) {
            $where[] = "alimit = " . $ilDB->quote($filter_access_limitation, "text");
        }
        if (sizeof($where)) {
            return " WHERE " . implode(" AND ", $where);
        } else {
            return "";
        }
    }
    
    public static function getCodesForExport($filter_code, $filter_role, $filter_generated, $filter_access_limitation)
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];

        // filter
        $where = self::filterToSQL($filter_code, $filter_role, $filter_generated, $filter_access_limitation);

        // set query
        $set = $ilDB->query("SELECT code FROM " . self::DB_TABLE . $where . " ORDER BY code_id");
        $result = array();
        while ($rec = $ilDB->fetchAssoc($set)) {
            $result[] = $rec["code"];
        }
        return $result;
    }
    
    /**
     * Check if code has been used already
     * @global type $ilDB
     * @param type $code
     * @return boolean
     */
    public static function isUnusedCode($code)
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];
        
        $set = $ilDB->query("SELECT used FROM " . self::DB_TABLE . " WHERE code = " . $ilDB->quote($code, "text"));
        $set = $ilDB->fetchAssoc($set);
        if ($set && !$set["used"]) {
            return true;
        }
        return false;
    }
    
    /**
     * Check if given code is a valid registration code
     * @param string $a_code code
     * @return bool
     */
    public static function isValidRegistrationCode($a_code)
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];
        
        $query = 'SELECT code_id FROM reg_registration_codes ' .
            'WHERE used = ' . $ilDB->quote(0, 'integer') . ' ' .
            'AND reg_enabled = ' . $ilDB->quote(1, 'integer') . ' ' .
            'AND code = ' . $ilDB->quote($a_code, 'text');
        $res = $ilDB->query($query);
        
        return $res->numRows() ? true : false;
    }

    public static function useCode($code)
    {
        // fau: regCodes - extended writing of code usage
        $codeObj = new ilRegistrationCode($code);
        return $codeObj->addUsage();
        // fau.
    }

    public static function getCodeRole($code)
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];

        $set = $ilDB->query("SELECT role FROM " . self::DB_TABLE . " WHERE code = " . $ilDB->quote($code, "text"));
        $row = $ilDB->fetchAssoc($set);
        if (isset($row["role"])) {
            return $row["role"];
        }
    }
    
    public static function getCodeData($code)
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];

        $set = $ilDB->query("SELECT role, role_local, alimit, alimitdt, reg_enabled, ext_enabled" .
            " FROM " . self::DB_TABLE .
            " WHERE code = " . $ilDB->quote($code, "text"));
        $row = $ilDB->fetchAssoc($set);
        return $row;
    }


    // fau: regCodes - define object methods
    /**
     * ilRegistrationCode constructor.
     * @param string $code		code that should be read from the database
     */
    public function __construct($code = null)
    {
        $this->init();
        $this->code = $code;
        if (!empty($code)) {
            $this->read();
        }
    }

    /**
     * Initialize all values
     */
    public function init()
    {
        $this->code_id = null;
        $this->code = null;
        $this->generated = new ilDateTime();
        $this->title = '';
        $this->description = '';
        $this->use_limit = 1;
        $this->use_count = 0;
        $this->last_use = new ilDateTime();
        $this->global_role = null;
        $this->local_roles = array();
        $this->limit_type = null;
        $this->limit_date = new ilDateTime();
        $this->limit_duration = array();
        $this->reg_enabled = true;
        $this->ext_enabled = false;
        $this->login_generation_type = 'guestlistener';
        $this->password_generation = ilRegistrationSettings::PW_GEN_MANUAL;
        $this->captcha_required = false;
        $this->email_verification = false;
        $this->email_verification_time = 1800;
        $this->notification_users = array();
    }

    /**
     * Read properties from the database
     */
    public function read()
    {
        global $ilDB;

        $query = "SELECT * FROM " . self::DB_TABLE . " WHERE code = " . $ilDB->quote($this->code, "text");
        $result = $ilDB->query($query);

        if ($row = $ilDB->fetchAssoc($result)) {
            $this->code_id = (int) $row['code_id'];
            $this->generated = new ilDateTime($row['generated_on'], IL_CAL_UNIX);
            $this->title = (string) $row['title'];
            $this->description = (string) $row['description'];
            $this->use_limit = (int) $row['use_limit'];
            $this->use_count = (int) $row['use_count'];
            $this->last_use = new ilDateTime($row['used'], IL_CAL_UNIX);
            $this->global_role = (int) $row['role'];
            $this->local_roles = explode(';', (string) $row['role_local']);
            for ($i = 0; $i < count($this->local_roles); $i++) {
                $this->local_roles[$i] = (int) $this->local_roles[$i];
            }
            $this->limit_type = $row['alimit'];
            switch ($this->limit_type) {
                case 'absolute':
                    $this->limit_date = new ilDateTime((string) $row['alimitdt'], IL_CAL_DATE);
                    $this->limit_duration = array();
                    break;
                case 'relative':
                    $this->limit_date = new ilDateTime();
                    $this->limit_duration = (array) unserialize($row['alimitdt']);
                    break;
                default:
                    $this->limit_date = new ilDateTime();
                    $this->limit_duration = array();
            }
            $this->reg_enabled = (bool) $row['reg_enabled'];
            $this->ext_enabled = (bool) $row['ext_enabled'];
            $this->login_generation_type = (string) $row['login_generation_type'];
            $this->password_generation = (int) $row['password_generation'];
            $this->captcha_required = (bool) $row['captcha_required'];
            $this->email_verification = (bool) $row['email_verification'];
            $this->email_verification_time = (integer) $row['email_verification_time'];
            $this->notification_users = explode(';', (string) $row['notification_users']);
        }
    }

    /**
     * Write properties to the database
     * Create the record if it does not exist
     */
    public function write()
    {
        global $ilDB;

        if (empty($this->code_id)) {
            $this->code_id = $ilDB->nextId(self::DB_TABLE);
        }
        if (empty($this->code)) {
            $this->code = self::generateRandomCode();
        }
        if ($this->generated->isNull()) {
            $this->generated = new ilDateTime(time(), IL_CAL_UNIX);
        }

        switch ($this->limit_type) {
            case 'relative':
                $alimitdt = serialize($this->limit_duration);
                break;
            case 'absolute':
                $alimitdt = $this->limit_date->get(IL_CAL_DATE);
                break;
            default:
                $alimitdt = null;
        }

        $ilDB->replace(
            self::DB_TABLE,
            array(
                'code_id' => array('integer',$this->code_id)
            ),
            array(
                'code' => array('text',$this->code),
                'generated_on' => array('integer', $this->generated->get(IL_CAL_UNIX)),
                'title' => array('text', $this->title),
                'description' => array('text', $this->description),
                'use_limit' => array('integer', $this->use_limit),
                'use_count' => array('integer', $this->use_count),
                'used' => array('integer', (int) $this->last_use->get(IL_CAL_UNIX)),
                'role' => array('integer', $this->global_role),
                'role_local' => array('text', implode(';', $this->local_roles)),
                'alimit' => array('text', $this->limit_type),
                'alimitdt' => array('text', $alimitdt),
                'reg_enabled' => array('integer', $this->reg_enabled),
                'ext_enabled' => array('integer', $this->ext_enabled),
                'login_generation_type' => array('text', $this->login_generation_type),
                'password_generation' => array('integer', $this->password_generation),
                'captcha_required' => array('integer', $this->captcha_required),
                'email_verification' => array('integer', $this->email_verification),
                'email_verification_time' => array('integer', $this->email_verification_time),
                'notification_users' => array('text', implode(';', $this->notification_users))
            )
        );
    }

    /**
     * Get the titles of the local riles
     * @return array
     */
    public function getLocalRoleTitles()
    {
        $titles = array();
        foreach ($this->local_roles as $role_id) {
            $titles[] = ilObject::_lookupTitle($role_id);
        }
        return $titles;
    }

    /**
     * Get a comma separated list of logins for the notification users
     * @return string
     */
    public function getNotificationLogins()
    {
        $logins = array();
        foreach ($this->notification_users as $id) {
            if ($login = ilObjUser::_lookupLogin($id)) {
                $logins[] = $login;
            }
        }
        return implode(',', $logins);
    }

    /**
     * Set a comma separated list of logins for the notification users
     * @param string $logins
     */
    public function setNotificationLogins($logins)
    {
        $this->notification_users = array();
        foreach (explode(',', $logins) as $login) {
            if ($uid = ilObjUser::_lookupId(trim($login))) {
                $this->notification_users[] = $uid;
            }
        }
    }


    /**
     * Check if the code can be used for account registration
     * - is saved
     * - has not reached limit of uses
     * - is before the accounts activation limit
     *
     * @return bool
     */
    public function isUsable()
    {
        if (empty($this->code_id)) {
            return false;
        }

        if ($this->use_limit > 0 and $this->use_limit <= $this->use_count) {
            return false;
        }

        if ($this->limit_type == 'absolute' and ilDateTime::_before($this->limit_date, new ilDateTime(time(), IL_CAL_UNIX))) {
            return false;
        }

        return true;
    }

    /**
     * Add the usage of a the code
     */
    public function addUsage()
    {
        $this->use_count++;
        $this->last_use = new ilDateTime(time(), IL_CAL_UNIX);
        $this->write();
        return true;
    }
    // fau.
}
