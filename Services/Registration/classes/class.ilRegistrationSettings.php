<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2001 ILIAS open source, University of Cologne            |
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


define('IL_REG_DISABLED',1);
define('IL_REG_DIRECT',2);
define('IL_REG_APPROVE',3);
define('IL_REG_ACTIVATION',4);
define('IL_REG_CODES',5);

define('IL_REG_ROLES_FIXED',1);
define('IL_REG_ROLES_EMAIL',2);

define('IL_REG_ERROR_UNKNOWN',1);
define('IL_REG_ERROR_NO_PERM',2);

/**
* Class ilObjAuthSettingsGUI
*
* @author Stefan Meyer <smeyer.ilias@gmx.de>
* @version $Id$
* 
* @ingroup ServicesRegistration
*/
class ilRegistrationSettings
{
	const ERR_UNKNOWN_RCP = 1;
	const ERR_MISSING_RCP = 2;
	
	const REG_HASH_LIFETIME_MIN_VALUE = 60;

// fau: regCodes - new constants for login generation
	const LOGIN_GEN_MANUAL = 'manual';
	const LOGIN_GEN_FIRST_LASTNAME = 'firstlastname';
	const LOGIN_GEN_GUEST_LISTENER = 'guestlistener';
	const LOGIN_GEN_GUEST_SELFREG = 'guestselfreg';
// fau.

// fau: regCodes - new constants for password generation
    const PW_GEN_MANUAL = 0;
    const PW_GEN_AUTO = 1;
    const PW_GEN_LOGIN = 2;
// fau.

// fau: regCodes - variable for code object
	/** @var  ilRegistrationCode $codeObj */
	protected $codeObj;
// fau.

	private $registration_type;
	private $password_generation_enabled;
	private $access_limitation;
	private $approve_recipient_logins;
	private $approve_recipient_ids;
	private $role_type;
	private $unknown;		
	private $reg_hash_life_time = 0;
	private $reg_allow_codes = false;
	private $allowed_domains;	
	
	function __construct()
	{
		$this->__read();
	}

// fau: regCodes - new function getInstance
	public static function getInstance()
	{
		/** @var ilRegistrationSettings $instance */
		static $instance;

		if (!isset($instance))
		{
			$instance = new ilRegistrationSettings();
		}
		return $instance;
	}
// fau.

// fau: regCodes - new function setCodeObject
	/**
	 * Inject a registration code object
	 * The settings of the will override the generalsettings
	 *
	 * @param ilRegistrationCode $codeObj
	 */
	public function setCodeObject(ilRegistrationCode $codeObj)
	{
		$this->codeObj = $codeObj;
	}
// fau.


// fau: regCodes - new function getLoginGenerationTypes
	/**
	 * Get a list of selectable login generation types
	 * @return array
	 */
	public static function getLoginGenerationTypes()
	{
		global $lng;
		$lng->loadLanguageModule('registration');

		return array (
			self::LOGIN_GEN_MANUAL => $lng->txt('reg_login_gen_manual'),
			self::LOGIN_GEN_FIRST_LASTNAME => $lng->txt('reg_login_gen_first_lastname'),
			self::LOGIN_GEN_GUEST_SELFREG => $lng->txt('reg_login_gen_guest_selfreg'),
			self::LOGIN_GEN_GUEST_LISTENER => $lng->txt('reg_login_gen_guest_listener')
		);
	}
// fau.

// fau: regCodes - new function loginGenerationType
	/**
	 * Get the login generation type (with or without code)
	 * @return string
	 */
	public function loginGenerationType()
	{
		if (isset($this->codeObj))
		{
			return $this->codeObj->login_generation_type;
		}
		else
		{
			return self::LOGIN_GEN_MANUAL;
		}
	}
// fau.

	function getRegistrationType()
	{
		return $this->registration_type;
	}
	function setRegistrationType($a_type)
	{
		$this->registration_type = $a_type;
	}

	static function _lookupRegistrationType()
	{
		global $ilSetting;

		$ret = (int)$ilSetting->get('new_registration_type',IL_REG_DISABLED);

		if($ret < 1 or $ret > 5)
		{
			//data is corrupted and should be processed like "No Registration possible" (#18261)
			$ret = IL_REG_DISABLED;
		}

		return $ret;
	}

	function enabled()
	{
		return $this->registration_type != IL_REG_DISABLED;
	}
	function directEnabled()
	{
		return $this->registration_type == IL_REG_DIRECT;
	}
	function approveEnabled()
	{
		return $this->registration_type == IL_REG_APPROVE;
	}
	public function activationEnabled()
	{
// fau: regCodes - take code settings for email verification with precedence
		if (isset($this->codeObj))
		{
			return $this->codeObj->email_verification;
		}
// fau.
		return $this->registration_type == IL_REG_ACTIVATION;
	}
	function registrationCodeRequired()
	{
		return $this->registration_type == IL_REG_CODES;
	}
	
	function passwordGenerationEnabled()
	{
// fau: regCodes - take code settings for password generation with precedence
		if (isset($this->codeObj))
		{
		    // the login generation type is treated like manual generation
            // this will affect the page after registration but not other functionality
			return ($this->codeObj->password_generation == self::PW_GEN_AUTO);
		}
// fau.
		return $this->password_generation_enabled;
	}
	function setPasswordGenerationStatus($a_status)
	{
		$this->password_generation_enabled = $a_status;
	}

// fau: regCodes - new function passwordGenerationType()
    function passwordGenerationType()
    {
        if (isset($this->codeObj))
        {
            return ($this->codeObj->password_generation);
        }
        return ($this->password_generation_enabled ? self::PW_GEN_AUTO : self::PW_GEN_MANUAL);
    }
// fau.


// fau: regCodes - new function getPasswordGenerationTypes
    /**
     * Get a list of selectable password generation types
     * @return array
     */
    public static function getPasswordGenerationTypes()
    {
        global $lng;
        $lng->loadLanguageModule('registration');

        return array (
            self::PW_GEN_MANUAL => $lng->txt('reg_pw_gen_manual'),
            self::PW_GEN_AUTO => $lng->txt('reg_pw_gen_auto'),
            self::PW_GEN_LOGIN => $lng->txt('reg_pw_gen_login'),
        );
    }
// fau.

	function getAccessLimitation()
	{
		return $this->access_limitation;
	}

	function setAccessLimitation($a_access_limitation)
	{
		$this->access_limitation = $a_access_limitation;
	}

	function setApproveRecipientLogins($a_rec_string)
	{
		$this->approve_recipient_logins = $a_rec_string;
		$this->approve_recipient_ids = array();

		// convert logins to array of ids
		foreach(explode(',',trim($this->approve_recipient_logins)) as $login)
		{
			if($uid = ilObjUser::_lookupId(trim($login)))
			{
				$this->approve_recipient_ids[] = $uid;
			}
		}
	}
	function getApproveRecipientLogins()
	{
		return $this->approve_recipient_logins;
	}
	function getApproveRecipients()
	{
// fau: regCodes - take the code settings for approve recipients with precedence
		if (isset($this->codeObj))
		{
			return $this->codeObj->notification_users;
		}
// fau.
		return $this->approve_recipient_ids ? $this->approve_recipient_ids : array();
	}
	function getUnknown()
	{
		return implode(',',$this->unknown);
	}

	function roleSelectionEnabled()
	{
		return $this->role_type == IL_REG_ROLES_FIXED;
	}
	function automaticRoleAssignmentEnabled()
	{
		return $this->role_type == IL_REG_ROLES_EMAIL;
	}
	function setRoleType($a_type)
	{
		$this->role_type = $a_type;
	}
	
	public function setRegistrationHashLifetime($a_lifetime)
	{
		$this->reg_hash_life_time = $a_lifetime;
		
		return $this;
	}
	
	public function getRegistrationHashLifetime()
	{
// fau: regCodes - take the code settings for approve recipients with precedence
		if (isset($this->codeObj))
		{
			return max($this->codeObj->email_verification_time, self::REG_HASH_LIFETIME_MIN_VALUE);
		}
// fau.

		return max($this->reg_hash_life_time, self::REG_HASH_LIFETIME_MIN_VALUE);
	}

	public function setAllowCodes($a_allow_codes)
	{
		$this->reg_allow_codes = (bool)$a_allow_codes;

		return $this;
	}

	public function getAllowCodes()
	{
		return $this->reg_allow_codes;
	}
	
	public function setAllowedDomains($a_value)
	{
		$a_value = explode(";", trim($a_value));
		$this->allowed_domains = $a_value;
	}
	
	public function getAllowedDomains()
	{
// fau: regCodes - don't restrict email domains with a code
		if (isset($this->codeObj))
		{
			return array();
		}
// fau.
		return (array)$this->allowed_domains;
	}
	
	function validate()
	{		
		$this->unknown = array();
		$this->mail_perm = array();

		$login_arr = explode(',',$this->getApproveRecipientLogins());
		$login_arr = $login_arr ? $login_arr : array();
		foreach($login_arr as $recipient)
		{
			if(!$recipient = trim($recipient))
			{
				continue;
			}
			if(!ilObjUser::_lookupId($recipient))
			{
				$this->unknown[] = $recipient;
				continue;
			}
			else
			{
				$valid = $recipient;
			}
		}
		if(count($this->unknown))
		{
			return self::ERR_UNKNOWN_RCP;
		}
		if($this->getRegistrationType() == IL_REG_APPROVE and !count((array) $valid))
		{
			return self::ERR_MISSING_RCP;
		}
		return 0;
	}

			
	function save()
	{
		global $ilias;

		$ilias->setSetting('reg_role_assignment',$this->role_type);
		$ilias->setSetting('new_registration_type',$this->registration_type);
		$ilias->setSetting('passwd_reg_auto_generate',$this->password_generation_enabled);
		$ilias->setSetting('approve_recipient',addslashes(serialize($this->approve_recipient_ids)));
		$ilias->setSetting('reg_access_limitation',$this->access_limitation);
		$ilias->setSetting('reg_hash_life_time',$this->reg_hash_life_time);
		$ilias->setSetting('reg_allow_codes',$this->reg_allow_codes);	
		$ilias->setSetting('reg_allowed_domains',implode(';', $this->allowed_domains));
		
		return true;
	}

	function __read()
	{
		global $ilias;

		//static method validates value
		$this->registration_type = self::_lookupRegistrationType();

		$this->role_type = $ilias->getSetting('reg_role_assignment',1);
		$this->password_generation_enabled = $ilias->getSetting('passwd_reg_auto_generate');
		$this->access_limitation = $ilias->getSetting('reg_access_limitation');
		$this->reg_hash_life_time = $ilias->getSetting('reg_hash_life_time');
		$this->reg_allow_codes = (bool)$ilias->getSetting('reg_allow_codes');
		
		$this->approve_recipient_ids = unserialize(stripslashes($ilias->getSetting('approve_recipient')));
		$this->approve_recipient_ids = $this->approve_recipient_ids ? 
			$this->approve_recipient_ids : 
			array();

		// create login array
		$tmp_logins = array();
		foreach($this->approve_recipient_ids as $id)
		{
			if($login = ilObjUser::_lookupLogin($id))
			{
				$tmp_logins[] = $login;
			}
		}
		$this->approve_recipient_logins = implode(',',$tmp_logins);

		$this->setAllowedDomains($ilias->getSetting('reg_allowed_domains'));
	}
}
?>