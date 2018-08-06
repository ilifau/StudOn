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

        // set current user
        $this->setAuth($login, $ilUser);
        $_SESSION['AccountId'] = $ilUser->getId();

        // Prepare the Single Log-Out function
        $this->prepareLogout();

        // handle successful login
        $this->loginObserver();

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
     * Called after successful login
     * TODO: The standard login observer can't be called because ShibAuth has no AuthContainer
     *
     * @see ilAuthBase::loginObserver
     */
    protected function loginObserver()
    {
        global $ilUser, $ilAppEventHandler;

        // check for incomplete profile data
        include_once "Services/User/classes/class.ilUserProfile.php";
        if(ilUserProfile::isProfileIncomplete($ilUser))
        {
            $ilUser->setProfileIncomplete(true);
            $ilUser->update();
        }

        include_once 'Services/Tracking/classes/class.ilOnlineTracking.php';
        ilOnlineTracking::addUser($ilUser->getId());

        include_once 'Modules/Forum/classes/class.ilObjForum.php';
        ilObjForum::_updateOldAccess($ilUser->getId());

        // set the last_login
        $ilUser->refreshLogin();

        // reset counter for failed logins
        ilObjUser::_resetLoginAttempts($ilUser->getId());

        // fim: [log] write own login log
        $this->writeAuthLog('login', $ilUser->getLogin());
        // fim.

        // --- anonymous/registered user
        ilLoggerFactory::getLogger('auth')->info(
            'logged in as '.  $ilUser->getLogin() .
            ', remote:' . $_SERVER['REMOTE_ADDR'] . ':' . $_SERVER['REMOTE_PORT'] .
            ', server:' . $_SERVER['SERVER_ADDR'] . ':' . $_SERVER['SERVER_PORT']
        );

        $ilAppEventHandler->raise(
            'Services/Authentication', 'afterLogin',
            array('username' => $ilUser->getLogin())
        );
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

        // create an empty user object (this makes the user id available)
        $userObj = new ilObjUser();
        $userObj->create();

        // set basic account data
        $userObj->setLogin($login);
        $userObj->setPasswd(end(ilUtil::generatePasswords(1)), IL_PASSWD_PLAIN);
        $userObj->setLanguage($lng->getLangKey());
        $userObj->setAuthMode('shibboleth');

        // apply the IDM data and save the user data
        $this->data->applyToUser($userObj, 'create');

         // write the preferences
        $userObj->setPref('hits_per_page', $ilSetting->get('hits_per_page'));
        $userObj->setPref('show_users_online', $ilSetting->get('show_users_online', 'y'));
        $userObj->writePrefs();

        return $userObj;
    }


    /**
     * update and get an existing studon user
     */
    protected function getUpdatedUser($uid)
    {
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

        // apply the IDM data and update the user
        $this->data->applyToUser($userObj, 'update');

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
