<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

/**
 * Class ilAccountRegistrationGUI
 * @author       Stefan Meyer <smeyer.ilias@gmx.de>
 * @ilCtrl_Calls ilAccountRegistrationGUI:
 */
class ilAccountRegistrationGUI
{
    protected ilRegistrationSettings $registration_settings;
    protected bool $code_enabled = false;
    protected bool $code_was_used;
    protected ilRecommendedContentManager $recommended_content_manager;

    protected ilUserProfile $user_profile;

    protected ?ilPropertyFormGUI $form = null;

    protected ilGlobalTemplateInterface $tpl;
    protected ilCtrlInterface $ctrl;
    protected ilLanguage $lng;
    protected ilErrorHandling $error;
    protected ?ilObjUser $userObj = null;
    protected ilObjUser $globalUser;
    protected ilSetting $settings;
    protected ilRbacReview $rbacreview;
    protected ilRbacAdmin $rbacadmin;
    protected ILIAS\UI\Factory $ui_factory;
    protected ILIAS\UI\Renderer $ui_renderer;

    protected ILIAS\Refinery\Factory $refinery;
    protected \ILIAS\HTTP\Services $http;

    // fau: regCodes - class variables
    protected ?ilRegistrationCode $codeObj = null;
    private ?string $login = null;
    // fau.


    public function __construct()
    {
        global $DIC;

        $this->tpl = $DIC->ui()->mainTemplate();

        $this->ctrl = $DIC->ctrl();
        $this->ctrl->saveParameter($this, 'lang');
        $this->lng = $DIC->language();
        $this->lng->loadLanguageModule('registration');
        $this->error = $DIC['ilErr'];
        $this->settings = $DIC->settings();
        $this->globalUser = $DIC->user();
        $this->rbacreview = $DIC->rbac()->review();
        $this->rbacadmin = $DIC->rbac()->admin();
        $this->ui_factory = $DIC->ui()->factory();
        $this->ui_renderer = $DIC->ui()->renderer();

        // fau: regCodes - initialize an already entered code and save in settings
        $this->registration_settings = ilRegistrationSettings::getInstance();
        $this->code_enabled = ($this->registration_settings->registrationCodeRequired() ||
            $this->registration_settings->getAllowCodes());

        $this->recommended_content_manager = new ilRecommendedContentManager();
        if ($this->code_enabled) {
            if (!empty($_GET['code'])) {
                $this->codeObj = new ilRegistrationCode($_GET['code']);
                if ($this->codeObj->isUsable()) {
                    $_SESSION['ilAccountRegistrationGUI:code'] = $this->codeObj->code;
                }
            } elseif (isset($_SESSION['ilAccountRegistrationGUI:code']) && $_SESSION['ilAccountRegistrationGUI:code']) {
                $this->codeObj = new ilRegistrationCode(($_SESSION['ilAccountRegistrationGUI:code']));
            }

            if (isset($this->codeObj)) {
                $this->registration_settings->setCodeObject($this->codeObj);
            }
        }
        // fau.
        $this->user_profile = new ilUserProfile();

        $this->http = $DIC->http();
        $this->refinery = $DIC->refinery();
    }

    public function executeCommand(): void
    {
        if ($this->registration_settings->getRegistrationType() === ilRegistrationSettings::IL_REG_DISABLED) {
            $this->error->raiseError($this->lng->txt('reg_disabled'), $this->error->FATAL);
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

        $this->tpl->setPermanentLink('usr', null, 'registration');
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

                $this->tpl->setOnScreenMessage('failure', $this->lng->txt('form_input_not_valid'));
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

    public function displayForm(): ilGlobalTemplateInterface
    {
        $tpl = ilStartUpGUI::initStartUpTemplate(['tpl.usr_registration.html', 'Services/Registration'], true);
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
            $this->initForm();
        }
        $tpl->setVariable('FORM', $this->form->getHTML());
        return $tpl;
    }

    protected function initForm(): void
    {
        $this->globalUser->setLanguage($this->lng->getLangKey());
        $this->globalUser->setId(ANONYMOUS_USER_ID);

        // needed for multi-text-fields (interests)
        iljQueryUtil::initjQuery();

        $this->form = new ilPropertyFormGUI();
        $this->form->setFormAction($this->ctrl->getFormAction($this));

        // fau: regCodes - don't show code field in the registration form
        // fau.

        // user defined fields
        $user_defined_data = $this->globalUser->getUserDefinedData();
        $user_defined_fields = ilUserDefinedFields::_getInstance();
        $custom_fields = [];

        foreach ($user_defined_fields->getRegistrationDefinitions() as $field_id => $definition) {
            $fprop = ilCustomUserFieldsHelper::getInstance()->getFormPropertyForDefinition(
                $definition,
                true,
                $user_defined_data['f_' . $field_id] ?? ''
            );
            if ($fprop instanceof ilFormPropertyGUI) {
                $custom_fields['udf_' . $definition['field_id']] = $fprop;
            }
        }

        $this->user_profile->setMode(ilUserProfile::MODE_REGISTRATION);
        $this->user_profile->skipGroup("preferences");

        $this->user_profile->setAjaxCallback(
            $this->ctrl->getLinkTarget($this, 'doProfileAutoComplete', '', true)
        );
        $this->lng->loadLanguageModule("user");
        // add fields to form
        $this->user_profile->addStandardFieldsToForm($this->form, null, $custom_fields);
        unset($custom_fields);

        // set language selection to current display language
        $flang = $this->form->getItemByPostVar("usr_language");
        if ($flang) {
            $flang->setValue($this->lng->getLangKey());
        }

        // add information to role selection (if not hidden)
        if ($this->code_enabled) {
            $role = $this->form->getItemByPostVar("usr_roles");
            if ($role && $role->getType() === "select") {
                $role->setInfo($this->lng->txt("registration_code_role_info"));
            }
        }

        // #11407
        $domains = [];
        foreach ($this->registration_settings->getAllowedDomains() as $item) {
            if (trim($item)) {
                $domains[] = $item;
            }
        }
        if (count($domains)) {
            $mail_obj = $this->form->getItemByPostVar('usr_email');
            $mail_obj->setInfo(sprintf(
                $this->lng->txt("reg_email_domains"),
                implode(", ", $domains)
            ) . "<br />" .
                ($this->code_enabled ? $this->lng->txt("reg_email_domains_code") : ""));
        }

        // #14272
        // fau: regCodes - check for registration type and code to set email required
//        if ($this->registration_settings->getRegistrationType() === ilRegistrationSettings::IL_REG_ACTIVATION) {
            $mail_obj = $this->form->getItemByPostVar('usr_email');
            if ($mail_obj) { // #16087
                $mail_obj->setRequired(true);
            }
  //      }
        // fau.

        global $DIC;
        array_map($this->form->addItem(...), $DIC['legalDocuments']->selfRegistration()->legacyInputGUIs());

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

    public function saveForm(): ilGlobalTemplateInterface
    {
        $this->initForm();
        $form_valid = $this->form->checkInput();

        // custom validation
        $valid_code = $valid_role = false;

        // code
        if ($this->code_enabled) {
            $code = $this->form->getInput('usr_registration_code');
            // fau: regCodes - take the code object instead of form input
            // could be optional
            // could be optional
            if ($this->codeObj) {
                // code has been checked in executeCommand
                $valid_code = true;

                // get role from code, check if (still) valid
                global $DIC;
                $role_id = $this->codeObj->global_role;
                if ($role_id && $DIC->rbac()->review()->isGlobalRole($role_id)) {
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
                $domains = [];
                foreach ($this->registration_settings->getAllowedDomains() as $item) {
                    if (trim($item)) {
                        $domains[] = $item;
                    }
                }
                if (count($domains)) {
                    $mail_valid = false;
                    foreach ($domains as $domain) {
                        $domain = str_replace("*", "~~~", $domain);
                        $domain = preg_quote($domain, '/');
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
            !ilSecuritySettingsChecker::isPasswordValidForUserContext(
                $this->form->getInput('usr_password'),
                $this->form->getInput('username'),
                $error_lng_var
            )
        ) {
            $passwd_obj = $this->form->getItemByPostVar('usr_password');
            $passwd_obj->setAlert($this->lng->txt($error_lng_var));
            $form_valid = false;
        }

        global $DIC;
        $form_valid = $DIC['legalDocuments']->selfRegistration()->saveLegacyForm($this->form) && $form_valid;

        // no need if role is attached to code
        if (!$valid_role) {
            // manual selection
            if ($this->registration_settings->roleSelectionEnabled()) {
                $selected_role = $this->form->getInput("usr_roles");
                if ($selected_role && ilObjRole::_lookupAllowRegister((int) $selected_role)) {
                    $valid_role = (int) $selected_role;
                }
            } // assign by email
            else {
                $registration_role_assignments = new ilRegistrationRoleAssignments();
                $valid_role = $registration_role_assignments->getRoleByEmail($this->form->getInput("usr_email"));
            }
        }

        // no valid role could be determined
        if (!$valid_role && (!isset($selected_role) || $selected_role !== '')) {
            $this->tpl->setOnScreenMessage('info', $this->lng->txt("registration_no_valid_role"));
            $form_valid = false;
        }

        // validate username
        $login_obj = $this->form->getItemByPostVar('username');
        $login = $this->form->getInput("username");
        // fau: regCodes - use login generation types
        if ($this->registration_settings->loginGenerationType() != ilRegistrationSettings::LOGIN_GEN_MANUAL) {
            $this->login = $this->__generateLogin();
            $_POST['username'] = $login;
            $this->form->getItemByPostVar('username')->setValue($login);

        }
        elseif ($form_valid) {
            // fau.
            if (ilObjUser::_loginExists($login)) {
                $login_obj->setAlert($this->lng->txt("login_exists"));
                $form_valid = false;
            } elseif ((int) $this->settings->get('allow_change_loginname') &&
                (int) $this->settings->get('reuse_of_loginnames') === 0 &&
                ilObjUser::_doesLoginnameExistInHistory($login)) {
                $login_obj->setAlert($this->lng->txt('login_exists'));
                $form_valid = false;
            }
        }

        if (!$form_valid) {
            $this->tpl->setOnScreenMessage('failure', $this->lng->txt('form_input_not_valid'));
        } else {
            $password = $this->createUser((int) $valid_role);
            $this->distributeMails($password);
            // fau: regCodes - call login with password
            return $this->login($password);
            // fau.
        }
        $this->form->setValuesByPost();
        return $this->displayForm();
    }

    protected function createUser(int $a_role): string
    {
        // something went wrong with the form validation
        if (!$a_role) {
            global $DIC;

            $ilias = $DIC['ilias'];
            $ilias->raiseError("Invalid role selection in registration" .
                ", IP: " . $_SERVER["REMOTE_ADDR"], $ilias->error_obj->FATAL);
        }

        $this->userObj = new ilObjUser();
        if ((int) $this->settings->get('auth_mode') !== ilAuthUtils::AUTH_LOCAL) {
            $this->userObj->setAuthMode('local');
        }

        $this->user_profile->skipGroup("preferences");
        $this->user_profile->skipGroup("settings");
        $this->user_profile->skipField("password");
        $this->user_profile->skipField("birthday");
        $this->user_profile->skipField("upload");
        foreach ($this->user_profile->getStandardFields() as $k => $v) {
            if ($v["method"]) {
                $method = "set" . substr($v["method"], 3);
                if (method_exists($this->userObj, $method)) {
                    if ($k !== "username") {
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
        $this->userObj->setLogin($this->login);
        if ($this->registration_settings->passwordGenerationType() == ilRegistrationSettings::PW_GEN_AUTO) {
            $password = ilSecuritySettingsChecker::generatePasswords(1);
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
        $user_defined_fields = ilUserDefinedFields::_getInstance();
        $defs = $user_defined_fields->getRegistrationDefinitions();
        $udf = [];
        foreach ($defs as $definition) {
            $f = "udf_" . $definition['field_id'];
            $item = $this->form->getItemByPostVar($f);
            if ($item && !$item->getDisabled()) {
                $udf[$definition['field_id']] = $this->form->getInput($f);
            }
        }
        $this->userObj->setUserDefinedData($udf);

        $this->userObj->setTimeLimitOwner(7);

        $access_limit = null;

        $this->code_was_used = false;
        $code_has_access_limit = false;
        $code_local_roles = [];
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
                            $access_limit = (int)($rel["d"] * 86400 + $rel["m"] * 2592000 +
                                $rel["y"] * 31536000 + time());
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
                    $rel_d = $access_limitations_obj->getRelative($a_role, 'd');
                    $rel_m = $access_limitations_obj->getRelative($a_role, 'm');
                    $access_limit = $rel_d * 86400 + $rel_m * 2592000 + time();
                    break;
            }
        }

        if ($access_limit) {
            $this->userObj->setTimeLimitUnlimited(false);
            $this->userObj->setTimeLimitUntil($access_limit);
        } else {
            $this->userObj->setTimeLimitUnlimited(true);
            $this->userObj->setTimeLimitUntil(time());
        }

        $this->userObj->setTimeLimitFrom(time());

        ilUserCreationContext::getInstance()->addContext(ilUserCreationContext::CONTEXT_REGISTRATION);

        $this->userObj->create();

        // fau: regCodes - 	check with code for activation
        if ($this->registration_settings->activationEnabled()) {
            // account has to be activated by email
            $this->userObj->setActive(false, 0);
        } elseif ($this->registration_settings->getRegistrationType() == ilRegistrationSettings::IL_REG_DIRECT ||
            isset($this->codeObj)) {
            // account can directly be activated
            $this->userObj->setActive(true, 0);
        } else {
            // account has to e approved by admin
            $this->userObj->setActive(false, 0);
        }
        // fau.

        // set a timestamp for last_password_change
        // this ts is needed by ilSecuritySettings
        $this->userObj->setLastPasswordChangeTS(time());

        $this->userObj->setIsSelfRegistered(true);

        //insert user data in table user_data
        $this->userObj->saveAsNew();

        // don't update owner before the first save. updateOwner rereads the object which fails if it not save before
        $this->userObj->updateOwner();

        // setup user preferences
        $this->userObj->setLanguage($this->form->getInput('usr_language'));

        global $DIC;
        $DIC['legalDocuments']->selfRegistration()->userCreation($this->userObj);

        $hits_per_page = $this->settings->get("hits_per_page");
        if ($hits_per_page < 10) {
            $hits_per_page = 10;
        }
        $this->userObj->setPref("hits_per_page", $hits_per_page);
        if ($this->http->wrapper()->query()->has('target')) {
            $this->userObj->setPref(
                'reg_target',
                $this->http->wrapper()->query()->retrieve(
                    'target',
                    $this->refinery->kindlyTo()->string()
                )
            );
        }
        $this->userObj->setPref('bs_allow_to_contact_me', $this->settings->get('bs_allow_to_contact_me', 'n'));
        $this->userObj->setPref('chat_osc_accept_msg', $this->settings->get('chat_osc_accept_msg', 'n'));
        // fau: regCodes - save used registration code in preferences
        if ($this->codeObj) {
            $this->userObj->setPref('registration_code', $this->codeObj->code);
        }
        // fau.
        $this->userObj->setPref('chat_broadcast_typing', $this->settings->get('chat_broadcast_typing', 'n'));
        $this->userObj->writePrefs();

        $this->rbacadmin->assignUser($a_role, $this->userObj->getId());

        // local roles from code
        if ($this->code_was_used && is_array($code_local_roles)) {
            $code_local_roles = array_map(intval(...), array_unique($code_local_roles));
            foreach ($code_local_roles as $local_role_obj_id) {
                // is given role (still) valid?
                if (ilObject::_lookupType($local_role_obj_id) === "role") {
                    $this->rbacadmin->assignUser($local_role_obj_id, $this->userObj->getId());

                    // patch to remove for 45 due to mantis 21953
                    $role_obj = $GLOBALS['DIC']['rbacreview']->getObjectOfRole($local_role_obj_id);
                    switch (ilObject::_lookupType($role_obj)) {
                        case 'crs':
                        case 'grp':
                            $role_refs = ilObject::_getAllReferences($role_obj);
                            $role_ref = end($role_refs);
                            // deactivated for now, see discussion at
                            // https://docu.ilias.de/goto_docu_wiki_wpage_5620_1357.html
                            // $this->recommended_content_manager->addObjectRecommendation($this->userObj->getId(), $role_ref);
                            // fau: regCodes - add courses and groups to the recommended contents
                            $this->recommended_content_manager->addObjectRecommendation($this->userObj->getId(), $role_ref);
                            // fau.
                            break;
                    }
                }
            }
        }

        return (string) $password;
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
                $base_login = ilFileUtils::getASCIIFilename(strtolower($this->form->getInput('usr_firstname'))) . '.'
                    . ilFileUtils::getASCIIFilename(strtolower($this->form->getInput('usr_lastname')));
                break;

            case ilRegistrationSettings::LOGIN_GEN_GUEST_LISTENER:
                $semester = $DIC->fau()->study()->getCurrentTerm()->toString();
                $base_login = 'gh'
                    . (substr($semester, 4, 1) == '1' ? 's' : 'w')
                    . substr($semester, 2, 2)
                    . substr(ilFileUtils::getASCIIFilename(strtolower($this->form->getInput('usr_firstname'))), 0, 2)
                    . substr(ilFileUtils::getASCIIFilename(strtolower($this->form->getInput('usr_lastname'))), 0, 4);
                break;

            case ilRegistrationSettings::LOGIN_GEN_GUEST_SELFREG:
                $prefix = ilCust::get("regbycode_prefix");
                if($prefix == '')
                    $prefix = "gsr";
                $base_login = $prefix . rand(10000, 99999);
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
    protected function distributeMails(string $password): void
    {
        // Send mail to approvers, if they are defined
        if ($this->registration_settings->getApproveRecipients()) {
            $mail = new ilRegistrationMailNotification();

            if (!$this->code_was_used &&
                $this->registration_settings->getRegistrationType() === ilRegistrationSettings::IL_REG_APPROVE) {
                $mail->setType(ilRegistrationMailNotification::TYPE_NOTIFICATION_CONFIRMATION);
            } else {
                $mail->setType(ilRegistrationMailNotification::TYPE_NOTIFICATION_APPROVERS);
            }
            $mail->setRecipients($this->registration_settings->getApproveRecipients());
            $mail->setAdditionalInformation(['usr' => $this->userObj]);
            $mail->send();
        }
        // Send mail to new user
        // Registration with confirmation link ist enabled
        // fau: regCodes - extended check for enabled activation (code or gloval)
        if ($this->registration_settings->activationEnabled()) {
            // fau.
            $mail = new ilRegistrationMimeMailNotification();
            $mail->setType(ilRegistrationMimeMailNotification::TYPE_NOTIFICATION_ACTIVATION);
            $mail->setRecipients([$this->userObj]);
            $mail->setAdditionalInformation(
                [
                    'usr' => $this->userObj,
                    'hash_lifetime' => $this->registration_settings->getRegistrationHashLifetime()
                ]
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

    // fau: regCodes - optional password parameter
    public function login($password = ''): ilGlobalTemplateInterface
    // fau.
    {
        $tpl = ilStartUpGUI::initStartUpTemplate(['tpl.usr_registered.html', 'Services/Registration'], false);
        $this->tpl->setVariable('TXT_PAGEHEADLINE', $this->lng->txt('registration'));

        $tpl->setVariable("TXT_WELCOME", $this->lng->txt("welcome") . ", " . $this->userObj->getTitle() . "!");
        if (
            (
                $this->registration_settings->getRegistrationType() === ilRegistrationSettings::IL_REG_DIRECT ||
                $this->registration_settings->getRegistrationType() === ilRegistrationSettings::IL_REG_CODES ||
                $this->code_was_used
            ) &&
            !$this->registration_settings->passwordGenerationEnabled()
        ) {
            // fau: regCodes - merge the username and password in the welcome text
            // create a hidden form to allow a direct login
            // set a timeout url to the starting page in order to prevent the password from being shown too long
            global $DIC;
            $ctrl = $DIC->ctrl();
            $ctrl->setParameterByClass('ilstartupgui', 'lang', $this->userObj->getLanguage());
            $ctrl->setParameterByClass('ilstartupgui', 'target', ilUtil::stripSlashes($_GET['target'] ?? ""));

            $tpl->setVariable("TXT_REGISTERED", sprintf($this->lng->txt("txt_registered"), $this->userObj->getLogin(), $password));
            $tpl->setVariable('FORMACTION', $ctrl->getFormActionByClass('ilstartupgui'));
            $tpl->setVariable('COMMAND', 'showLoginPage');
            //$tpl->setVariable('USERNAME', $this->userObj->getLogin());
            //$tpl->setVariable('PASSWORD', $password);
            $tpl->setVariable('TXT_LOGIN', $this->lng->txt('local_login_to_ilias_registered'));
            $tpl->setVariable('TIMEOUT_URL', 'index.php');
            // fau.
        } elseif ($this->registration_settings->getRegistrationType() === ilRegistrationSettings::IL_REG_APPROVE) {
            $tpl->setVariable('TXT_REGISTERED', $this->lng->txt('txt_submitted'));
        } elseif ($this->registration_settings->getRegistrationType() === ilRegistrationSettings::IL_REG_ACTIVATION) {
            $tpl->setVariable('TXT_REGISTERED', $this->lng->txt('reg_confirmation_link_successful'));
        } else {
            $tpl->setVariable('TXT_REGISTERED', $this->lng->txt('txt_registered_passw_gen'));
        }
        return $tpl;
    }

    protected function doProfileAutoComplete(): void
    {
        $field_id = (string) $_REQUEST["f"];
        $term = (string) $_REQUEST["term"];

        $result = ilPublicUserProfileGUI::getAutocompleteResult($field_id, $term);
        if (count($result)) {
            echo json_encode($result, JSON_THROW_ON_ERROR);
        }

        exit();
    }
}
