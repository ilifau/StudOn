<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

use FAU\Staging\Data\Identity;

/**
 * fau: samlAuth - new class for saml authentication in studon
 * fau: samlChange - initiate and handle change requests
 */
class ilAuthProviderSamlStudOn extends ilAuthProviderSaml
{
    protected Identity $identity;

    /**
     * @inheritdoc
     */
    public function doAuthentication(\ilAuthStatus $status)
    {
        global $DIC;

        if (!is_array($this->attributes) || 0 === count($this->attributes)) {
            $this->getLogger()->warning('Could not parse any attributes from SAML response.');
            $this->handleAuthenticationFail($status, 'auth_shib_not_configured');
            return false;
        }

        try {
            // get the uid attribute
            $this->uid = $this->attributes['urn:mace:dir:attribute-def:uid'][0];

            // needed since ILIAS 5.4.10
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
                $login = $this->findLogin();
            }

            // take an already selected login fon the SSO change
            if (empty($login) && !empty($status->getSsoChangeSelectedLogin())) {
                $login = $status->getSsoChangeSelectedLogin();
            }

            // prepare the SSO change selection
            if (empty($login)) {
                $logins = $DIC->fau()->user()->repo()->findLocalLoginsByName($this->identity->getGivenName(), $this->identity->getSn());
                if (!empty($logins)) {
                    $status->setStatus(ilAuthStatus::STATUS_SSO_CHANGE_REQUIRED);
                    $status->setSsoChangeIdentity($this->identity->getPkPersistentId());
                    $status->setSsoChangeLogins($logins);
                    return false;
                }
            }

            // generate a new login
            if (empty($login)) {
                $login = $DIC->fau()->user()->generateLogin($this->identity->getPkPersistentId());
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
                    $factory = new ilSamlAuthFactory();
                    $auth = $factory->auth();
                    $auth->logout(ILIAS_HTTP_PATH . '/login.php?cmd=force_login&reason=shib_user_not_found');
                    return false;
                }

                // check the minimum attributes needed for new users
                if (empty($this->identity->getGivenName()) || empty($this->identity->getSn())) {
                    $this->getLogger()->warning('Could not create new user because firstname or lastname is missing in SAML attributes.');
                    $this->handleAuthenticationFail($status, 'shib_data_missing');
                    $factory = new ilSamlAuthFactory();
                    $auth = $factory->auth();
                    $auth->logout(ILIAS_HTTP_PATH . '/login.php?cmd=force_login&reason=shib_data_missing');
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
     * Returns the login name of a fitting account
     *
     * @return 	string 	found username or ''
     */
    protected function findLogin()
    {
        // First search for the identity as external account
        if ($login = ilObjUser::_findLoginByField('ext_account', $this->identity->getPkPersistentId())) {
            return $login;
        }

        // as a fallback search for an idle external account
        if ($login = ilObjUser::_findLoginByField('idle_ext_account', $this->identity->getPkPersistentId())) {
            return $login;
        }
        return '';
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
        $userObj->setPasswd(null);
        $userObj->setLanguage($DIC->language()->getLangKey());
        $userObj->setAuthMode('shibboleth');
        $userObj->setExternalAccount($this->identity->getPkPersistentId());

        // can be used in test platform for limited access
        if (ilCust::get('shib_create_limited')) {
            $userObj->setTimeLimitUnlimited(0);
            $userObj->setTimeLimitFrom(time() - 10);
            $userObj->setTimeLimitUntil($DIC->fau()->tools()->convert()->dbDateToUnix(ilCust::get('shib_create_limited')));
        } else {
            $userObj->setTimeLimitUnlimited(1);
            $userObj->setTimeLimitFrom(time());
            $userObj->setTimeLimitUntil(time());
        }
        $userObj->setActive(1, 6);
        $userObj->setTimeLimitOwner(7);
        $userObj->saveAsNew();

        // apply the IDM data and update the user
        $DIC->fau()->sync()->idm()->applyIdentityToUser($this->identity, $userObj);

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

        // Update the login name
        if ($login != $this->identity->getPkPersistentId() && (
            strpos($login, 'user.') === 0 or        // rewrite local dummy users
            strpos($login, 'vhb.') === 0            // rewrite vhb users
        )) {
            $userObj->updateLogin($this->identity->getPkPersistentId());
        }

        // set the authentication mode to shibboleth
        // this will cause the profile fields to be updated in applyIdentityToUser
        $userObj->setAuthMode("shibboleth");
        $userObj->setExternalAccount($this->identity->getPkPersistentId());
        $userObj->setIdleExtAccount(null);


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
        $DIC->fau()->sync()->idm()->applyIdentityToUser($this->identity, $userObj);

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
