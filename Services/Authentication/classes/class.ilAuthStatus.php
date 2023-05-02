<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Auth status implementation
 *
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 *
 */
class ilAuthStatus
{
    private static $instance = null;
    
    private $logger = null;
    
    const STATUS_UNDEFINED = 1;
    const STATUS_AUTHENTICATED = 2;
    const STATUS_AUTHENTICATION_FAILED = 3;
    const STATUS_ACCOUNT_MIGRATION_REQUIRED = 4;
    const STATUS_CODE_ACTIVATION_REQUIRED = 5;

    const STATUS_SSO_CHANGE_REQUIRED = 550;
    
    private $status = self::STATUS_UNDEFINED;
    private $reason = '';
    private $translated_reason = '';
    private $auth_user_id = 0;

    private $sso_change_identity = '';
    private $sso_change_logins = [];
    private $sso_change_selected_login = '';
    
    /**
     * Constructor
     */
    private function __construct()
    {
        $this->logger = ilLoggerFactory::getLogger('auth');
    }
    
    /**
     * Get status instance
     * @return \ilAuthStatus
     */
    public static function getInstance()
    {
        if (self::$instance) {
            return self::$instance;
        }
        return self::$instance = new self();
    }
    
    /**
     * Get logger
     * @return \ilLogger
     */
    protected function getLogger()
    {
        return $this->logger;
    }
    
    /**
     * Set auth status
     * @param int $a_status
     */
    public function setStatus($a_status)
    {
        $this->status = $a_status;
    }
    
    /**
     * Get status
     * @return int $status
     */
    public function getStatus()
    {
        return $this->status;
    }
    
    /**
     * Set reason
     * @param string $a_reason A laguage key, which can be translated to an end user message
     */
    public function setReason($a_reason)
    {
        $this->reason = $a_reason;
    }
    
    /**
     * Set translated reason
     * @param string $a_reason
     */
    public function setTranslatedReason($a_reason)
    {
        $this->translated_reason = $a_reason;
    }
    
    /**
     * Get reason for authentication success, fail, migration...
     * @return string
     */
    public function getReason()
    {
        return $this->reason;
    }
    
    /**
     * Get translated reason
     */
    public function getTranslatedReason()
    {
        // fau: loginFailed - add a help text to tie failure message
        if (strlen($this->translated_reason)) {
            $message = $this->translated_reason;
        }
        else {
            $message = $GLOBALS['DIC']->language()->txt($this->getReason());
        }

        return $message . $GLOBALS['DIC']->language()->txt('err_wrong_login_assist');
        // fau.
    }
    
    
    public function setAuthenticatedUserId($a_id)
    {
        $this->auth_user_id = $a_id;
    }
    
    /**
     * Get authenticated user id
     * @return int
     */
    public function getAuthenticatedUserId()
    {
        return $this->auth_user_id;
    }

    public function getSsoChangeIdentity(): string
    {
        return $this->sso_change_identity;
    }

    public function setSsoChangeIdentity(string $sso_change_identity): void
    {
        $this->sso_change_identity = $sso_change_identity;
    }

    public function getSsoChangeLogins(): array
    {
        return $this->sso_change_logins;
    }

    public function setSsoChangeLogins(array $sso_change_logins): void
    {
        $this->sso_change_logins = $sso_change_logins;
    }

    public function getSsoChangeSelectedLogin(): string
    {
        return $this->sso_change_selected_login;
    }

    public function setSsoChangeSelectedLogin(string $sso_change_selected_login): void
    {
        $this->sso_change_selected_login = $sso_change_selected_login;
    }
}
