<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once('./Services/AuthShibboleth/classes/class.ilShibboleth.php');
require_once('./Services/Idm/classes/class.ilIdmData.php');
require_once('./Services/StudyData/classes/class.ilStudyData.php');

/**
 * fau: samlAuth - Special SimpleSAML authentication class for StudOn
 *
 * This class is initialized in ilAuthUtils::_initAuth()
 * Afterwards it is available as global object $ilAuth
 * The login is done in shib_login.php after ILIAS initialisation
 *
 * @author   Fred Neumann <fred.neumann@fau.de>
 *
 * @defgroup ServicesAuthShibboleth Services/AuthShibboleth
 * @ingroup  ServicesAuthShibboleth
 */
class ilSimpleSamlAuthStudOn extends ShibAuth
{
    /**
     * @var sspmod_studon_Interface
     */
    protected $interface = null;
    /**
     * @var array   $attributes         attributes provided by simpleSAMLphp
     */
    protected $attributes = null;

    /**
     * @var string  $identity           user id provided by simpleSAMLAphp
     */
    protected $identity;

    /**
     * @var ilIdmData   data provided by idm
     */
    protected $data = null;


	/**
     * Constructor
	 * @param      $authParams
	 * @param bool $updateUserData
	 */
	public function __construct($authParams, $updateUserData = false)
    {
        parent::__construct($authParams, $updateUserData);

        // get information from simpleSAML
        $this->interface = $GLOBALS['ilSimpleSAMLInterface'];
        $this->attributes = $this->interface->getAttributes();
        $this->identity = $this->attributes['urn:mace:dir:attribute-def:uid'][0];

        // prevent a call of the login function from $ilAuth
		// this would be done too early in the initialisation process
		// the login function is called from saml_login.php after initialisation
		$this->setAllowLogin(false);
    }


	/**
	 * Login function
     *
     * This function is called from shib_login after ILIAS initialisation
	 */
	public function login()
    {
		global $ilCust, $ilUser, $ilias, $lng;

        // optionally log the request data for specific accounts
        $this->debugLogin();

        // check shibboleth session
        if (empty($this->identity))
        {
            $this->handleFailedLogin($lng->txt('shib_not_configured'));
        }

        // get the idm data
        if (DEVMODE and $ilCust->getSetting('shib_devmode_identity'))
        {
            $this->fetchIdmData($ilCust->getSetting('shib_devmode_identity'));
        }
        else
        {
            $this->fetchIdmData();
        }

        // get the studon login name for the idm data
        if (DEVMODE and $ilCust->getSetting('shib_devmode_login'))
        {
            $login = $ilCust->getSetting('shib_devmode_login');
        }
        else
        {
            $login = $this->generateLogin();
        }

        // set and update the user object
        if ($uid = ilObjUser::_lookupId($login))
        {
            $ilUser = $this->getUpdatedUser($uid);
        }
        else
        {
            $ilUser = $this->getNewUser($login);
        }
        $ilias->account = $ilUser;

        // set user authentified
        $this->setAuth($login, $ilUser);
        $this->writeAuthLog('login', $login);     // table ut_auth
        $_SESSION['AccountId'] = $ilUser->getId();

        $this->prepareLogout();

        // update the last login
        ilObjUser::_updateLastLogin($ilUser->getId());

        // check the authentication mode
        // this will redirect to the conversion screen for users with local auth mode
        ilInitialisation::checkStudOnAuthMode(true, AUTH_SHIBBOLETH);

        // redirect, if possible
        if ($_GET["target"] != "")
        {
            ilUtil::redirect("goto.php?target=".$_GET["target"]."&client_id=".CLIENT_ID);
        }
        else
        {
            ilUtil::redirect("index.php");
        }
	}

    /**
     * Prepare the Single Log-Out function
     * This registers a background call of saml_logout.php when the SAML session is logged out
     * /etc/hosts should configure 127.0.0.1 as www.studon.fau.de
     * /etc/apache2/studon-included.conf should allow http for saml_logout.php
     * /etc/apache2/conf-available/directory-access.conf should allow 127.0.0.1
     */
    protected function prepareLogout()
    {
        require_once "./Services/User/classes/class.ilUserUtil.php";

        // register the logout service
        $this->interface->registerLogoutService(ILIAS_HTTP_PATH . '/saml_logout.php', session_id());

        // used by saml_logout.php to verify a call of the logout service
        $_SESSION['saml_session_id'] = $this->interface->getSamlSessionId();

        // used by ilUserUtil::_getLogoutLink('auto')
        $_SESSION['saml_logout_url'] = $this->interface->getLogoutURL(ilUserUtil::_getLogoutLink('local'));
    }


	/**
	* Automatically generates the username/screenname of a Shibboleth user or returns
	* the user's already existing username
	*
	* @return 	string 	generated username
	*/
	protected function generateLogin()
	{
        // Try the identity as login
        if ($login = ilObjUser::_findLoginByField('login',  $this->data->identity))
        {
            return $login;
        }

        // Try the identity as external account
		if ($login = ilObjUser::_findLoginByField('ext_account', $this->data->identity))
		{
			return $login;
		}

		// Try the matriculation number
		if ($login = ilObjUser::_findLoginByField('matriculation', $this->data->matriculation))
		{
	        return $login;
		}
		
		// use the identity directly if no account is found
		// a new account will be created with this identity as login
		return $this->data->identity;
	}

    /**
     * create and get a new studon user
     */
    protected function getNewUser($login)
    {
        global $ilCust, $ilSetting, $lng;

        // check general possibility for creating accounts
        if (!$ilCust->getSetting('shib_allow_create'))
        {
            $this->handleFailedLogin($lng->txt('shib_user_not_found'));
        }

        // check the minimum attributes needed for new users
        if (
            empty($this->data->firstname)
            or empty($this->data->lastname)
            or empty($this->data->gender)
            //or empty($this->data->email)
        )
        {
            $this->handleFailedLogin(sprintf($lng->txt('shib_data_missing'),
                    (empty($this->data->firstname) ? $lng->txt('shib_data_missing_firstname') : '')
                .   (empty($this->data->lastname) ? $lng->txt('shib_data_missing_lastname') : '')
                .   (empty($this->data->gender) ? $lng->txt('shib_data_missing_gender') : '')
            //	.   (empty($this->data->email) ? $lng->txt('shib_data_missing_email') : '')
            ));
        }

        // check the availability of matriculation
        //  if (empty($this->data->matriculation))
        //  {
        //      $this->handleFailedLogin('','ilias.php?baseClass=ilStartUpGUI&cmd=shibNotCreatable&target='.$_GET["target"]);
        //  }

        // provided data
        $userObj = new ilObjUser();
        $userObj->setLogin($login);

        $userObj->setFirstname($this->data->firstname);
        $userObj->setLastname($this->data->lastname);
        $userObj->setGender($this->data->gender);
        $userObj->setEmail($this->data->email);
        $userObj->setMatriculation($this->data->matriculation);
        $userObj->setExternalAccount($this->data->identity);
        $userObj->setExternalPasswd($this->data->coded_password);

        if (!empty($this->data->coded_password))
        {
            $userObj->setPasswd($this->data->coded_password, IL_PASSWD_SSHA);
        }
        else
        {
            $userObj->setPasswd(md5(end(ilUtil::generatePasswords(1))), IL_PASSWD_MD5);
        }

        // system data
        $userObj->setAuthMode('shibboleth');
        $userObj->setFullname();
        $userObj->setTitle($userObj->getFullname());
        $userObj->setDescription($userObj->getEmail());
        $userObj->setLanguage($lng->getLangKey());

        // time limit
		if ($ilCust->getSetting('shib_create_limited'))
		{
			$limit = new ilDateTime($ilCust->getSetting('shib_create_limited'), IL_CAL_DATE);
			$userObj->setTimeLimitUnlimited(0);
			$userObj->setTimeLimitFrom(time());
			$userObj->setTimeLimitUntil($limit->get(IL_CAL_UNIX));
		}
		else
		{
			$userObj->setTimeLimitUnlimited(1);
			$userObj->setTimeLimitFrom(time());
			$userObj->setTimeLimitUntil(time());
		}
		$userObj->setTimeLimitOwner(7);
        $userObj->setLoginAttempts(0);

        // create
        $userObj->create();
        $userObj->setActive(1);
        $userObj->updateOwner();
        $userObj->saveAsNew();

         // preferences
        $userObj->setPref('hits_per_page', $ilSetting->get('hits_per_page', 30));
        $userObj->setPref('show_users_online', $ilSetting->get('show_users_online', 'y'));
        $userObj->writePrefs();

        // study data
        if (!empty($this->data->studies))
        {
            ilStudyData::_saveStudyData($userObj->getId(), $this->data->studies);
        }

        // update role assignments
        ilShibbolethRoleAssignmentRules::updateAssignments($userObj->getId(), (array) $this->data);

        return $userObj;
    }


    /**
     * update and get an existing studon user
     */
    protected function getUpdatedUser($uid)
    {
        global $ilSetting, $ilCust;

        $userObj = new ilObjUser($uid);

        // activate a timed out account via shibboleth
        // it is assumed that all users coming from shibboleth are allowed to access studon
        if (isset($_SESSION["SHIBBOLETH_CONVERSION"])
            or !$userObj->getActive()
            or !$userObj->checkTimeLimit())
        {
            // update the username if neccessary
            $login = $userObj->getLogin();
            if ($login != $this->data->identity

                and (strpos($login,'user.') === 0 or    // loca users
                     strpos($login,'vhb.') === 0 or     // vhb users
                     strpos($login, '.') 	=== false   // all other users except firstname.lastname
                ))
            {
                $userObj->updateLogin($this->data->identity);
            }

            // set the authentication mode to shibboleth
            // this will cause the profile fields to be updated below
            $userObj->setAuthMode("shibboleth");

            // reset agreement to force a new acceptance
            // set user active and unlimited
            $userObj->setAgreeDate(NULL);

            unset($_SESSION["SHIBBOLETH_CONVERSION"]);
        }

        // update the profile fields if auth mode is shibboleth
        if ($userObj->getAuthMode() == "shibboleth")
        {
            if (!empty($this->data->firstname)) {
                $userObj->setFirstname($this->data->firstname);
            }
            if (!empty($this->data->lastname)) {
                $userObj->setLastname($this->data->lastname);
            }
            if (!empty($this->data->gender)) {
                $userObj->setGender($this->data->gender);
            }
            if (!empty($this->data->email)
                and ($userObj->getEmail() == '' or $userObj->getEmail() == $ilSetting->get('mail_external_sender_noreply')))
            {
                $userObj->setEmail($this->data->email);
            }
            if (!empty($this->data->coded_password)) {
                $userObj->setPasswd($this->data->coded_password, IL_PASSWD_SSHA);
            }

            // dependent system data
            $userObj->setFullname();
            $userObj->setTitle($userObj->getFullname());
            $userObj->setDescription($userObj->getEmail());
        }

        // time limit and activation
        if ($ilCust->getSetting('shib_create_limited'))
        {
            $limit = new ilDateTime($ilCust->getSetting('shib_create_limited'), IL_CAL_DATE);
            $userObj->setTimeLimitUnlimited(0);
            $userObj->setTimeLimitFrom(time());
            $userObj->setTimeLimitUntil($limit->get(IL_CAL_UNIX));
        }
        else
        {
            $userObj->setTimeLimitUnlimited(1);
            $userObj->setTimeLimitFrom(time());
            $userObj->setTimeLimitUntil(time());
        }
        $userObj->setActive(1, 6);
        $userObj->setTimeLimitOwner(7);
        $userObj->setLoginAttempts(0);

        // always update external account and password
        $userObj->setExternalAccount($this->data->identity);
        $userObj->setExternalPasswd($this->data->coded_password);

        // always update matriculation number and study data
        if (!empty($this->data->matriculation)) {
            $userObj->setMatriculation($this->data->matriculation);
        }
        if (!empty($this->data->studies)) {
            ilStudyData::_saveStudyData($userObj->getId(), $this->data->studies);
        }

        $userObj->update();

        // update role assignments
        ilShibbolethRoleAssignmentRules::updateAssignments($userObj->getId(), (array) $this->data);

        return $userObj;
    }


    /**
     * Handle a failed login
     * @param string      failure message to be shown
     * @param string      redirection url
     */
    protected function handleFailedLogin($message = "", $url = "")
    {
        $this->setAuth('anonymous');
        $_SESSION['AccountId'] = ANONYMOUS_USER_ID;

        if ($message)
        {
            ilUtil::sendFailure($message, true);
        }

        if ($url)
        {
            ilUtil::redirect($url);
        }
        else
        {
            ilUtil::redirect(ilUtil::_getRootLoginLink());
        }
    }


    /**
     *  Optionally log the request data for specific accounts
     */
    protected function debugLogin()
    {
        global $ilCust;

        if ($log_accounts = $ilCust->getSetting('shib_log_accounts'))
        {
            $log_accounts = explode(',', $log_accounts);
            foreach ($log_accounts as $log_account)
            {
                if ($this->identity == trim($log_account))
                {
                    require_once "include/inc.debug.php";
                    log_request();
                    log_server();
                }
            }
        }
    }

    /**
     * Fetch the idm data either from database or from shibboleth attributes
     * @param   string  $a_identity     identity to be used (optional)
     */
    protected function fetchIdmData($a_identity = '')
    {
        $this->data = new ilIdmData();

        // set the identity to find the data in the database
        if (!empty($a_identity))
        {
            $this->data->identity = $a_identity;
        }
        else
        {
            $this->data->identity = $this->identity;
        }

        // try to read the idm data from the database
        if ($this->data->read() == false)
        {
            // not existent in database, the get the data from the shibboleth attibutes
            $rawdata = array();
            $rawdata['last_change']                 = date('Y-m-d H:i:s', time());
            $rawdata['pk_persistent_id']            = $this->identity;
            $rawdata['sn']                          = $this->attributes['urn:mace:dir:attribute-def:sn'][0];
            $rawdata['given_name']                  = $this->attributes['urn:mace:dir:attribute-def:givenName'][0];
            $rawdata['mail']                        = $this->attributes['urn:mace:dir:attribute-def:mail'][0];
            $rawdata['schac_gender']                = $this->attributes['urn:mace:terena.org:attribute-def:schacGender'][0];
            $rawdata['unscoped_affiliation']        = implode(';',$this->attributes['urn:mace:dir:attribute-def:eduPersonAffiliation']);
            $rawdata['user_password']               = $this->attributes['urn:mace:dir:attribute-def:userPassword'][0];
            $rawdata['schac_personal_unique_code']  = $this->attributes['urn:mace:terena.org:attribute-def:schacPersonalUniqueCode'][0];
            $rawdata['fau_features_of_study']       = '';
            $rawdata['fau_employee']                = null;
            $rawdata['fau_student']                 = null;
            $rawdata['fau_guest']                   = null;

            $this->data->setRawData($rawdata, true);
        }
    }
}
