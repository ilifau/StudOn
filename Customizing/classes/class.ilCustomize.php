<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* fau: customSettings - class for looking up customization settings
*
* Some settings are based on dynamic criteria (e.g. role dependent).
* All other settings are looked up in the following ini files:
*
* 0. [admin] section in Customizing/customize.ini.php if administration is visible
* 1. [norep] section in Customizing/customize.ini.php if repository is not visible
* 2. [user skin] section in Customizing/customize.ini.php
* 3. [customize] section in data/<client>/client.ini.php
* 4. [customize] section in ilias.ini.php
* 5. [default]   section in Customizing/customize.ini.php
*
* The user skin's section corresponds to the directory name of the skin.
* The client and ilias settings can differ between a development
* and a production installation of ILIAS.
*
* Each non-dynamic setting should have at least a definition
* in the default section of customize.ini.php (last lookup).
* The default setting should correspond to a non-customized ILIAS.
*
* Settings should have positive naming similar to:
* <module>_<show|enable|with>_<element> = "0|1"
*
* @author	Fred Neumann <fred.neumann@fim.uni-erlangen.de>
* @version $Id: $
* @package	ilias-core
*/
class ilCustomize
{
	/**
	* Array with default settings
	* @var array
	* @access private
	*/
	var $default_settings = array();


	/**
	* Array with settings if administration is visible
	* @var array
	* @access private
	*/
	var $admin_settings = array();

	/**
	* Array with settings if repository is not visible
	* @var array
	* @access private
	*/
	var $norep_settings = array();

	/**
	* Array with skin dependent settings
	* @var array
	* @access private
	*/
	var $skin_settings = array();
	
	/**
	* Array with client dependent settings
	* @var array
	* @access private
	*/
	var $client_settings = array();

	/**
	* Array with ilias dependent settings
	* @var array
	* @access private
	*/
	var $ilias_settings = array();

	
	/**
	* Constructor
	* @access	public
	*/
	function ilCustomize()
	{
		global $ilAccess, $ilUser, $ilClientIniFile, $ilIliasIniFile;

        $ini = new ilIniFile("./Customizing/customize.ini.php");
		$ini->read();
		
		// settings will be looked up in the following order:
		// admin, norep, skin, client, ilias, default
		
		if (isset($ilAccess))
		{
			if ($this->__administrationIsVisible())
			{
				$this->admin_settings = $ini->readGroup("admin");
			}
			if (!$this->__repositoryIsVisible())
			{
				$this->norep_settings = $ini->readGroup("norep");
			}
		}
		
		if (isset($ilUser))
		{
			$skin = $ilUser->getPref("skin");
			if ($skin != "default")
			{
				$this->skin_settings = $ini->readGroup($skin);
			}
		}
		$this->client_settings = $ilClientIniFile->readGroup("customize");
		$this->ilias_settings = $ilIliasIniFile->readGroup("customize");
		$this->default_settings = $ini->readGroup("default");
	}

	/**
	* get a customization setting
	*
	* @param	string	setting name
	* @return   mixed  	setting value
	* @access	public
	*/
	function getSetting($a_setting)
	{
		switch ($a_setting)
		{
			case "repository_is_visible":
			    return $this->__repositoryIsVisible();

			case "administration_is_visible":
			    return $this->__administrationIsVisible();
			    
			case "export_member_data_is_allowed":
			    return $this->__exportMemberDataIsAllowed();

			case "edit_answered_test_questions_is_allowed":
			    return $this->__editAssessmentSettingsIsAllowed();

			default:
			
				if (isset($this->admin_settings[$a_setting]))
				{
					return $this->admin_settings[$a_setting];
				}
				elseif (isset($this->norep_settings[$a_setting]))
				{
					return $this->norep_settings[$a_setting];
				}
				elseif (isset($this->skin_settings[$a_setting]))
				{
					return $this->skin_settings[$a_setting];
				}
				elseif (isset($this->client_settings[$a_setting]))
				{
					return $this->client_settings[$a_setting];
				}
				elseif (isset($this->ilias_settings[$a_setting]))
				{
					return $this->ilias_settings[$a_setting];
				}
				elseif (isset($this->default_settings[$a_setting]))
				{
					return $this->default_settings[$a_setting];
				}
		}
	}
	

	/**
	* Checks if a dynamic feature is available for a user
	*
	* Dynamic features are defined as invisible repository objects.
	* These objects can be assigned as selected items to roles of users.
	* A customizing setting with the feature name gives the ref_id of the item.
	* A feature is availeble if the item is selected for one of the user's roles.
	*
	* @param	string	    feature name
	* @return   boolean  	feature is available
	* @access	public
	*/
	function checkFeature($a_feature)
	{
		$ref_id = $this->getSetting($a_feature);
		
		return $this->checkRoleItem($ref_id);
	}


	/**
	* Checks if a repository item is assigned to a user's role
	*
	* @param	int	    	item ref id
	* @return   boolean  	item is assigned
	* @access	public
	*/
	function checkRoleItem($a_item_ref_id)
	{
		global $rbacreview, $ilUser;

		include_once './classes/class.ilRoleDesktopItem.php';

		$roles = $rbacreview->assignedRoles($ilUser->getId());
		$role_items = array();
		foreach ($roles as $role_id)
		{
			$items_obj =& new ilRoleDesktopItem($role_id);
			
			if ($items_obj->isAssigned($a_item_ref_id))
			{
				return true;
			}
		}
		return false;
	}

	
	/**
	* Checks if the repository should be visible
	*
	* This checked for:
	* - Repository button in the main menu
	* - Repository path in the locator of objects
	* - Up icon in repository objects
	* - Tree/Flat icon in repository objects
	*
	* Current implementation:
	* checks for visibility of the root folder
	*
	* @return   boolean     repository is visible
	* @access   private
	*/
	private function __repositoryIsVisible()
	{
		global $rbacsystem;
		return $rbacsystem->checkAccess("visible", ROOT_FOLDER_ID);
	}
	

	/**
	* Checks if the administration section should be visible
	*
	* @return   boolean     administration is visible
	* @access   private
	*/
	private function __administrationIsVisible()
	{
		global $rbacsystem;
		return $rbacsystem->checkAccess("visible", SYSTEM_FOLDER_ID);
	}

	
	/**
	* Checks if export of member data is allowed for the user
	*
	* @return   boolean     export of member data is allowed
	* @access   private
	*/
	private function __exportMemberDataIsAllowed()
	{
		global $rbacsystem;
		
		static $allowed = null;
		
		if (!isset($allowed))
		{
			include_once('Services/PrivacySecurity/classes/class.ilPrivacySettings.php');
			$privacy = ilPrivacySettings::_getInstance();
			
			if ($rbacsystem->checkAccess('export_member_data', $privacy->getPrivacySettingsRefId()))
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

	/**
	* Checks if assessment settings can be edited
	*
	* @return   boolean     assessment settings can be edited
	* @access   private
	*/
	private function __editAssessmentSettingsIsAllowed()
	{
		global $tree, $rbacsystem;

		static $allowed = null;
		if (!isset($allowed))
		{
			$assf = current($tree->getChildsByType(SYSTEM_FOLDER_ID, 'assf'));
			if ($rbacsystem->checkAccess('write', $assf['ref_id']))
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
?>