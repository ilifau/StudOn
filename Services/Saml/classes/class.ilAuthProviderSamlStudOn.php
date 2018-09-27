<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once('./Services/Idm/classes/class.ilIdmData.php');
require_once('./Services/StudyData/classes/class.ilStudyData.php');

/**
 * fau: samlAuth new class for saml authentication in studon
 */
class ilAuthProviderSamlStudOn extends ilAuthProviderSaml
{
	/**
	 * @var ilIdmData   data provided by idm
	 */
	protected $data = null;

	/**
	 * @inheritdoc
	 */
	public function doAuthentication(\ilAuthStatus $status)
	{
		if (!is_array($this->attributes) || 0 === count($this->attributes)) {
			$this->getLogger()->warning('Could not parse any attributes from SAML response.');
			$this->handleAuthenticationFail($status, 'shib_not_configured');
			return false;
		}

		try {
			// get the uid attribute
			$this->uid = $this->attributes['urn:mace:dir:attribute-def:uid'][0];

			// optionally log the request data for specific accounts
			$this->debugLogin();

			// check shibboleth session
			if (empty($this->uid))
			{
				$this->getLogger()->warning('Uid attribute is not set in SAML data.');
				$this->handleAuthenticationFail($status, 'shib_not_configured');
				return false;
			}

			// get the idm data
			if (DEVMODE and ilCust::get('shib_devmode_identity'))
			{
				$this->fetchIdmData(ilCust::get('shib_devmode_identity'));
			}
			else
			{
				$this->fetchIdmData();
			}

			// get the studon login name for the idm data
			if (DEVMODE and ilCust::get('shib_devmode_login'))
			{
				$login = ilCust::get('shib_devmode_login');
			}
			else
			{
				$login = $this->generateLogin();
			}

			// set and update the user object
			if ($id = (int) ilObjUser::_lookupId($login))
			{
				$user = $this->getUpdatedUser($id);
			}
			else
			{
				// check general possibility for creating accounts
				if (!ilCust::get('shib_allow_create'))
				{
					$this->getLogger()->warning('Creation of new users from SAML authentication is prevented.');
					$this->handleAuthenticationFail($status, 'shib_user_not_found');
					return false;
				}

				// check the minimum attributes needed for new users
				if (
					empty($this->data->firstname)
					|| empty($this->data->lastname)
					|| empty($this->data->gender)
					//|| empty($this->data->email)
				)
				{
					$this->getLogger()->warning('Could not create new user because firstname, lastname or gender is m missing in SAML attributes.');
					$this->handleAuthenticationFail($status, 'shib_shib_data_missing');
					return false;
				}
				$user = $this->getNewUser($login);
			}

			$status->setStatus(ilAuthStatus::STATUS_AUTHENTICATED);
			$status->setAuthenticatedUserId($user->getId());
			ilSession::set('used_external_auth', true);
			return true;


		} catch (\ilException $e) {
			$this->getLogger()->error($e->getMessage());
			$this->handleAuthenticationFail($status, 'err_wrong_login');
			return false;
		}
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
        if (!empty($this->data->matriculation))
        {
            if ($login = ilObjUser::_findLoginByField('matriculation', $this->data->matriculation))
            {
                return $login;
            }
        }

		// use the identity directly if no account is found
		// a new account will be created with this identity as login
		return $this->data->identity;
	}

	/**
	 * create and get a new studon user
	 * @param string $login
	 * @return ilObjUser
	 */
	protected function getNewUser($login)
	{
		global $ilSetting, $lng;

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
	 * @param int $user_id
	 * @return ilObjUser
	 * @throws ilUserException
	 */
	protected function getUpdatedUser($user_id)
	{
		$userObj = new ilObjUser($user_id);

		// activate a timed out account via shibboleth
		// it is assumed that all users coming from shibboleth are allowed to access studon
		if (!$userObj->getActive() || !$userObj->checkTimeLimit())
		{
			// update the username if necessary
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
		}

		// apply the IDM data and update the user
		$this->data->applyToUser($userObj, 'update');

		return $userObj;
	}


	/**
	 *  Optionally log the request data for specific accounts
	 */
	protected function debugLogin()
	{
		if ($log_accounts = ilCust::get('shib_log_accounts'))
		{
			$log_accounts = explode(',', $log_accounts);
			foreach ($log_accounts as $log_account)
			{
				if ($this->uid == trim($log_account))
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
			$this->data->identity = $this->uid;
		}

		// try to read the idm data from the database
		if ($this->data->read() == false)
		{
			// not existent in database, the get the data from the shibboleth attributes
			$rawdata = array();
			$rawdata['last_change']                 = date('Y-m-d H:i:s', time());
			$rawdata['pk_persistent_id']            = $this->uid;
			$rawdata['sn']                          = $this->attributes['urn:mace:dir:attribute-def:sn'][0];
			$rawdata['given_name']                  = $this->attributes['urn:mace:dir:attribute-def:givenName'][0];
			$rawdata['mail']                        = $this->attributes['urn:mace:dir:attribute-def:mail'][0];
			$rawdata['schac_gender']                = $this->attributes['urn:mace:terena.org:attribute-def:schacGender'][0];
			$rawdata['unscoped_affiliation']        = implode(';',$this->attributes['urn:mace:dir:attribute-def:eduPersonAffiliation']);
            // Passwords by SSO have {CRYPT} prefix - not yet supported by StudOn
			//$rawdata['user_password']               = $this->attributes['urn:mace:dir:attribute-def:userPassword'][0];
			$rawdata['schac_personal_unique_code']  = $this->attributes['urn:mace:terena.org:attribute-def:schacPersonalUniqueCode'][0];
			$rawdata['fau_features_of_study']       = '';
			$rawdata['fau_employee']                = null;
			$rawdata['fau_student']                 = null;
			$rawdata['fau_guest']                   = null;

			$this->data->setRawData($rawdata, true);
		}
	}
}