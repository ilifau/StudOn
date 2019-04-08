<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/** @defgroup ServicesRegistration Services/Registration
 */

/**
* Class ilAccountRegistrationGUI
*
* @author Stefan Meyer <smeyer.ilias@gmx.de>
* @version $Id$
*
* @ilCtrl_Calls ilAccountRegistrationGUI:
*
* @ingroup ServicesRegistration
*/

require_once './Services/Registration/classes/class.ilRegistrationSettings.php';
require_once 'Services/TermsOfService/classes/class.ilTermsOfServiceHelper.php';

// fau: regCodes - always inclode the code class
require_once('Services/Registration/classes/class.ilRegistrationCode.php');
// fau.

/**
 * 
 */
class ilAccountRegistrationGUI
{
	protected $registration_settings; // [object]
	protected $code_enabled; // [bool]
	protected $code_was_used; // [bool]
	/** @var \ilObjUser|null */
	protected $userObj;

// fau: regCodes - class variables

    /** @var ilRegistrationCode|null  */
	protected $codeObj = null;

	/** @var  ilPropertyFormGUI $form */
	protected $form;
// fau.

	public function __construct()
	{
		global $ilCtrl,$tpl,$lng;

		$this->tpl =& $tpl;

		$this->ctrl =& $ilCtrl;
		$this->ctrl->saveParameter($this,'lang');

		$this->lng =& $lng;
		$this->lng->loadLanguageModule('registration');

// fau: regCodes - initialize an already entered code and save in settings
		$this->registration_settings = ilRegistrationSettings::getInstance();
		
		$this->code_enabled = ($this->registration_settings->registrationCodeRequired() ||
			$this->registration_settings->getAllowCodes());

		if ($this->code_enabled)
		{
			if (!empty($_GET['code']))
			{
				$this->codeObj = new ilRegistrationCode($_GET['code']);
				if ($this->codeObj->isUsable())
				{
					$_SESSION['ilAccountRegistrationGUI:code'] = $this->codeObj->code;
				}
			}
			elseif ($_SESSION['ilAccountRegistrationGUI:code'])
			{
				$this->codeObj = new ilRegistrationCode(($_SESSION['ilAccountRegistrationGUI:code']));
			}

			if (isset($this->codeObj))
			{
				$this->registration_settings->setCodeObject($this->codeObj);
			}
		}
// fau.
	}

	public function executeCommand()
	{
		global $ilErr, $tpl;

		if($this->registration_settings->getRegistrationType() == IL_REG_DISABLED)
		{
			$ilErr->raiseError($this->lng->txt('reg_disabled'),$ilErr->FATAL);
		}

		$next_class = $this->ctrl->getNextClass($this);
		$cmd = $this->ctrl->getCmd();

		switch($next_class)
		{
			default:
				if($cmd)
				{
					$this->$cmd();
				}
				else
				{
// fau: regCodes - determine default command based on code entry
					if (!$this->code_enabled)
					{
						$this->displayForm();
					}
					elseif (!isset($this->codeObj))
					{
						$this->displayCodeForm();
					}
					elseif (!$this->codeObj->isUsable())
					{
						$this->displayCodeForm();
					}
					else
					{
						$this->displayForm();
					}
// fau.
				}
				break;
		}
		$tpl->setPermanentLink('usr', null, 'registration');
		$tpl->show();
		return true;
	}

// fau: regCodes - handle separate form for code entry
	public function displayCodeForm()
	{
		if(!$this->form)
		{
			$this->__initCodeForm();
		}
		ilStartUpGUI::initStartUpTemplate(array('tpl.usr_registration.html', 'Services/Registration'), true);
		$this->tpl->setVariable('TXT_PAGEHEADLINE', $this->lng->txt('registration'));
		if((bool)$this->registration_settings->registrationCodeRequired())
		{
			$this->tpl->setVariable('DESCRIPTION', $this->lng->txt("registration_code_required_info"));
		}
		else
		{
			$this->tpl->setVariable('DESCRIPTION', $this->lng->txt("registration_code_optional_info"));

		}

		$this->tpl->setVariable('FORM', $this->form->getHTML());
	}


	protected function __initCodeForm()
	{
		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$this->form = new ilPropertyFormGUI();
		$this->form->setFormAction($this->ctrl->getFormAction($this));

		include_once 'Services/Registration/classes/class.ilRegistrationCode.php';
		$code = new ilTextInputGUI($this->lng->txt("registration_code"), "usr_registration_code");
		$code->setSize(40);
		$code->setMaxLength(ilRegistrationCode::CODE_LENGTH);
		$code->setRequired((bool)$this->registration_settings->registrationCodeRequired());
		$this->form->addItem($code);

		$this->form->addCommandButton("saveCodeForm", $this->lng->txt("register"));
		$this->form->addCommandButton("cancelForm", $this->lng->txt("cancel"));
	}


	public function saveCodeForm()
	{
		$this->__initCodeForm();

		$valid = $this->form->checkInput();

		if ($this->form->getInput('usr_registration_code'))
		{
			$codeObj = new ilRegistrationCode($this->form->getInput('usr_registration_code'));
			if (!$codeObj->isUsable())
			{
				$codeItem = $this->form->getItemByPostVar('usr_registration_code');
				$codeItem->setAlert($this->lng->txt('registration_code_not_valid'));
				$valid = false;

				ilUtil::sendFailure($this->lng->txt('form_input_not_valid'));
			}
			else
			{
				$_SESSION['ilAccountRegistrationGUI:code'] = $codeObj->code;
			}
		}

		if (!$valid)
		{
			$this->displayCodeForm();
		}
		else
		{
			$this->ctrl->redirect($this, 'displayForm');
		}
	}
// fau.


	/**
	 * 
	 */
	public function displayForm()
	{
		/**
		 * @var $lng ilLanguage
		 */
		global $lng;

		ilStartUpGUI::initStartUpTemplate(array('tpl.usr_registration.html', 'Services/Registration'), true);

// fau: regCodes - show customized title and headline of registration code
		if (isset($this->codeObj) && !empty($this->codeObj->title))
		{
			$this->tpl->setVariable('TXT_PAGEHEADLINE', $this->codeObj->title);
		}
		else
		{
			$this->tpl->setVariable('TXT_PAGEHEADLINE', $this->lng->txt('registration'));
		}

		if (isset($this->codeObj) && !empty($this->codeObj->description))
		{
			$this->tpl->setVariable('DESCRIPTION', $this->codeObj->description);
		}
// fau.

		if(!$this->form)
		{
			$this->__initForm();
		}
		$this->tpl->setVariable('FORM', $this->form->getHTML());
	}
	
	protected function __initForm()
	{
		global $lng, $ilUser;
		
		// needed for multi-text-fields (interests)
		include_once 'Services/jQuery/classes/class.iljQueryUtil.php';
		iljQueryUtil::initjQuery();
		
		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$this->form = new ilPropertyFormGUI();
		$this->form->setFormAction($this->ctrl->getFormAction($this));
		
		
// fau: regCodes - don't show code field in the registration form
// fau.

		// user defined fields

		$user_defined_data = $ilUser->getUserDefinedData();

		include_once './Services/User/classes/class.ilUserDefinedFields.php';
		$user_defined_fields =& ilUserDefinedFields::_getInstance();
		$custom_fields = array();
		foreach($user_defined_fields->getRegistrationDefinitions() as $field_id => $definition)
		{
			if($definition['field_type'] == UDF_TYPE_TEXT)
			{
				$custom_fields["udf_".$definition['field_id']] =
					new ilTextInputGUI($definition['field_name'], "udf_".$definition['field_id']);
				$custom_fields["udf_".$definition['field_id']]->setValue($user_defined_data["f_".$field_id]);
				$custom_fields["udf_".$definition['field_id']]->setMaxLength(255);
				$custom_fields["udf_".$definition['field_id']]->setSize(40);
			}
			else if($definition['field_type'] == UDF_TYPE_WYSIWYG)
			{
				$custom_fields["udf_".$definition['field_id']] =
					new ilTextAreaInputGUI($definition['field_name'], "udf_".$definition['field_id']);
				$custom_fields["udf_".$definition['field_id']]->setValue($user_defined_data["f_".$field_id]);
				$custom_fields["udf_".$definition['field_id']]->setUseRte(true);
			}
			else
			{
				$custom_fields["udf_".$definition['field_id']] =
					new ilSelectInputGUI($definition['field_name'], "udf_".$definition['field_id']);
				$custom_fields["udf_".$definition['field_id']]->setValue($user_defined_data["f_".$field_id]);
				$custom_fields["udf_".$definition['field_id']]->setOptions(
					$user_defined_fields->fieldValuesToSelectArray($definition['field_values']));
			}
			if($definition['required'])
			{
				$custom_fields["udf_".$definition['field_id']]->setRequired(true);
			}

			if($definition['field_type'] == UDF_TYPE_SELECT && !$user_defined_data["f_".$field_id])
			{
				$options = array(""=>$lng->txt("please_select")) + $custom_fields["udf_".$definition['field_id']]->getOptions();
				$custom_fields["udf_".$definition['field_id']]->setOptions($options);
			}
		}

		// standard fields
		include_once("./Services/User/classes/class.ilUserProfile.php");
		$up = new ilUserProfile();
		$up->setMode(ilUserProfile::MODE_REGISTRATION);
		$up->skipGroup("preferences");
		
		$up->setAjaxCallback(
			$this->ctrl->getLinkTarget($this, 'doProfileAutoComplete', '', true)
		);

		$lng->loadLanguageModule("user");

		// add fields to form
		$up->addStandardFieldsToForm($this->form, NULL, $custom_fields);
		unset($custom_fields);
		
		
		// set language selection to current display language
		$flang = $this->form->getItemByPostVar("usr_language");
		if($flang)
		{
			$flang->setValue($lng->getLangKey());	
		}
		
		// add information to role selection (if not hidden)
		if($this->code_enabled)
		{
			$role = $this->form->getItemByPostVar("usr_roles");
			if($role && $role->getType() == "select")
			{
				$role->setInfo($lng->txt("registration_code_role_info"));
			}
		}
		
		// #11407
		$domains = array();
		foreach($this->registration_settings->getAllowedDomains() as $item)
		{
			if(trim($item))
			{
				$domains[] = $item;
			}
		}			
		if(sizeof($domains))
		{											
			$mail_obj = $this->form->getItemByPostVar('usr_email');
			$mail_obj->setInfo(sprintf($lng->txt("reg_email_domains"),
				implode(", ", $domains))."<br />".
				($this->code_enabled ? $lng->txt("reg_email_domains_code") : ""));
		}
		
		// #14272
// fau: regCodes - check for registration type and code to set email required
		if($this->registration_settings->activationEnabled())
// fau.
		{
			$mail_obj = $this->form->getItemByPostVar('usr_email');
			if($mail_obj) // #16087
			{
				$mail_obj->setRequired(true);
			}
		}

		require_once 'Services/TermsOfService/classes/class.ilTermsOfServiceSignableDocumentFactory.php';
		$document = ilTermsOfServiceSignableDocumentFactory::getByLanguageObject($lng);
		if(ilTermsOfServiceHelper::isEnabled() && $document->exists())
		{
			$field = new ilFormSectionHeaderGUI();
			$field->setTitle($lng->txt('usr_agreement'));
			$this->form->addItem($field);

			$field = new ilCustomInputGUI();
			$field->setHTML('<div id="agreement">' . $document->getContent() . '</div>');
			$this->form->addItem($field);

			$field = new ilCheckboxInputGUI($lng->txt('accept_usr_agreement'), 'accept_terms_of_service');
			$field->setRequired(true);
			$field->setValue(1);
			$this->form->addItem($field);
		}

		require_once 'Services/Captcha/classes/class.ilCaptchaUtil.php';
// fau: regCodes - use code setting for captcha display
		if((isset($this->codeObj) && $this->codeObj->captcha_required) || ilCaptchaUtil::isActiveForRegistration())
// fau.
		{
			require_once 'Services/Captcha/classes/class.ilCaptchaInputGUI.php';
			$captcha = new ilCaptchaInputGUI($lng->txt("captcha_code"), 'captcha_code');
			$captcha->setRequired(true);
			$this->form->addItem($captcha);
		}

		$this->form->addCommandButton("saveForm", $lng->txt("register"));
		// fim: [layout] add cancel button
		$this->form->addCommandButton("cancelForm", $lng->txt("cancel"));
		// fim.
	}

	/**
	 * fim: [layout] action for cancel button
	 */
	public function cancelForm()
	{
		ilUtil::redirect("index.php");
	}
	// fim.



	public function saveForm()
	{
		global $lng, $ilSetting, $rbacreview;

		$this->__initForm();
		$form_valid = $this->form->checkInput();
		
		require_once 'Services/User/classes/class.ilObjUser.php';

		
		// custom validation
		$valid_code = $valid_role = false;
		 		
		// code
		if($this->code_enabled)
		{
// fau: regCodes - take the code object instead of form input
			// could be optional
			if($this->codeObj)
			{				
				// code has been checked in executeCommand
				$valid_code = true;

				// get role from code, check if (still) valid
				$role_id = $this->codeObj->global_role;
				if($role_id && $rbacreview->isGlobalRole($role_id))
				{
					$valid_role = $role_id;
				}
			}
		}
// fau.

		// valid codes override email domain check
		if(!$valid_code)
		{
			// validate email against restricted domains
			$email = $this->form->getInput("usr_email");
			if($email)
			{
				// #10366
				$domains = array();
				foreach($this->registration_settings->getAllowedDomains() as $item)
				{
					if(trim($item))
					{
						$domains[] = $item;
					}
				}			
				if(sizeof($domains))
				{								
					$mail_valid = false;
					foreach($domains as $domain)
					{
						$domain = str_replace("*", "~~~", $domain);
						$domain = preg_quote($domain);
						$domain = str_replace("~~~", ".+", $domain);					
						if(preg_match("/^".$domain."$/", $email, $hit))
						{
							$mail_valid = true;
							break;
						}
					}
					if(!$mail_valid)
					{
						$mail_obj = $this->form->getItemByPostVar('usr_email');
						$mail_obj->setAlert(sprintf($lng->txt("reg_email_domains"),
							implode(", ", $domains)));
						$form_valid = false;
					}
				}
			}
		}

		$error_lng_var = '';
		if(
			!$this->registration_settings->passwordGenerationEnabled() &&
			!ilUtil::isPasswordValidForUserContext($this->form->getInput('usr_password'), $this->form->getInput('username'), $error_lng_var)
		)
		{
			$passwd_obj = $this->form->getItemByPostVar('usr_password');
			$passwd_obj->setAlert($lng->txt($error_lng_var));
			$form_valid = false;
		}

		if(ilTermsOfServiceHelper::isEnabled() && !$this->form->getInput('accept_terms_of_service'))
		{
			$agr_obj = $this->form->getItemByPostVar('accept_terms_of_service');
			if($agr_obj)
			{
				$agr_obj->setAlert($lng->txt('force_accept_usr_agreement'));
			}
			else
			{
				ilUtil::sendFailure($lng->txt('force_accept_usr_agreement'));
			}
			$form_valid = false;
		}

		// no need if role is attached to code
		if(!$valid_role)
		{
			// manual selection	
			if ($this->registration_settings->roleSelectionEnabled())
			{
				include_once "./Services/AccessControl/classes/class.ilObjRole.php";
				$selected_role = $this->form->getInput("usr_roles");
				if ($selected_role && ilObjRole::_lookupAllowRegister($selected_role))
				{
					$valid_role = (int)$selected_role;
				}
			}
			// assign by email
			else
			{				
				include_once 'Services/Registration/classes/class.ilRegistrationEmailRoleAssignments.php';
				$registration_role_assignments = new ilRegistrationRoleAssignments();
				$valid_role = (int)$registration_role_assignments->getRoleByEmail($this->form->getInput("usr_email"));
			}
		}

		// no valid role could be determined
	    if (!$valid_role)
		{
			ilUtil::sendInfo($lng->txt("registration_no_valid_role"));
			$form_valid = false;
		}			

		// validate username
		$login_obj = $this->form->getItemByPostVar('username');
		$login = $this->form->getInput("username");

// fau: regCodes - use login generation types
		if ($this->registration_settings->loginGenerationType() != ilRegistrationSettings::LOGIN_GEN_MANUAL)
		{
			$login = $this->__generateLogin();
			$_POST['username'] = $login;
			$this->form->getItemByPostVar('username')->setValue($login);
		}
		elseif (!ilUtil::isLogin($login))
// fau.
		{
			$login_obj->setAlert($lng->txt("login_invalid"));
			$form_valid = false;
		}
		else if (ilObjUser::_loginExists($login))
		{
			$login_obj->setAlert($lng->txt("login_exists"));
			$form_valid = false;
		}
		else if ((int)$ilSetting->get('allow_change_loginname') &&
			(int)$ilSetting->get('reuse_of_loginnames') == 0 &&
			ilObjUser::_doesLoginnameExistInHistory($login))
		{
			$login_obj->setAlert($lng->txt('login_exists'));
			$form_valid = false;
		}

		if(!$form_valid)
		{
			ilUtil::sendFailure($lng->txt('form_input_not_valid'));
		}
		else
		{
			$password = $this->__createUser($valid_role);
			$this->__distributeMails($password);
			$this->login($password);
			return true;
		}		

		$this->form->setValuesByPost();
		$this->displayForm();
		return false;
	}
	
	protected function __createUser($a_role)
	{
		/**
		 * @var $ilSetting ilSetting
		 * @var $rbacadmin ilRbacAdmin
		 * @var $lng       ilLanguage
		 */
		global $ilSetting, $rbacadmin, $lng;
		
		
		// something went wrong with the form validation
		if(!$a_role)
		{			
			global $ilias;
			$ilias->raiseError("Invalid role selection in registration".
				", IP: ".$_SERVER["REMOTE_ADDR"], $ilias->error_obj->FATAL);
		}
		

		$this->userObj = new ilObjUser();
		
		include_once("./Services/User/classes/class.ilUserProfile.php");
		$up = new ilUserProfile();
		$up->setMode(ilUserProfile::MODE_REGISTRATION);

		$map = array();
		$up->skipGroup("preferences");
		$up->skipGroup("settings");
		$up->skipField("password");
		$up->skipField("birthday");
		$up->skipField("upload");
		foreach ($up->getStandardFields() as $k => $v)
		{
			if($v["method"])
			{
				$method = "set".substr($v["method"], 3);
				if(method_exists($this->userObj, $method))
				{					
					if ($k != "username")
					{
						$k = "usr_".$k;
					}
					$field_obj = $this->form->getItemByPostVar($k);
					if($field_obj)
					{
					
						$this->userObj->$method($this->form->getInput($k));
					}
				}
			}
		}

		$this->userObj->setFullName();

		$birthday_obj = $this->form->getItemByPostVar("usr_birthday");
		if($birthday_obj)
		{
			$birthday = $this->form->getInput("usr_birthday");
			$this->userObj->setBirthday($birthday);
		}

		$this->userObj->setTitle($this->userObj->getFullname());
		$this->userObj->setDescription($this->userObj->getEmail());

// fau: regCodes: respect the password generation type
		if ($this->registration_settings->passwordGenerationType() == ilRegistrationSettings::PW_GEN_AUTO)
		{
			$password = ilUtil::generatePasswords(1);
			$password = $password[0];
		}
		elseif ($this->registration_settings->passwordGenerationType() == ilRegistrationSettings::PW_GEN_LOGIN)
        {
            $password = $this->userObj->getLogin();
        }
// fau.
		else
		{
			$password = $this->form->getInput("usr_password");
		}
		$this->userObj->setPasswd($password);
		
		
		// Set user defined data
		include_once './Services/User/classes/class.ilUserDefinedFields.php';
		$user_defined_fields =& ilUserDefinedFields::_getInstance();
		$defs = $user_defined_fields->getRegistrationDefinitions();
		$udf = array();
		foreach ($_POST as $k => $v)
		{
			if (substr($k, 0, 4) == "udf_")
			{
				$f = substr($k, 4);
				$udf[$f] = $v;
			}
		}
		$this->userObj->setUserDefinedData($udf);

		$this->userObj->setTimeLimitOwner(7);
		
		
		$access_limit = null;

		$this->code_was_used = false;
		if($this->code_enabled)
		{					 
			$code_local_roles = $code_has_access_limit = null;

// fau: regCodes - take the code object instead of form input
			if(isset($this->codeObj))
			{
				// set code to used				
				$this->codeObj->addUsage();
				$this->code_was_used = true;
				
				// handle code attached local role(s) and access limitation
				$code_local_roles = $this->codeObj->local_roles;

				if ($this->codeObj->limit_type)
				{
					// see below					
					$code_has_access_limit = true;
					
					switch($this->codeObj->limit_type)
					{
						case "absolute":					
							$abs = date_parse($this->codeObj->limit_date->get(IL_CAL_DATE));
							$access_limit = mktime(23, 59, 59, $abs['month'], $abs['day'], $abs['year']);
							break;
						
						case "relative":					
							$rel = $this->codeObj->limit_duration;
							$access_limit = $rel["d"] * 86400 + $rel["m"] * 2592000 + 
								$rel["y"] * 31536000 + time();		
							break;
					}
				}
			}
		}
// fau.

		// code access limitation will override any other access limitation setting
		if (!($this->code_was_used && $code_has_access_limit) &&
			$this->registration_settings->getAccessLimitation())
		{
			include_once 'Services/Registration/classes/class.ilRegistrationRoleAccessLimitations.php';
			$access_limitations_obj = new ilRegistrationRoleAccessLimitations();
			switch($access_limitations_obj->getMode($a_role))
			{
				case 'absolute':			
					$access_limit = $access_limitations_obj->getAbsolute($a_role);
					break;
				
				case 'relative':			
					$rel_d = (int) $access_limitations_obj->getRelative($a_role,'d');
					$rel_m = (int) $access_limitations_obj->getRelative($a_role,'m');
					$rel_y = (int) $access_limitations_obj->getRelative($a_role,'y');
					$access_limit = $rel_d * 86400 + $rel_m * 2592000 + $rel_y * 31536000 + time();		
					break;
			}
		}
		
		if($access_limit)
		{
			$this->userObj->setTimeLimitUnlimited(0);
			$this->userObj->setTimeLimitUntil($access_limit);
		}
		else
		{
			$this->userObj->setTimeLimitUnlimited(1);
			$this->userObj->setTimeLimitUntil(time());
		}

		$this->userObj->setTimeLimitFrom(time());

		include_once './Services/User/classes/class.ilUserCreationContext.php';
		ilUserCreationContext::getInstance()->addContext(ilUserCreationContext::CONTEXT_REGISTRATION);

		$this->userObj->create();

// fau: regCodes - 	check with code for activation
		if($this->registration_settings->activationEnabled())
		{
			// account has to be activated by email
			$this->userObj->setActive(0,0);
		}
		elseif($this->registration_settings->getRegistrationType() == IL_REG_DIRECT ||
			isset($this->codeObj))
		{
			// account can directly be activated
			$this->userObj->setActive(1,0);
		}
		else
		{
			// account has to e approved by admin
			$this->userObj->setActive(0,0);
		}
// fau.
		$this->userObj->updateOwner();

		// set a timestamp for last_password_change
		// this ts is needed by ilSecuritySettings
		$this->userObj->setLastPasswordChangeTS( time() );
		
		$this->userObj->setIsSelfRegistered(true);

		//insert user data in table user_data
		$this->userObj->saveAsNew();

		require_once 'Services/TermsOfService/classes/class.ilTermsOfServiceSignableDocumentFactory.php';
		ilTermsOfServiceHelper::trackAcceptance($this->userObj, ilTermsOfServiceSignableDocumentFactory::getByLanguageObject($lng));

		// setup user preferences
		$this->userObj->setLanguage($this->form->getInput('usr_language'));
		$hits_per_page = $ilSetting->get("hits_per_page");
		if ($hits_per_page < 10)
		{
			$hits_per_page = 10;
		}
		$this->userObj->setPref("hits_per_page", $hits_per_page);
		if(strlen($_GET['target']) > 0)
		{
			$this->userObj->setPref('reg_target', ilUtil::stripSlashes($_GET['target']));
		}
		/*$show_online = $ilSetting->get("show_users_online");
		if ($show_online == "")
		{
			$show_online = "y";
		}
		$this->userObj->setPref("show_users_online", $show_online);*/
		$this->userObj->setPref('bs_allow_to_contact_me',  $ilSetting->get('bs_allow_to_contact_me', 'n'));
		$this->userObj->setPref('chat_osc_accept_msg', $ilSetting->get('chat_osc_accept_msg', 'n'));

// fau: regCodes - save used registration code in preferences
		if ($this->codeObj)
		{
			$this->userObj->setPref('registration_code', $this->codeObj->code);
		}
// fau.
		$this->userObj->writePrefs();

		
		$rbacadmin->assignUser((int)$a_role, $this->userObj->getId());
		
		// local roles from code
		if($this->code_was_used && is_array($code_local_roles))
		{
			foreach(array_unique($code_local_roles) as $local_role_obj_id)
			{
				// is given role (still) valid?
				if(ilObject::_lookupType($local_role_obj_id) == "role")
				{
					$rbacadmin->assignUser($local_role_obj_id, $this->userObj->getId());

					// patch to remove for 45 due to mantis 21953
					$role_obj = $GLOBALS['rbacreview']->getObjectOfRole($local_role_obj_id);
					switch(ilObject::_lookupType($role_obj))
					{
						case 'crs':
						case 'grp':
							$role_refs = ilObject::_getAllReferences($role_obj);
							$role_ref = end($role_refs);
							ilObjUser::_addDesktopItem($this->userObj->getId(),$role_ref,ilObject::_lookupType($role_obj));
							break;
					}
				}
			}
		}

		return $password;
	}
// fau: regCodes - new function __generateLogin
	protected function __generateLogin()
	{
		$base_login = '';

		switch ($this->registration_settings->loginGenerationType())
		{
			case ilRegistrationSettings::LOGIN_GEN_MANUAL:
				$base_login =  $this->form->getInput('username');
				break;

			case ilRegistrationSettings::LOGIN_GEN_FIRST_LASTNAME:
				$base_login = ilUtil::getASCIIFilename(strtolower($this->form->getInput('usr_firstname')))
					. ilUtil::getASCIIFilename(strtolower($this->form->getInput('usr_lastname')));
				break;

			case ilRegistrationSettings::LOGIN_GEN_GUEST_LISTENER:
				$base_login = 'gh'
					. (substr(ilStudyAccess::_getRunningSemesterString(), 4, 1) == '1' ? 's' : 'w')
					. substr(ilStudyAccess::_getRunningSemesterString(), 2, 2)
					. substr(ilUtil::getASCIIFilename(strtolower($this->form->getInput('usr_firstname'))), 0, 2)
					. substr(ilUtil::getASCIIFilename(strtolower($this->form->getInput('usr_lastname'))), 0, 4);
				break;

			case ilRegistrationSettings::LOGIN_GEN_GUEST_SELFREG:
				$base_login = 'gsr'. rand(10000,99999);
				break;
		}

		// append a number to get an unused login
		$login = $base_login;
		$i = 0;
		while (ilObjUser::_loginExists($login))
		{
			$i++;
			$login = $base_login . $i;
		}

		return $login;
	}
// fau.



	protected function __distributeMails($password)
	{
		global $ilSetting;

		include_once './Services/Language/classes/class.ilLanguage.php';
		include_once './Services/User/classes/class.ilObjUser.php';
		include_once "Services/Mail/classes/class.ilFormatMail.php";
		include_once './Services/Registration/classes/class.ilRegistrationMailNotification.php';

		// Always send mail to approvers
		if($this->registration_settings->getRegistrationType() == IL_REG_APPROVE && !$this->code_was_used)
		{
			$mail = new ilRegistrationMailNotification();
			$mail->setType(ilRegistrationMailNotification::TYPE_NOTIFICATION_CONFIRMATION);
			$mail->setRecipients($this->registration_settings->getApproveRecipients());
			$mail->setAdditionalInformation(array('usr' => $this->userObj));
			$mail->send();
		}
		else
		{
			$mail = new ilRegistrationMailNotification();
			$mail->setType(ilRegistrationMailNotification::TYPE_NOTIFICATION_APPROVERS);
			$mail->setRecipients($this->registration_settings->getApproveRecipients());
			$mail->setAdditionalInformation(array('usr' => $this->userObj));
			$mail->send();
			
		}

		// Send mail to new user
		
		// Registration with confirmation link ist enabled
// fau: regCodes - extended check for enabled activation (code or gloval)
		if($this->registration_settings->activationEnabled())
// fau.
		{
			include_once './Services/Registration/classes/class.ilRegistrationMimeMailNotification.php';

			$mail = new ilRegistrationMimeMailNotification();
			$mail->setType(ilRegistrationMimeMailNotification::TYPE_NOTIFICATION_ACTIVATION);
			$mail->setRecipients(array($this->userObj));
			$mail->setAdditionalInformation(
				array(
					 'usr'           => $this->userObj,
					 'hash_lifetime' => $this->registration_settings->getRegistrationHashLifetime()
				)
			);
			$mail->send();
		} else {
			$accountMail = new \ilAccountRegistrationMail($this->registration_settings, $this->lng);
			$accountMail->withDirectRegistrationMode()->send($this->userObj, $password, $this->code_was_used);
		}
	}

	/**
	 * @param string $password
	 */
	public function login($password)
	{
		/**
		 * @var $lng ilLanguage
		 */
		global $lng;

		ilStartUpGUI::initStartUpTemplate(array('tpl.usr_registered.html', 'Services/Registration'), false);
		$this->tpl->setVariable('TXT_PAGEHEADLINE', $this->lng->txt('registration'));

		$this->tpl->setVariable("TXT_WELCOME", $lng->txt("welcome") . ", " . $this->userObj->getTitle() . "!");
		if(
			(
				$this->registration_settings->getRegistrationType() == IL_REG_DIRECT ||
				$this->registration_settings->getRegistrationType() == IL_REG_CODES ||
				$this->code_was_used
			) &&
			!$this->registration_settings->passwordGenerationEnabled()
		)
		{
			// store authenticated user in session
			ilSession::set('registered_user', $this->userObj->getId());

			$this->tpl->setCurrentBlock('activation');
// fau: regCodes - merge the username  and password in the welcome text and set formaction to login script
            if ($this->registration_settings->passwordGenerationType() == ilRegistrationSettings::PW_GEN_LOGIN)
            {
                $this->tpl->setVariable("TXT_REGISTERED", sprintf($this->lng->txt("txt_registered_pw_is_login"), $this->userObj->getLogin()));
            }
            else
            {
                $this->tpl->setVariable("TXT_REGISTERED", sprintf($this->lng->txt("txt_registered"), $this->userObj->getLogin(), $password));
            }
			$this->tpl->setVariable('USERNAME', $this->userObj->getLogin());
			$this->tpl->setVariable('PASSWORD', $password);

			$this->ctrl->setParameterByClass('ilStartUpGUI', 'login_target', ilUtil::stripSlashes($_GET['target']));
			$action = $this->ctrl->getFormActionByClass(['ilStartupGUI', 'ilStartUpGUI']);
// fau.
			$this->tpl->setVariable('FORMACTION', $action);

// fau: samlAuth - changed language var for local login
			$this->tpl->setVariable('TXT_LOGIN', $lng->txt('local_login_to_ilias'));
// fau.
			$this->tpl->parseCurrentBlock();
		}
		else if($this->registration_settings->getRegistrationType() == IL_REG_APPROVE)
		{
			$this->tpl->setVariable('TXT_REGISTERED', $lng->txt('txt_submitted'));
		}
// fau: regCodes show info about confirmation mail also for code - don't redirect automatically
		else if($this->registration_settings->activationEnabled())
		{
			$login_url = './login.php?cmd=force_login&lang=' . $this->userObj->getLanguage();
			$this->tpl->setVariable('TXT_REGISTERED', sprintf($lng->txt('reg_confirmation_link_successful'), $login_url));
		}
// fau.
		else
		{
			$this->tpl->setVariable('TXT_REGISTERED', $this->lng->txt('txt_registered_passw_gen'));
		}
	}

	/**
	 * Do Login
	 * @todo refactor this method should be renamed, but i don't wanted to make changed in
	 * tpl.usr_registered.html in stable release.
	 */
	protected function showLogin()
	{
		/**
		 * @var ilAuthSession
		 */
		$auth_session = $GLOBALS['DIC']['ilAuthSession'];
		$auth_session->setAuthenticated(
			true,
			ilSession::get('registered_user')
		);
		ilInitialisation::initUserAccount();
		return ilInitialisation::redirectToStartingPage();
	}

	protected function doProfileAutoComplete()
	{	
		$field_id = (string)$_REQUEST["f"];
		$term = (string)$_REQUEST["term"];
				
		include_once "Services/User/classes/class.ilPublicUserProfileGUI.php";
		$result = ilPublicUserProfileGUI::getAutocompleteResult($field_id, $term);		
		if(sizeof($result))
		{
			include_once 'Services/JSON/classes/class.ilJsonUtil.php';
			echo ilJsonUtil::encode($result);
		}
		
		exit();		
	}
}
