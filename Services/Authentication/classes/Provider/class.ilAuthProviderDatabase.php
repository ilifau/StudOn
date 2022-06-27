<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once './Services/Authentication/classes/Provider/class.ilAuthProvider.php';
include_once './Services/Authentication/interfaces/interface.ilAuthProviderInterface.php';

/**
 * Description of class class
 *
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 *
 */
class ilAuthProviderDatabase extends ilAuthProvider implements ilAuthProviderInterface
{

    
    /**
     * Do authentication
     * @return bool
     */
    public function doAuthentication(ilAuthStatus $status)
    {
        include_once './Services/User/classes/class.ilUserPasswordManager.php';

        /**
         * @var $user ilObjUser
         */
        $user = ilObjectFactory::getInstanceByObjId(ilObjUser::_loginExists($this->getCredentials()->getUsername()), false);

        $this->getLogger()->debug('Trying to authenticate user: ' . $this->getCredentials()->getUsername());
        if ($user instanceof ilObjUser) {
            if ($user->getId() == ANONYMOUS_USER_ID) {
                $this->getLogger()->notice('Failed authentication for anonymous user id. ');
                $this->handleAuthenticationFail($status, 'err_wrong_login');
                return false;
            }
            if (!ilAuthUtils::isLocalPasswordEnabledForAuthMode($user->getAuthMode(true))) {
                $this->getLogger()->debug('DB authentication failed: current user auth mode does not allow local validation.');
                $this->getLogger()->debug('User auth mode: ' . $user->getAuthMode(true));
                $this->handleAuthenticationFail($status, 'err_wrong_login');
                return false;
            }
            if (ilUserPasswordManager::getInstance()->verifyPassword($user, $this->getCredentials()->getPassword())) {
                $this->getLogger()->debug('Successfully authenticated user: ' . $this->getCredentials()->getUsername());
                $status->setStatus(ilAuthStatus::STATUS_AUTHENTICATED);
                $status->setAuthenticatedUserId($user->getId());
                return true;
            }
        }

        // fau: loginFallback - try local login with external account
        if (ilCust::get('local_auth_external')) {
            $login = false;
            $login = ($login ? $login : ilObjUser::_checkExternalAuthAccount('local', $this->getCredentials()->getUsername()));
            $login = ($login ? $login : ilObjUser::_checkExternalAuthAccount('default', $this->getCredentials()->getUsername()));
            $login = ($login ? $login : ilObjUser::_checkExternalAuthAccount('shibboleth', $this->getCredentials()->getUsername()));
            if ($login) {
                $user = ilObjectFactory::getInstanceByObjId(ilObjUser::_lookupId($login), false);

                $this->getLogger()->debug('Trying to authenticate user with ext_account: ' . $login);
                if ($user instanceof ilObjUser) {
                    if (ilUserPasswordManager::getInstance()->verifyPassword($user, $this->getCredentials()->getPassword())) {
                        $this->getLogger()->debug('Successfully authenticated user: ' . $user->getLogin());
                        $status->setStatus(ilAuthStatus::STATUS_AUTHENTICATED);
                        $status->setAuthenticatedUserId($user->getId());
                        return true;
                    }
                }
            }
        }
        // fau.

        // fau: loginFallback - try for login with matriculation as password
        if (ilCust::get('local_auth_matriculation') && $this->getCredentials()->getPassword() != '') {
            // take the user that is already fount
            if ($user instanceof ilObjUser) {
                $this->getLogger()->debug('Trying to authenticate with matriculation as password for: ' . $user->getLogin());
                if ($user->getMatriculation() == $this->getCredentials()->getPassword()) {
                    $this->getLogger()->debug('Successfully authenticated user: ' . $user->getLogin());
                    $status->setStatus(ilAuthStatus::STATUS_AUTHENTICATED);
                    $status->setAuthenticatedUserId($user->getId());
                    return true;
                }
            }
        }
        // fau.

        // fau: loginFallback - check password from a remote account with same login
        if (ilCust::get('local_auth_remote')) {
            // take the user that is already found
            if ($user instanceof ilObjUser) {
                $this->getLogger()->debug('Trying to authenticate with remote account: ' . $user->getLogin());
                require_once('Services/Authentication/classes/Provider/class.ilRemoteAuthDB.php');
                $db = ilRemoteAuthDB::getInstance();
                if (!isset($db)) {
                    $this->getLogger()->debug('remote db not connected');
                }
                else {
                    $remoteUser = $db->getRemoteUser($user->getLogin());
                    if (!isset($remoteUser)) {
                        $this->getLogger()->debug('remote user not found');
                    }
                    elseif (ilUserPasswordManager::getInstance()->verifyPassword($remoteUser, $this->getCredentials()->getPassword())) {
                        $this->getLogger()->debug('Successfully authenticated remote user: ' . $user->getLogin());
                        $status->setStatus(ilAuthStatus::STATUS_AUTHENTICATED);
                        $status->setAuthenticatedUserId($user->getId());
                        return true;
                    }

                }
            }
        }
        // fau.


        // fau: loginFallback - check password from an idm account
        // fau: userData - check password from an idm account
        if (ilCust::get('local_auth_idm')) {
            // take the user that is already found
            if ($user instanceof ilObjUser) {
                $this->getLogger()->debug('Trying to authenticate with idm account: ' . $user->getExternalAccount());

                global $DIC;
                if (empty($identity = $DIC->fau()->staging()->repo()->getIdentity($user->getExternalAccount()))) {
                    $this->getLogger()->debug('idm data not found');
                }
                else {
                    $idmUser = $DIC->fau()->sync()->idm()->getDummyUserForLocalAuth($identity);
                    if (ilUserPasswordManager::getInstance()->verifyPassword($idmUser, $this->getCredentials()->getPassword())) {
                        $this->getLogger()->debug('Successfully authenticated idm user: ' . $user->getExternalAccount());
                        $status->setStatus(ilAuthStatus::STATUS_AUTHENTICATED);
                        $status->setAuthenticatedUserId($user->getId());
                        return true;
                    }
                }
            }
        }
        // fau.


        $this->handleAuthenticationFail($status, 'err_wrong_login');
        return false;
    }
}
