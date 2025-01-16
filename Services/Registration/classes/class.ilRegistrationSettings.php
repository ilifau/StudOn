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
 * Class ilObjAuthSettingsGUI
 * @author  Stefan Meyer <smeyer.ilias@gmx.de>
 * @ingroup ServicesRegistration
 */
class ilRegistrationSettings
{
    public const ERR_UNKNOWN_RCP = 1;
    public const ERR_MISSING_RCP = 2;

    public const REG_HASH_LIFETIME_MIN_VALUE = 60;

    public const IL_REG_DISABLED = 1;
    public const IL_REG_DIRECT = 2;
    public const IL_REG_APPROVE = 3;
    public const IL_REG_ACTIVATION = 4;
    public const IL_REG_CODES = 5;
    public const IL_REG_ROLE_UNDEFINED = 0;
    public const IL_REG_ROLES_FIXED = 1;
    public const IL_REG_ROLES_EMAIL = 2;
    public const IL_REG_ERROR_UNKNOWN = 1;
    public const IL_REG_ERROR_NO_PERM = 2;
    // fau: regCodes - new constants for login generation
    public const LOGIN_GEN_MANUAL = 'manual';
    public const LOGIN_GEN_FIRST_LASTNAME = 'firstlastname';
    public const LOGIN_GEN_GUEST_LISTENER = 'guestlistener';
    public const LOGIN_GEN_GUEST_SELFREG = 'guestselfreg';
    // fau.

    // fau: regCodes - new constants for password generation
    public const PW_GEN_MANUAL = 0;
    public const PW_GEN_AUTO = 1;
    public const PW_GEN_LOGIN = 2;
    // fau.

    // fau: regCodes - variable for code object
    protected ilRegistrationCode $codeObj;
    // fau.
    private int $registration_type;
    private bool $password_generation_enabled = false;
    private bool $access_limitation = false;
    private string $approve_recipient_logins = '';
    private array $approve_recipient_ids = [];
    private int $role_type = self::IL_REG_ROLE_UNDEFINED;
    private array $unknown = [];
    private int $reg_hash_life_time = 0;
    private bool $reg_allow_codes = false;
    private array $allowed_domains = [];

    protected ilSetting $settings;

    public function __construct(ilSetting $settings = null)
    {
        global $DIC;

        if (null == $settings) {
            $settings = $DIC->settings();
        }

        $this->settings = $settings;
        $this->read();
    }

    // fau: regCodes - new function getInstance
    public static function getInstance()
    {
        /** @var ilRegistrationSettings $instance */
        static $instance;

        if (!isset($instance)) {
            $instance = new ilRegistrationSettings();
        }
        return $instance;
    }
    // fau.

    // fau: regCodes - new function setCodeObject
    /**
     * Inject a registration code object
     * The settings of the will override the generalsettings
     *
     * @param ilRegistrationCode $codeObj
     */
    public function setCodeObject(ilRegistrationCode $codeObj)
    {
        $this->codeObj = $codeObj;
    }
    // fau.
    
    // fau: regCodes - new function getLoginGenerationTypes
    /**
     * Get a list of selectable login generation types
     * @return array
     */
    public static function getLoginGenerationTypes()
    {
        global $lng;
        $lng->loadLanguageModule('registration');

        return array(
            self::LOGIN_GEN_MANUAL => $lng->txt('reg_login_gen_manual'),
            self::LOGIN_GEN_FIRST_LASTNAME => $lng->txt('reg_login_gen_first_lastname'),
            self::LOGIN_GEN_GUEST_SELFREG => $lng->txt('reg_login_gen_guest_selfreg'),
            self::LOGIN_GEN_GUEST_LISTENER => $lng->txt('reg_login_gen_guest_listener')
        );
    }
    // fau.
    
    // fau: regCodes - new function loginGenerationType
    /**
     * Get the login generation type (with or without code)
     * @return string
     */
    public function loginGenerationType()
    {
        if (isset($this->codeObj)) {
            return $this->codeObj->login_generation_type;
        } else {
            return self::LOGIN_GEN_MANUAL;
        }
    }
    // fau.

    public function getRegistrationType(): int
    {
        return $this->registration_type;
    }

    public function setRegistrationType(int $a_type): void
    {
        $this->registration_type = $a_type;
    }

    public static function _lookupRegistrationType(): int
    {
        global $DIC;

        $ilSetting = $DIC['ilSetting'];
        $ret = (int) $ilSetting->get('new_registration_type', (string) self::IL_REG_DISABLED);

        if ($ret < 1 || $ret > 5) {
            //data is corrupted and should be processed like "No Registration possible" (#18261)
            $ret = self::IL_REG_DISABLED;
        }
        return $ret;
    }

    public function enabled(): bool
    {
        return $this->registration_type !== self::IL_REG_DISABLED;
    }

    public function directEnabled(): bool
    {
        return $this->registration_type === self::IL_REG_DIRECT;
    }

    public function approveEnabled(): bool
    {
        return $this->registration_type === self::IL_REG_APPROVE;
    }

    public function activationEnabled(): bool
    {
        // fau: regCodes - take code settings for email verification with precedence
        if (isset($this->codeObj)) {
            return $this->codeObj->email_verification;
        }
        // fau.        
        return $this->registration_type === self::IL_REG_ACTIVATION;
    }

    public function registrationCodeRequired(): bool
    {
        return $this->registration_type === self::IL_REG_CODES;
    }

    public function passwordGenerationEnabled(): bool
    {
        // fau: regCodes - take code settings for password generation with precedence
        if (isset($this->codeObj)) {
            return ($this->codeObj->password_generation == self::PW_GEN_AUTO);
        }
        // fau.
        return $this->password_generation_enabled;
    }

    public function setPasswordGenerationStatus(bool $a_status): void
    {
        $this->password_generation_enabled = $a_status;
    }

    public function getAccessLimitation(): bool
    {
        return $this->access_limitation;
    }

    // fau: regCodes - new function passwordGenerationType()
    public function passwordGenerationType()
    {
        if (isset($this->codeObj)) {
            return ($this->codeObj->password_generation);
        }
        return ($this->password_generation_enabled ? self::PW_GEN_AUTO : self::PW_GEN_MANUAL);
    }
    // fau.


    // fau: regCodes - new function getPasswordGenerationTypes
    /**
     * Get a list of selectable password generation types
     * @return array
     */
    public static function getPasswordGenerationTypes()
    {
        global $lng;
        $lng->loadLanguageModule('registration');

        return array(
            self::PW_GEN_MANUAL => $lng->txt('reg_pw_gen_manual'),
            self::PW_GEN_AUTO => $lng->txt('reg_pw_gen_auto'),
            // self::PW_GEN_LOGIN => $lng->txt('reg_pw_gen_login'),
        );
    }
    // fau.

    public function setAccessLimitation(bool $a_access_limitation): void
    {
        $this->access_limitation = $a_access_limitation;
    }

    public function setApproveRecipientLogins(string $a_rec_string): void
    {
        $this->approve_recipient_logins = $a_rec_string;
        $this->approve_recipient_ids = [];

        // convert logins to array of ids
        foreach (explode(',', trim($this->approve_recipient_logins)) as $login) {
            if ($uid = ilObjUser::_lookupId(trim($login))) {
                $this->approve_recipient_ids[] = $uid;
            }
        }
    }

    public function getApproveRecipientLogins(): string
    {
        return $this->approve_recipient_logins;
    }

    public function getApproveRecipients(): array
    {
        // fau: regCodes - take the code settings for approve recipients with precedence
        if (isset($this->codeObj)) {
            return $this->codeObj->notification_users;
        }
        // fau.
        return $this->approve_recipient_ids;
    }

    public function getUnknown(): string
    {
        return implode(',', $this->unknown);
    }

    public function roleSelectionEnabled(): bool
    {
        return $this->role_type === self::IL_REG_ROLES_FIXED;
    }

    public function automaticRoleAssignmentEnabled(): bool
    {
        return $this->role_type === self::IL_REG_ROLES_EMAIL;
    }

    public function setRoleType(int $a_type): void
    {
        $this->role_type = $a_type;
    }

    public function setRegistrationHashLifetime(int $a_lifetime): self
    {
        $this->reg_hash_life_time = $a_lifetime;
        return $this;
    }

    public function getRegistrationHashLifetime(): int
    {
        // fau: regCodes - take the code settings for approve recipients with precedence
        if (isset($this->codeObj)) {
            return max($this->codeObj->email_verification_time, self::REG_HASH_LIFETIME_MIN_VALUE);
        }
        // fau.

        return max($this->reg_hash_life_time, self::REG_HASH_LIFETIME_MIN_VALUE);
    }

    public function setAllowCodes(bool $a_allow_codes): self
    {
        $this->reg_allow_codes = $a_allow_codes;

        return $this;
    }

    public function getAllowCodes(): bool
    {
        return $this->reg_allow_codes;
    }

    public function setAllowedDomains(string $a_value): void
    {
        $a_value = array_map(
            static function (string $value): string {
                return trim($value);
            },
            explode(";", trim($a_value))
        );

        $this->allowed_domains = $a_value;
    }

    public function getAllowedDomains(): array
    {
        // fau: regCodes - don't restrict email domains with a code
        if (isset($this->codeObj)) {
            return array();
        }
        // fau.        
        return $this->allowed_domains;
    }

    public function validate(): int
    {
        $this->unknown = [];

        $login_arr = explode(',', $this->getApproveRecipientLogins());
        $login_arr = $login_arr ?: [];
        $valid = [];
        foreach ($login_arr as $recipient) {
            if (!$recipient = trim($recipient)) {
                continue;
            }
            if (!ilObjUser::_lookupId($recipient)) {
                $this->unknown[] = $recipient;
            } else {
                $valid[] = $recipient;
            }
        }
        if (count($this->unknown)) {
            return self::ERR_UNKNOWN_RCP;
        }
        if ($this->getRegistrationType() === self::IL_REG_APPROVE && !count($valid)) {
            return self::ERR_MISSING_RCP;
        }
        return 0;
    }

    public function save(): bool
    {
        $this->settings->set('reg_role_assignment', (string) $this->role_type);
        $this->settings->set('new_registration_type', (string) $this->registration_type);
        $this->settings->set('passwd_reg_auto_generate', (string) $this->password_generation_enabled);
        $this->settings->set('approve_recipient', addslashes(serialize($this->approve_recipient_ids)));
        $this->settings->set('reg_access_limitation', (string) $this->access_limitation);
        $this->settings->set('reg_hash_life_time', (string) $this->reg_hash_life_time);
        $this->settings->set('reg_allow_codes', (string) $this->reg_allow_codes);
        $this->settings->set('reg_allowed_domains', implode(';', $this->allowed_domains));
        return true;
    }

    private function read(): void
    {
        //static method validates value
        $this->registration_type = self::_lookupRegistrationType();

        $this->role_type = (int) $this->settings->get('reg_role_assignment', '1');
        $this->password_generation_enabled = (bool) $this->settings->get('passwd_reg_auto_generate');
        $this->access_limitation = (bool) $this->settings->get('reg_access_limitation');
        $this->reg_hash_life_time = (int) $this->settings->get('reg_hash_life_time');
        $this->reg_allow_codes = (bool) $this->settings->get('reg_allow_codes');

        $this->approve_recipient_ids = unserialize(
            stripslashes($this->settings->get('approve_recipient', serialize([]))),
            ['allowed_classes' => false]
        ) ?: [];

        // create login array
        $tmp_logins = [];
        foreach ($this->approve_recipient_ids as $id) {
            if ($login = ilObjUser::_lookupLogin((int) $id)) {
                $tmp_logins[] = $login;
            }
        }
        $this->approve_recipient_logins = implode(',', $tmp_logins);
        $this->setAllowedDomains((string) $this->settings->get('reg_allowed_domains'));
    }
}
