<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* fau: rootAsLogin - new class ilCustomLoginGUI
*
* This class creates login forms to be added to container pages
*
* @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
* @version $Id:  $
*/
class ilCustomLoginGUI
{
	/**
	 * Add the login blocks to a page 
	 * 
	 * @param 	string 		html code with placeholders
	 * @return 	string		html code with replaces placeholders
	 */
	static function addLoginBlocks($a_html)
	{
		global $ilUser, $ilSetting;

		if ($ilUser->getId() == ANONYMOUS_USER_ID)
		{
			$block = self::getLoginBlockLocal();
			$a_html = preg_replace('/\[LOGIN\].*\[\/LOGIN\]/', $block, $a_html);

            if ($ilSetting->get("shib_active"))
            {
				$block = self::getLoginBlockSSO();
				$a_html = preg_replace('/\[SSO\].*\[\/SSO\]/', $block, $a_html);
			}
			else
			{
				$a_html = preg_replace('/\[SSO\].*\[\/SSO\]/', '', $a_html);
			}
		}
		else
		{
			$block = self::getLoginBlockLogout();
			$a_html = preg_replace('/\[LOGIN\].*\[\/LOGIN\]/', $block, $a_html);
			$a_html = preg_replace('/\[SSO\].*\[\/SSO\]/', '', $a_html);
		}
		
		$block = self::getLoginBlockLinks();
		$a_html = preg_replace('/\[LOGIN_LINKS\].*\[\/LOGIN_LINKS\]/', $block, $a_html);
		
		return $a_html;
	}

	/**
	 * Get the HTML code for SSO login block
	 * 
	 * @return string
	 */
	static function getLoginBlockSSO()
	{
		global $ilSetting, $lng;

		// prepare the shibboleth link
		$shib_link = 'saml.php';
		if (!empty($_GET["login_target"]))
		{
			$shib_link .= "?target=" . $_GET["login_target"];
	    }
	    elseif (!empty($_GET["target"]))
        {
            $shib_link .= "?target=" . $_GET["target"];
        }

		$tpl = new ilTemplate("tpl.custom_sso.html", true, true, "Services/Init");
		if ($ilSetting->get("shib_login_instructions"))
		{
			$tpl->setVariable("shib_login_instructions", $ilSetting->get("shib_login_instructions"));
		}
		$tpl->setVariable("FORMACTION", ilCust::get("shib_login_help_url"));
		$tpl->setVariable("SHIB_LINK", $shib_link);
		$tpl->setVariable("SHIB_TITLE", $lng->txt("login_to_ilias_via_shibboleth"));
		$tpl->setVariable("SHIB_TITLE_ADD", $lng->txt("login_to_ilias_via_shibboleth_addition"));
		
		if ($help_url = ilCust::get("shib_login_help_url"))
		{
			$tpl->setVariable("HELP_LINK",  $help_url);
			$tpl->setVariable("HELP_TITLE", $lng->txt("shib_login_help_title"));
		}
		
		return $tpl->get();
	}

	
	
	/**
	 * Get the HTML code for local login block
	 * 
	 * @return string
	 */
	static function getLoginBlockLocal()
	{
		global $DIC;

		$ilCtrl = $DIC->ctrl();
		$ilSetting = $DIC->settings();
		$lng = $DIC->language();

		if (!empty($_GET['login_target']))
        {
            $ilCtrl->setParameterByClass('ilStartUpGUI', 'target', $_GET['login_target']);
        }
        elseif (!empty($_GET['target']))
        {
            $ilCtrl->setParameterByClass('ilStartUpGUI', 'target', $_GET['target']);
        }
		$action = $ilCtrl->getFormActionByClass(['ilStartupGUI', 'ilStartUpGUI']);

		$tpl = new ilTemplate("tpl.custom_login.html", true, true, "Services/Init");
		$tpl->setVariable("FORMACTION", $action);
		$tpl->setVariable("LOGIN_TITLE", $lng->txt("local_login_to_ilias"));
		$tpl->setVariable("LOGIN_TITLE_ADD", $lng->txt("local_login_to_ilias_addition"));

		if ($help_url = ilCust::get("ilias_login_help_url"))
		{
			$tpl->setVariable("HELP_LINK",  $help_url);
			$tpl->setVariable("HELP_TITLE", $lng->txt("local_login_help_title"));
		}	
			
		$loginSettings = new ilSetting("login_settings");
		$information = trim($loginSettings->get("login_message_".$lng->getLangKey()));
		if ($information)
		{
			$tpl->setVariable("local_login_instructions", $information);
		}
		
		$tpl->setVariable("LABEL_USERNAME", $lng->txt("username"));
		$tpl->setVariable("LABEL_PASSWORD", $lng->txt("password"));
		$tpl->setVariable("VAL_USERNAME", $_SESSION["username"]);
		$tpl->setVariable("LABEL_SUBMIT", $lng->txt("log_in"));
		
		if ($ilSetting->get("shib_active"))
		{
			$tpl->setVariable("LOGIN_INIT_DISPLAY", "false");
		}
		else
		{
			$tpl->setVariable("LOGIN_INIT_DISPLAY", "true");
		}

  		unset($_SESSION["username"]);
		return $tpl->get();
	}

	
	/**
	 * Get the HTML code for logout block
	 * 
	 * @return string
	 */
	static function getLoginBlockLogout()
	{
		global $DIC;
		$lng = $DIC->language();

		$tpl = new ilTemplate("tpl.custom_logout.html", true, true, "Services/Init");
		$tpl->setVariable("TXT_LOGGED_IN", $lng->txt("logged_in_to_ilias"));
		$tpl->setVariable("TXT_LOGGED_IN_INFO", $lng->txt("logged_in_to_ilias_info"));
		$tpl->setVariable("LINK_LOGOUT", ilUserUtil::_getLogoutLink());
		$tpl->setVariable("TXT_LOGOUT", $lng->txt("logout"));

		return $tpl->get();
	}
	
	
	/**
	 * Get the HTML code for login links block
	 * 
	 * @return string
	 */
	static function getLoginBlockLinks()
	{
		global $ilSetting, $lng, $ilUser;

		$tpl = new ilTemplate("tpl.custom_login_links.html", true, true, "Services/Init");

		$common_par = "lang=".$lng->getLangKey()
					. "&amp;client_id=".CLIENT_ID
					. "&amp;target=".$_GET["login_target"];

		if ($ilSetting->get("password_assistance") and $ilUser->getId() == ANONYMOUS_USER_ID)
		{
			$tpl->setCurrentBlock("password_assistance");
			$tpl->setVariable("FORGOT_PASSWORD", $lng->txt("forgot_password"));
			$tpl->setVariable("FORGOT_USERNAME", $lng->txt("forgot_username"));
			$tpl->setVariable("CMD_FORGOT_PASSWORD","ilias.php?baseClass=ilStartUpGUI&amp;cmd=jumpToPasswordAssistance&amp;".$common_par);
			$tpl->setVariable("CMD_FORGOT_USERNAME","ilias.php?baseClass=ilStartUpGUI&amp;cmd=jumpToUsernameAssistance&amp;".$common_par);
			$tpl->parseCurrentBlock();
		}

		$tpl->setVariable("USER_AGREEMENT", $lng->txt("usr_agreement"));
		$tpl->setVariable("LINK_USER_AGREEMENT", "ilias.php?baseClass=ilStartUpGUI&amp;cmd=showTermsOfService&amp;".$common_par);
		
		return $tpl->get();
	}
}
?>