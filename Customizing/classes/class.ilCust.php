<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* fau: customSettings - class for looking up customization settings
*
* Settings are looked up in the following ini files:
*
* 1. [customize] section in data/<client>/client.ini.php
* 2. [default]   section in Customizing/customize.ini.php
*
* Each setting should have at least a definition
* in the default section of customize.ini.php (last lookup).
* The default setting should correspond to a non-customized ILIAS.
*
* Settings should have positive naming similar to:
* <module>_<show|enable|with>_<element> = "0|1"
*
* @author	Fred Neumann <fred.neumann@fim.uni-erlangen.de>
* @package	ilias-core
*/
class ilCust
{
	/** @var self */
	static $instance;

	/**
	* Array with default settings
	* @var array
	* @access private
	*/
	var $default_settings = array();


	/**
	* Array with client dependent settings
	* @var array
	* @access private
	*/
	var $client_settings = array();


	/**
	 * Constructor
	 * @todo make private when all calls are migrated to the static function
	 */
	public function __construct()
	{
		global $DIC;

		/** @var ilIniFile $ilClientIniFile */
		$ilClientIniFile = $DIC['ilClientIniFile'];

        $ini = new ilIniFile("./Customizing/customize.ini.php");
		$ini->read();
		
		// settings will be looked up in the following order
		$this->client_settings = $ilClientIniFile->readGroup("customize");
		$this->default_settings = $ini->readGroup("default");
	}

	/**
	 * Get an instance of the object
	 * @return self
	 */
	public static function getInstance()
	{
		if (!isset(self::$instance)) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Statically get a setting (preferred)
	 * @param $a_setting
	 * @return mixed
	 */
	public static function get($a_setting)
	{
		return self::getInstance()->getSetting($a_setting);
	}


	/**
	 * get a customization setting
	 *
	 * @param	string		setting name
	 * @return   mixed  	setting value
	 * @deprecated 			use the static function instead
	 */
	public function getSetting($a_setting)
	{
		if (isset($this->client_settings[$a_setting]))
		{
			return $this->client_settings[$a_setting];
		}
		elseif (isset($this->default_settings[$a_setting]))
		{
			return $this->default_settings[$a_setting];
		}

		return '';
	}

	/**
	* Checks if the administration section should be visible
	* @return   boolean
	*/
	public static function administrationIsVisible()
	{
		global $DIC;
		return $DIC->rbac()->system()->checkAccess("visible", SYSTEM_FOLDER_ID);
	}

	
	/**
	* Checks if a user has extended access to other user data
	* @return   boolean
	*/
	public static function extendedUserDataAccess()
	{
		global $DIC;
		
		static $allowed = null;
		
		if (!isset($allowed))
		{
			$privacy = ilPrivacySettings::_getInstance();
			$allowed = $DIC->rbac()->system()->checkAccess('export_member_data', $privacy->getPrivacySettingsRefId());
		}
		
		return $allowed;
	}

	/**
	* Checks if assessment settings can be edited
	* @return   bool
	*/
	public static function editAssessmentSettingsIsAllowed()
	{
		global $DIC;
		$tree = $DIC->repositoryTree();
		$rbacsystem = $DIC->rbac()->system();

		static $allowed = null;

		if (!isset($allowed))
		{
			$assf = current($tree->getChildsByType(SYSTEM_FOLDER_ID, 'assf'));
			$allowed = $rbacsystem->checkAccess('write', $assf['ref_id']);
		}
		return $allowed;
	}

	/**
	 * Check if a deactivation of the subscription fair time is allowed in courses and groups
	 * @return bool
	 */
	public static function deactivateFairTimeIsAllowed()
	{
		return self::administrationIsVisible();
	}
}
