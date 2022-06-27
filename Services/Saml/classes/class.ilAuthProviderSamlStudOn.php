<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

use FAU\Staging\Data\Identity;

/**
 * fau: samlAuth new class for saml authentication in studon
 */
class ilAuthProviderSamlStudOn extends ilAuthProviderSaml
{
    protected Identity $identity;

    /**
     * @inheritdoc
     */
    public function doAuthentication(\ilAuthStatus $status)
    {
        if (!is_array($this->attributes) || 0 === count($this->attributes)) {
            $this->getLogger()->warning('Could not parse any attributes from SAML response.');
            $this->handleAuthenticationFail($status, 'auth_shib_not_configured');
            return false;
        }

        try {
            // get the uid attribute
            $this->uid = $this->attributes['urn:mace:dir:attribute-def:uid'][0];

            // nedded since ILIAS 5.4.10
            if (empty($this->uid)) {
                $this->uid = $this->attributes['urn:oid:0.9.2342.19200300.100.1.1'][0];
            }


            // optionally log the request data for specific accounts
            $this->debugLogin();

            // check shibboleth session
            if (empty($this->uid)) {
                $this->getLogger()->warning('Uid attribute is not set in SAML data.');
                $this->handleAuthenticationFail($status, 'auth_shib_not_configured');
                return false;
            }

            // get the idm data
            if (DEVMODE and ilCust::get('shib_devmode_identity')) {
                $this->fetchIdentity(ilCust::get('shib_devmode_identity'));
            } else {
                $this->fetchIdentity();
            }

            // get the studon login name for the idm data
            if (DEVMODE and ilCust::get('shib_devmode_login')) {
                $login = ilCust::get('shib_devmode_login');
            } else {
                $login = $this->generateLogin();
            }

            // set and update the user object
            if ($id = (int) ilObjUser::_lookupId($login)) {
                // existing user account matches
                $user = $this->getUpdatedUser($id);
            }
            else {
                // check general possibility for creating accounts
                if (!ilCust::get('shib_allow_create')) {
                    $this->getLogger()->warning('Creation of new users from SAML authentication is prevented.');
                    $this->handleAuthenticationFail($status, 'shib_user_not_found');
                    return false;
                }

                // check the minimum attributes needed for new users
                if (empty($this->identity->getGivenName()) || empty($this->identity->getSn())) {
                    $this->getLogger()->warning('Could not create new user because firstname or lastname is m missing in SAML attributes.');
                    $this->handleAuthenticationFail($status, 'shib_data_missing');
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
        if ($login = ilObjUser::_findLoginByField('login', $this->identity->getPkPersistentId())) {
            return $login;
        }

        // Try the identity as external account
        if ($login = ilObjUser::_findLoginByField('ext_account', $this->identity->getPkPersistentId())) {
            return $login;
        }

        // Try the matriculation number
        if (!empty($this->identity->getMatriculation())) {
            if ($login = ilObjUser::_findLoginByField('matriculation', $this->identity->getMatriculation())) {
                return $login;
            }
        }

        // use the identity directly if no account is found
        // a new account will be created with this identity as login
        return $this->identity->getPkPersistentId();
    }

    /**
     * create and get a new studon user
     * @param string $login
     * @return ilObjUser
     */
    protected function getNewUser($login)
    {
        global $DIC;

        // create an empty user object (this makes the user id available)
        $userObj = new ilObjUser();
        $userObj->create();

        // set basic account data
        $userObj->setLogin($login);
        $userObj->setPasswd(ilUtil::generatePasswords(1)[0], IL_PASSWD_PLAIN);
        $userObj->setLanguage($DIC->language()->getLangKey());
        $userObj->setAuthMode('shibboleth');

        // can be used in test platform for limited access
        if (ilCust::get('shib_create_limited')) {
            $userObj->setTimeLimitUnlimited(0);
            $userObj->setTimeLimitFrom(time() - 10);
            $userObj->setTimeLimitUntil($DIC->fau()->tools()->dbDateToUnix(ilCust::get('shib_create_limited')));
        } else {
            $userObj->setTimeLimitUnlimited(1);
            $userObj->setTimeLimitFrom(time());
            $userObj->setTimeLimitUntil(time());
        }
        $userObj->setActive(1, 6);
        $userObj->setTimeLimitOwner(7);
        $userObj->saveAsNew();

        // apply the IDM data and update the user
        $DIC->fau()->sync()->idm()->applyIdentityToUser($this->identity, $userObj, true);

        // write the preferences
        $userObj->setPref('hits_per_page', $DIC->settings()->get('hits_per_page'));
        $userObj->setPref('show_users_online', $DIC->settings()->get('show_users_online', 'y'));
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
        global $DIC;

        $userObj = new ilObjUser($user_id);
        $login = $userObj->getLogin();

        // set account to standard sso, if possible
        if (strpos($login, 'user.') === 0 or        // rewrite local dummy users
                strpos($login, 'vhb.') === 0 or     // rewrite vhb users
                strpos($login, '.') === false       // keep firstname.lastname
            ) {

            if ($login != $this->identity->getPkPersistentId()) {
                $userObj->updateLogin($this->identity->getPkPersistentId());
            }

            // set the authentication mode to shibboleth
            // this will cause the profile fields to be updated in applyIdentityToUser
            $userObj->setAuthMode("shibboleth");
        }

        // reset agreement to force a new acceptance if user is not active
        if (!$userObj->getActive() || !$userObj->checkTimeLimit()) {
            $userObj->setAgreeDate(null);
        }

        // activate an inactive or timed out account via shibboleth
        // it is assumed that all users coming from shibboleth are allowed to access studon
        $userObj->setActive(1, 6);
        $userObj->setTimeLimitUnlimited(true);
        $userObj->setTimeLimitOwner(7);

        // apply the IDM data and update the user
        $DIC->fau()->sync()->idm()->applyIdentityToUser($this->identity, $userObj, false);

        return $userObj;
    }


    /**
     *  Optionally log the request data for specific accounts
     */
    protected function debugLogin()
    {
        if ($log_accounts = ilCust::get('shib_log_accounts')) {
            $log_accounts = explode(',', $log_accounts);
            foreach ($log_accounts as $log_account) {
                if ($this->uid == trim($log_account)) {
                    require_once "include/inc.debug.php";
                    log_request();
                    log_server();
                }
            }
        }
    }

    /**
     * Fetch the idm data either from database or from shibboleth attributes
     * @param   string  $a_uid     user id to be used (optional)
     */
    protected function fetchIdentity($a_uid = '')
    {
        global $DIC;
        $this->identity = $DIC->fau()->staging()->repo()->getIdentity(empty($a_uid) ? $this->uid : $a_uid);
        if (!isset($this->identity)) {
            $this->identity = Identity::model();
        }
    }
}
