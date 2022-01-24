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
	 * Flag for lazy-loading of the client settings
	 * @var bool
	 */
	private $default_settings_loaded = false;

	/**
	 * Flag for lazy-loading of the default settings
	 * @var bool
	 */
	private $client_settings_loaded = false;

	/**
	* Array with default settings
	* @var array
	*/
	private $default_settings = array();


	/**
	* Array with client dependent settings
	* @var array
	*/
	private $client_settings = array();


	/**
	 * Lazy loading of the settings when they are needed
	 * Force a loading if ini file is given as parameter (for use in setup)
	 * @param ilIniFile  $ilClientIniFile
	 */
	public function loadSettings($ilClientIniFile = null)
	{
		global $DIC;

		if ($ilClientIniFile instanceof ilIniFile)
		{
			$this->client_settings = $ilClientIniFile->readGroup("customize");
			$this->client_settings_loaded = true;
		}
		elseif (!$this->client_settings_loaded)
		{
			// read the client settings if available
			if (isset($DIC) && $DIC->offsetExists('ilClientIniFile'))
			{
				/** @var ilIniFile $ilClientIniFile */
				$ilClientIniFile = $DIC['ilClientIniFile'];
				if ($ilClientIniFile instanceof ilIniFile) {
                    $this->client_settings = $ilClientIniFile->readGroup("customize");
                    $this->client_settings_loaded = true;
                }
			}
		}

		if (!$this->default_settings_loaded)
		{
			// read the default settings
			$ini = new ilIniFile("./Customizing/customize.ini.php");
			$ini->read();
			$this->default_settings = $ini->readGroup("default");
			$this->default_settings_loaded = true;
		}
	}

	/**
	 * get a customization setting
	 *
	 * @param	string		setting name
	 * @return   mixed  	setting value
	 */
	private function getSetting($a_setting)
	{
		$this->loadSettings();

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
        global $DIC;

        static $allowed = null;

        if (!isset($allowed))
        {
            if (self::administrationIsVisible())
            {
                $allowed = true;
            }
            elseif ($DIC->rbac()->review()->isAssigned($DIC->user()->getId(), ilCust::get('fair_admin_role_id')))
            {
                $allowed = true;
            }
            else
            {
                $allowed = false;
            }
        }

        return $allowed;
	}
}
