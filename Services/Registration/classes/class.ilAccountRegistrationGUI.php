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

    /** @var \ilTermsOfServiceDocumentEvaluation */
    protected $termsOfServiceEvaluation;

    /**
     * @var ilRecommendedContentManager
     */
    protected $recommended_content_manager;

    // fau: regCodes - class variables

    /** @var ilRegistrationCode|null  */
    protected $codeObj = null;

    /** @var  ilPropertyFormGUI $form */
    protected $form;
    // fau.

    public function __construct()
    {
        global $DIC;

        $ilCtrl = $DIC->ctrl();
        $tpl = $DIC['tpl'];
        $lng = $DIC->language();

        $this->tpl =&$tpl;

        $this->ctrl =&$ilCtrl;
        $this->ctrl->saveParameter($this, 'lang');

        $this->lng =&$lng;
        $this->lng->loadLanguageModule('registration');

        // fau: regCodes - initialize an already entered code and save in settings
        $this->registration_settings = ilRegistrationSettings::getInstance();
        
        $this->code_enabled = ($this->registration_settings->registrationCodeRequired() ||
            $this->registration_settings->getAllowCodes());

        $this->termsOfServiceEvaluation = $DIC['tos.document.evaluator'];
        $this->recommended_content_manager = new ilRecommendedContentManager();

        if ($this->code_enabled) {
            if (!empty($_GET['code'])) {
                $this->codeObj = new ilRegistrationCode($_GET['code']);
                if ($this->codeObj->isUsable()) {
                    $_SESSION['ilAccountRegistrationGUI:code'] = $this->codeObj->code;
                }
            } elseif ($_SESSION['ilAccountRegistrationGUI:code']) {
                $this->codeObj = new ilRegistrationCode(($_SESSION['ilAccountRegistrationGUI:code']));
            }

            if (isset($this->codeObj)) {
                $this->registration_settings->setCodeObject($this->codeObj);
            }
        }
        // fau.
    }

    public function executeCommand()
    {
        global $DIC;

        if ($this->registration_settings->getRegistrationType() == IL_REG_DISABLED) {
            $ilErr = $DIC['ilErr'];
            $ilErr->raiseError($this->lng->txt('reg_disabled'), $ilErr->FATAL);
        }

        $cmd = $this->ctrl->getCmd();
        switch ($cmd) {
            case 'saveForm':
            // fau: regCodes - add commands for code form
            case 'saveCodeForm':
            case 'cancelForm':
            // fau.
                $tpl = $this->$cmd();
                break;
            default:
                // fau: regCodes - determine default command based on code entry
                if (!$this->code_enabled) {
                    $tpl = $this->displayForm();
                } elseif (!isset($this->codeObj)) {
                    $tpl = $this->displayCodeForm();
                } elseif (!$this->codeObj->isUsable()) {
                    $tpl = $this->displayCodeForm();
                } else {
                    $tpl = $this->displayForm();
                }
            // fau.
        }

        $gtpl = $DIC['tpl'];
        $gtpl->setPermanentLink('usr', null, 'registration');
        ilStartUpGUI::printToGlobalTemplate($tpl);
    }

    // fau: regCodes - handle separate form for code entry
    public function displayCodeForm()
    {
        if (!$this->form) {
            $this->__initCodeForm();
        }

        $tpl = ilStartUpGUI::initStartUpTemplate(array('tpl.usr_registration.html', 'Services/Registration'), true);

        ilStartUpGUI::initStartUpTemplate(array('tpl.usr_registration.html', 'Services/Registration'), true);
        $tpl->setVariable('TXT_PAGEHEADLINE', $this->lng->txt('registration'));
        if ((bool) $this->registration_settings->registrationCodeRequired()) {
            $tpl->setVariable('DESCRIPTION', $this->lng->txt("registration_code_required_info"));
        } else {
            $tpl->setVariable('DESCRIPTION', $this->lng->txt("registration_code_optional_info"));
        }

        $tpl->setVariable('FORM', $this->form->getHTML());

        return $tpl;
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
        $code->setRequired((bool) $this->registration_settings->registrationCodeRequired());
        $this->form->addItem($code);

        $this->form->addCommandButton("saveCodeForm", $this->lng->txt("register"));
        $this->form->addCommandButton("cancelForm", $this->lng->txt("cancel"));
    }


    public function saveCodeForm()
    {
        $this->__initCodeForm();

        $valid = $this->form->checkInput();

        if ($this->form->getInput('usr_registration_code')) {
            $codeObj = new ilRegistrationCode($this->form->getInput('usr_registration_code'));
            if (!$codeObj->isUsable()) {
                $codeItem = $this->form->getItemByPostVar('usr_registration_code');
                $codeItem->setAlert($this->lng->txt('registration_code_not_valid'));
                $valid = false;

                ilUtil::sendFailure($this->lng->txt('form_input_not_valid'));
            } else {
                $_SESSION['ilAccountRegistrationGUI:code'] = $codeObj->code;
            }
        }

        if (!$valid) {
            return $this->displayCodeForm();
        } else {
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

        $tpl = ilStartUpGUI::initStartUpTemplate(array('tpl.usr_registration.html', 'Services/Registration'), true);

        // fau: regCodes - show customized title and headline of registration code
        if (isset($this->codeObj) && !empty($this->codeObj->title)) {
            $tpl->setVariable('TXT_PAGEHEADLINE', $this->codeObj->title);
        } else {
            $tpl->setVariable('TXT_PAGEHEADLINE', $this->lng->txt('registration'));
        }

        if (isset($this->codeObj) && !empty($this->codeObj->description)) {
            $tpl->setVariable('DESCRIPTION', $this->codeObj->description);
        }
        // fau.

        if (!$this->form) {
            $this->__initForm();
        }
        $tpl->setVariable('FORM', $this->form->getHTML());
        return $tpl;
    }

    protected function __initForm()
    {
        global $DIC;

        $ilUser = $DIC->user();

        $ilUser->setLanguage($this->lng->getLangKey());
        $ilUser->setId(ANONYMOUS_USER_ID);

        // needed for multi-text-fields (interests)
        iljQueryUtil::initjQuery();

        $this->form = new ilPropertyFormGUI();
        $this->form->setFormAction($this->ctrl->getFormAction($this));

        
        // fau: regCodes - don't show code field in the registration form
        // fau.

        // user defined fields
        $user_defined_data = $ilUser->getUserDefinedData();

        $user_defined_fields = ilUserDefinedFields::_getInstance();
        $custom_fields = array();
        
        foreach ($user_defined_fields->getRegistrationDefinitions() as $field_id => $definition) {
            $fprop = ilCustomUserFieldsHelper::getInstance()->getFormPropertyForDefinition(
                $definition,
                true,
                $user_defined_data['f_' . $field_id]
            );
            if ($fprop instanceof ilFormPropertyGUI) {
                $custom_fields['udf_' . $definition['field_id']] = $fprop;
            }
        }
        
        // standard fields
        $up = new ilUserProfile();
        $up->setMode(ilUserProfile::MODE_REGISTRATION);
        $up->skipGroup("preferences");
        
        $up->setAjaxCallback(
            $this->ctrl->getLinkTarget($this, 'doProfileAutoComplete', '', true)
        );

        $this->lng->loadLanguageModule("user");

        // add fields to form
        $up->addStandardFieldsToForm($this->form, null, $custom_fields);
        unset($custom_fields);
        
        
        // set language selection to current display language
        $flang = $this->form->getItemByPostVar("usr_language");
        if ($flang) {
            $flang->setValue($this->lng->getLangKey());
        }
        
        // add information to role selection (if not hidden)
        if ($this->code_enabled) {
            $role = $this->form->getItemByPostVar("usr_roles");
            if ($role && $role->getType() == "select") {
                $role->setInfo($this->lng->txt("registration_code_role_info"));
            }
        }
        
        // #11407
        $domains = array();
        foreach ($this->registration_settings->getAllowedDomains() as $item) {
            if (trim($item)) {
                $domains[] = $item;
            }
        }
        if (sizeof($domains)) {
            $mail_obj = $this->form->getItemByPostVar('usr_email');
            $mail_obj->setInfo(sprintf(
                $this->lng->txt("reg_email_domains"),
                implode(", ", $domains)
            ) . "<br />" .
                ($this->code_enabled ? $this->lng->txt("reg_email_domains_code") : ""));
        }
        
        // #14272
        // fau: regCodes - check for registration type and code to set email required
        if ($this->registration_settings->activationEnabled()) {
            // fau.
            $mail_obj = $this->form->getItemByPostVar('usr_email');
            if ($mail_obj) { // #16087
                $mail_obj->setRequired(true);
            }
        }

        if (\ilTermsOfServiceHelper::isEnabled() && $this->termsOfServiceEvaluation->hasDocument()) {
            $document = $this->termsOfServiceEvaluation->document();

            $field = new ilFormSectionHeaderGUI();
            $field->setTitle($this->lng->txt('usr_agreement'));
            $this->form->addItem($field);

            $field = new ilCustomInputGUI();
            $field->setHTML('<div id="agreement">' . $document->content() . '</div>');
            $this->form->addItem($field);

            $field = new ilCheckboxInputGUI($this->lng->txt('accept_usr_agreement'), 'accept_terms_of_service');
            $field->setRequired(true);
            $field->setValue(1);
            $this->form->addItem($field);
        }


        // fau: regCodes - use code setting for captcha display
        if ((isset($this->codeObj) && $this->codeObj->captcha_required) || ilCaptchaUtil::isActiveForRegistration()) {
            // fau.
            $captcha = new ilCaptchaInputGUI($this->lng->txt("captcha_code"), 'captcha_code');
            $captcha->setRequired(true);
            $this->form->addItem($captcha);
        }

        $this->form->addCommandButton("saveForm", $this->lng->txt("register"));
        // fau: regCodes - add cancel button
        $this->form->addCommandButton("cancelForm", $this->lng->txt("cancel"));
        // fau.
    }

    // fau: regCodes - new function cancelForm()
    /**
     * Cancel the account registration and unset the registration code
     */
    public function cancelForm()
    {
        global $DIC;
        unset($_SESSION['ilAccountRegistrationGUI:code']);
        $DIC->ctrl()->redirectToURL('index.php');
    }
    // fau.



    public function saveForm()
    {
        global $DIC;

        $ilSetting = $DIC->settings();
        $rbacreview = $DIC->rbac()->review();

        $this->__initForm();
        $form_valid = $this->form->checkInput();



        // custom validation
        $valid_code = $valid_role = false;
                
        // code
        if ($this->code_enabled) {
            // fau: regCodes - take the code object instead of form input
            // could be optional
            if ($this->codeObj) {
                // code has been checked in executeCommand
                $valid_code = true;

                // get role from code, check if (still) valid
                $role_id = $this->codeObj->global_role;
                if ($role_id && $rbacreview->isGlobalRole($role_id)) {
                    $valid_role = $role_id;
                }
            }
        }
        // fau.

        // valid codes override email domain check
        if (!$valid_code) {
            // validate email against restricted domains
            $email = $this->form->getInput("usr_email");
            if ($email) {
                // #10366
                $domains = array();
                foreach ($this->registration_settings->getAllowedDomains() as $item) {
                    if (trim($item)) {
                        $domains[] = $item;
                    }
                }
                if (sizeof($domains)) {
                    $mail_valid = false;
                    foreach ($domains as $domain) {
                        $domain = str_replace("*", "~~~", $domain);
                        $domain = preg_quote($domain);
                        $domain = str_replace("~~~", ".+", $domain);
                        if (preg_match("/^" . $domain . "$/", $email, $hit)) {
                            $mail_valid = true;
                            break;
                        }
                    }
                    if (!$mail_valid) {
                        $mail_obj = $this->form->getItemByPostVar('usr_email');
                        $mail_obj->setAlert(sprintf(
                            $this->lng->txt("reg_email_domains"),
                            implode(", ", $domains)
                        ));
                        $form_valid = false;
                    }
                }
            }
        }

        $error_lng_var = '';
        if (
            !$this->registration_settings->passwordGenerationEnabled() &&
            !ilUtil::isPasswordValidForUserContext($this->form->getInput('usr_password'), $this->form->getInput('username'), $error_lng_var)
        ) {
            $passwd_obj = $this->form->getItemByPostVar('usr_password');
            $passwd_obj->setAlert($this->lng->txt($error_lng_var));
            $form_valid = false;
        }

        $showGlobalTermsOfServieFailure  = false;
        if (\ilTermsOfServiceHelper::isEnabled() && !$this->form->getInput('accept_terms_of_service')) {
            $agr_obj = $this->form->getItemByPostVar('accept_terms_of_service');
            if ($agr_obj) {
                $agr_obj->setAlert($this->lng->txt('force_accept_usr_agreement'));
                $form_valid = false;
            } else {
                $showGlobalTermsOfServieFailure = true;
            }
        }

        // no need if role is attached to code
        if (!$valid_role) {
            // manual selection
            if ($this->registration_settings->roleSelectionEnabled()) {
                $selected_role = $this->form->getInput("usr_roles");
                if ($selected_role && ilObjRole::_lookupAllowRegister($selected_role)) {
                    $valid_role = (int) $selected_role;
                }
            }
            // assign by email
            else {
                $registration_role_assignments = new ilRegistrationRoleAssignments();
                $valid_role = (int) $registration_role_assignments->getRoleByEmail($this->form->getInput("usr_email"));
            }
        }

        // no valid role could be determined
        if (!$valid_role) {
            ilUtil::sendInfo($this->lng->txt("registration_no_valid_role"));
            $form_valid = false;
        }

        // validate username
        $login_obj = $this->form->getItemByPostVar('username');
        $login = $this->form->getInput("username");
        // fau: regCodes - use login generation types
        if ($this->registration_settings->loginGenerationType() != ilRegistrationSettings::LOGIN_GEN_MANUAL) {
            $login = $this->__generateLogin();
            $_POST['username'] = $login;
            $this->form->getItemByPostVar('username')->setValue($login);
        } elseif (!ilUtil::isLogin($login)) {
            // fau.
            $login_obj->setAlert($this->lng->txt("login_invalid"));
            $form_valid = false;
        } elseif (ilObjUser::_loginExists($login)) {
            $login_obj->setAlert($this->lng->txt("login_exists"));
            $form_valid = false;
        } elseif ((int) $ilSetting->get('allow_change_loginname') &&
            (int) $ilSetting->get('reuse_of_loginnames') == 0 &&
            ilObjUser::_doesLoginnameExistInHistory($login)) {
            $login_obj->setAlert($this->lng->txt('login_exists'));
            $form_valid = false;
        }

        if (!$form_valid) {
            ilUtil::sendFailure($this->lng->txt('form_input_not_valid'));
        } elseif ($showGlobalTermsOfServieFailure) {
            $this->lng->loadLanguageModule('tos');
            \ilUtil::sendFailure(sprintf(
                $this->lng->txt('tos_account_reg_not_possible'),
                'mailto:' . ilUtil::prepareFormOutput(ilSystemSupportContacts::getMailsToAddress())
            ));
        } else {
            $password = $this->__createUser($valid_role);
            $this->__distributeMails($password);
            // fau: regCodes - call login with password
            return $this->login($password);
            // fau.
        }
        $this->form->setValuesByPost();
        return $this->displayForm();
    }

    protected function __createUser($a_role)
    {
        /**
         * @var $ilSetting ilSetting
         * @var $rbacadmin ilRbacAdmin
         * @var $lng       ilLanguage
         */
        global $DIC;

        $ilSetting = $DIC->settings();
        $rbacadmin = $DIC->rbac()->admin();
        
        
        // something went wrong with the form validation
        if (!$a_role) {
            global $DIC;

            $ilias = $DIC['ilias'];
            $ilias->raiseError("Invalid role selection in registration" .
                ", IP: " . $_SERVER["REMOTE_ADDR"], $ilias->error_obj->FATAL);
        }
        

        $this->userObj = new ilObjUser();

        $up = new ilUserProfile();
        $up->setMode(ilUserProfile::MODE_REGISTRATION);

        $map = array();
        $up->skipGroup("preferences");
        $up->skipGroup("settings");
        $up->skipField("password");
        $up->skipField("birthday");
        $up->skipField("upload");
        foreach ($up->getStandardFields() as $k => $v) {
            if ($v["method"]) {
                $method = "set" . substr($v["method"], 3);
                if (method_exists($this->userObj, $method)) {
                    if ($k != "username") {
                        $k = "usr_" . $k;
                    }
                    $field_obj = $this->form->getItemByPostVar($k);
                    if ($field_obj) {
                        $this->userObj->$method($this->form->getInput($k));
                    }
                }
            }
        }

        $this->userObj->setFullName();

        $birthday_obj = $this->form->getItemByPostVar("usr_birthday");
        if ($birthday_obj) {
            $birthday = $this->form->getInput("usr_birthday");
            $this->userObj->setBirthday($birthday);
        }

        $this->userObj->setTitle($this->userObj->getFullname());
        $this->userObj->setDescription($this->userObj->getEmail());

        // fau: regCodes: respect the password generation type
        if ($this->registration_settings->passwordGenerationType() == ilRegistrationSettings::PW_GEN_AUTO) {
            $password = ilUtil::generatePasswords(1);
            $password = $password[0];
        } elseif ($this->registration_settings->passwordGenerationType() == ilRegistrationSettings::PW_GEN_LOGIN) {
            $password = $this->userObj->getLogin();
        }
        // fau.
        else {
            $password = $this->form->getInput("usr_password");
        }
        $this->userObj->setPasswd($password);
        
        
        // Set user defined data
        $user_defined_fields =&ilUserDefinedFields::_getInstance();
        $defs = $user_defined_fields->getRegistrationDefinitions();
        $udf = array();
        foreach ($_POST as $k => $v) {
            if (substr($k, 0, 4) == "udf_") {
                $f = substr($k, 4);
                $udf[$f] = $v;
            }
        }
        $this->userObj->setUserDefinedData($udf);

        $this->userObj->setTimeLimitOwner(7);
        
        
        $access_limit = null;

        $this->code_was_used = false;
        if ($this->code_enabled) {
            $code_local_roles = $code_has_access_limit = null;

            // fau: regCodes - take the code object instead of form input
            if (isset($this->codeObj)) {
                // set code to used
                $this->codeObj->addUsage();
                $this->code_was_used = true;
                
                // handle code attached local role(s) and access limitation
                $code_local_roles = $this->codeObj->local_roles;

                if ($this->codeObj->limit_type) {
                    // see below
                    $code_has_access_limit = true;
                    
                    switch ($this->codeObj->limit_type) {
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
            $this->registration_settings->getAccessLimitation()) {
            $access_limitations_obj = new ilRegistrationRoleAccessLimitations();
            switch ($access_limitations_obj->getMode($a_role)) {
                case 'absolute':
                    $access_limit = $access_limitations_obj->getAbsolute($a_role);
                    break;
                
                case 'relative':
                    $rel_d = (int) $access_limitations_obj->getRelative($a_role, 'd');
                    $rel_m = (int) $access_limitations_obj->getRelative($a_role, 'm');
                    $access_limit = $rel_d * 86400 + $rel_m * 2592000 + time();
                    break;
            }
        }
        
        if ($access_limit) {
            $this->userObj->setTimeLimitUnlimited(0);
            $this->userObj->setTimeLimitUntil($access_limit);
        } else {
            $this->userObj->setTimeLimitUnlimited(1);
            $this->userObj->setTimeLimitUntil(time());
        }

        $this->userObj->setTimeLimitFrom(time());

        ilUserCreationContext::getInstance()->addContext(ilUserCreationContext::CONTEXT_REGISTRATION);

        $this->userObj->create();

        // fau: regCodes - 	check with code for activation
        if ($this->registration_settings->activationEnabled()) {
            // account has to be activated by email
            $this->userObj->setActive(0, 0);
        } elseif ($this->registration_settings->getRegistrationType() == IL_REG_DIRECT ||
            isset($this->codeObj)) {
            // account can directly be activated
            $this->userObj->setActive(1, 0);
        } else {
            // account has to e approved by admin
            $this->userObj->setActive(0, 0);
        }
        // fau.
        $this->userObj->updateOwner();

        // set a timestamp for last_password_change
        // this ts is needed by ilSecuritySettings
        $this->userObj->setLastPasswordChangeTS(time());
        
        $this->userObj->setIsSelfRegistered(true);

        //insert user data in table user_data
        $this->userObj->saveAsNew();

        // setup user preferences
        $this->userObj->setLanguage($this->form->getInput('usr_language'));

        $handleDocument = \ilTermsOfServiceHelper::isEnabled() && $this->termsOfServiceEvaluation->hasDocument();
        if ($handleDocument) {
            $helper = new \ilTermsOfServiceHelper();

            $helper->trackAcceptance($this->userObj, $this->termsOfServiceEvaluation->document());
        }

        $hits_per_page = $ilSetting->get("hits_per_page");
        if ($hits_per_page < 10) {
            $hits_per_page = 10;
        }
        $this->userObj->setPref("hits_per_page", $hits_per_page);
        if (strlen($_GET['target']) > 0) {
            $this->userObj->setPref('reg_target', ilUtil::stripSlashes($_GET['target']));
        }
        /*$show_online = $ilSetting->get("show_users_online");
        if ($show_online == "")
        {
            $show_online = "y";
        }
        $this->userObj->setPref("show_users_online", $show_online);*/
        $this->userObj->setPref('bs_allow_to_contact_me', $ilSetting->get('bs_allow_to_contact_me', 'n'));
        $this->userObj->setPref('chat_osc_accept_msg', $ilSetting->get('chat_osc_accept_msg', 'n'));

        // fau: regCodes - save used registration code in preferences
        if ($this->codeObj) {
            $this->userObj->setPref('registration_code', $this->codeObj->code);
        }
        // fau.
        $this->userObj->writePrefs();

        
        $rbacadmin->assignUser((int) $a_role, $this->userObj->getId());
        
        // local roles from code
        if ($this->code_was_used && is_array($code_local_roles)) {
            foreach (array_unique($code_local_roles) as $local_role_obj_id) {
                // is given role (still) valid?
                if (ilObject::_lookupType($local_role_obj_id) == "role") {
                    $rbacadmin->assignUser($local_role_obj_id, $this->userObj->getId());

                    // patch to remove for 45 due to mantis 21953
                    $role_obj = $GLOBALS['DIC']['rbacreview']->getObjectOfRole($local_role_obj_id);
                    switch (ilObject::_lookupType($role_obj)) {
                        case 'crs':
                        case 'grp':
                            $role_refs = ilObject::_getAllReferences($role_obj);
                            $role_ref = end($role_refs);
                            // deactivated for now, see discussion at
                            // https://docu.ilias.de/goto_docu_wiki_wpage_5620_1357.html
                            // fau: regCodes - add courses and groups to the recommended contents
                            $this->recommended_content_manager->addObjectRecommendation($this->userObj->getId(), $role_ref);
                            // fau.
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
        global $DIC;
        $base_login = '';

        switch ($this->registration_settings->loginGenerationType()) {
            case ilRegistrationSettings::LOGIN_GEN_MANUAL:
                $base_login = $this->form->getInput('username');
                break;

            case ilRegistrationSettings::LOGIN_GEN_FIRST_LASTNAME:
                $base_login = ilUtil::getASCIIFilename(strtolower($this->form->getInput('usr_firstname'))) . '.'
                    . ilUtil::getASCIIFilename(strtolower($this->form->getInput('usr_lastname')));
                break;

            case ilRegistrationSettings::LOGIN_GEN_GUEST_LISTENER:
                $semester = $DIC->fau()->study()->getCurrentTerm()->toString();
                $base_login = 'gh'
                    . (substr($semester, 4, 1) == '1' ? 's' : 'w')
                    . substr($semester, 2, 2)
                    . substr(ilUtil::getASCIIFilename(strtolower($this->form->getInput('usr_firstname'))), 0, 2)
                    . substr(ilUtil::getASCIIFilename(strtolower($this->form->getInput('usr_lastname'))), 0, 4);
                break;

            case ilRegistrationSettings::LOGIN_GEN_GUEST_SELFREG:
                $base_login = 'gsr' . rand(10000, 99999);
                break;
        }

        // append a number to get an unused login
        $login = $base_login;
        $i = 0;
        while (ilObjUser::_loginExists($login)) {
            $i++;
            $login = $base_login . $i;
        }

        return $login;
    }
    // fau.



    protected function __distributeMails($password)
    {
        // Always send mail to approvers
        if ($this->registration_settings->getRegistrationType() == IL_REG_APPROVE && !$this->code_was_used) {
            $mail = new ilRegistrationMailNotification();
            $mail->setType(ilRegistrationMailNotification::TYPE_NOTIFICATION_CONFIRMATION);
            $mail->setRecipients($this->registration_settings->getApproveRecipients());
            $mail->setAdditionalInformation(array('usr' => $this->userObj));
            $mail->send();
        } else {
            $mail = new ilRegistrationMailNotification();
            $mail->setType(ilRegistrationMailNotification::TYPE_NOTIFICATION_APPROVERS);
            $mail->setRecipients($this->registration_settings->getApproveRecipients());
            $mail->setAdditionalInformation(array('usr' => $this->userObj));
            $mail->send();
        }

        // Send mail to new user
        // Registration with confirmation link ist enabled
        // fau: regCodes - extended check for enabled activation (code or gloval)
        if ($this->registration_settings->activationEnabled()) {
            // fau.

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
            $accountMail = new ilAccountRegistrationMail(
                $this->registration_settings,
                $this->lng,
                ilLoggerFactory::getLogger('user')
            );
            $accountMail->withDirectRegistrationMode()->send($this->userObj, $password, $this->code_was_used);
        }
    }

    /**
     * @param string $password
     */
    // fau: regCodes - optional password parameter
    public function login($password = '')
    // fau.
    {
        global $DIC;
        $f = $DIC->ui()->factory();
        $renderer = $DIC->ui()->renderer();

        $tpl = ilStartUpGUI::initStartUpTemplate(array('tpl.usr_registered.html', 'Services/Registration'), false);
        $this->tpl->setVariable('TXT_PAGEHEADLINE', $this->lng->txt('registration'));

        $tpl->setVariable("TXT_WELCOME", $this->lng->txt("welcome") . ", " . $this->userObj->getTitle() . "!");
        if (
            (
                $this->registration_settings->getRegistrationType() == IL_REG_DIRECT ||
                $this->registration_settings->getRegistrationType() == IL_REG_CODES ||
                $this->code_was_used
            ) &&
            !$this->registration_settings->passwordGenerationEnabled()
        ) {
            // fau: regCodes - merge the username and password in the welcome text
            // create a hidden form to allow a direct login
            // set a timeout url to the starting page in order to prevent the password from being shown too long
            $ctrl = $DIC->ctrl();
            $ctrl->setParameterByClass('ilstartupgui', 'lang', $this->userObj->getLanguage());
            $ctrl->setParameterByClass('ilstartupgui', 'target', ilUtil::stripSlashes($_GET['target']));

            $tpl->setVariable("TXT_REGISTERED", sprintf($this->lng->txt("txt_registered"), $this->userObj->getLogin(), $password));
            $tpl->setVariable('FORMACTION', $ctrl->getFormActionByClass('ilstartupgui'));
            $tpl->setVariable('COMMAND', 'doStandardAuthentication');
            $tpl->setVariable('USERNAME', $this->userObj->getLogin());
            $tpl->setVariable('PASSWORD', $password);
            $tpl->setVariable('TXT_LOGIN', $this->lng->txt('local_login_to_ilias'));
            $tpl->setVariable('TIMEOUT_URL', 'index.php');
            // fau.
        } elseif ($this->registration_settings->getRegistrationType() == IL_REG_APPROVE) {
            $tpl->setVariable('TXT_REGISTERED', $this->lng->txt('txt_submitted'));
        } elseif ($this->registration_settings->getRegistrationType() == IL_REG_ACTIVATION) {
            $tpl->setVariable('TXT_REGISTERED', $this->lng->txt('reg_confirmation_link_successful'));
        } else {
            $tpl->setVariable('TXT_REGISTERED', $this->lng->txt('txt_registered_passw_gen'));
        }
        return $tpl;
    }

    /**
     * Do Login
     * @todo refactor this method should be renamed, but i don't wanted to make changed in
     * tpl.usr_registered.html in stable release.
     */
    protected function showLogin()
    {
        global $DIC;
        /**
         * @var ilAuthSession
         */
        $auth_session = $DIC['ilAuthSession'];
        $auth_session->setAuthenticated(
            true,
            $DIC->user()->getId()
        );
        ilInitialisation::initUserAccount();
        return ilInitialisation::redirectToStartingPage();
    }

    protected function doProfileAutoComplete()
    {
        $field_id = (string) $_REQUEST["f"];
        $term = (string) $_REQUEST["term"];

        $result = ilPublicUserProfileGUI::getAutocompleteResult($field_id, $term);
        if (sizeof($result)) {
            echo ilJsonUtil::encode($result);
        }
        
        exit();
    }
}
