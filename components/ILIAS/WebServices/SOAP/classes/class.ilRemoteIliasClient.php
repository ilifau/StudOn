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

use FAU\Tools\Cust;

// fau: sendSimpleResults - SOAP client to call the StudOn main installation from the exam installation
class ilRemoteIliasClient extends ilSoapClient
{
    private string $remote_user;
    private string $remote_password;
    private string $remote_client_id;
    private ?string $sid = null;

    public function __construct()
    {
        $this->remote_user = (string) Cust::get('remote_soap_user');
        $this->remote_password = (string) Cust::get('remote_soap_password');
        $this->remote_client_id = (string) Cust::get('remote_soap_client_id');

        parent::__construct((string) Cust::get('remote_soap_server'));
    }

    public static function isRemoteNotificationEnabled(): bool
    {
        return (bool) Cust::get('tst_notify_remote');
    }

    public function login(): ?string
    {
        if ($this->sid !== null) {
            return $this->sid;
        }
        if (trim($this->getServer()) === '' || !$this->init()) {
            return null;
        }
        $sid = $this->call('login', [$this->remote_client_id, $this->remote_user, $this->remote_password]);
        $this->sid = is_string($sid) && $sid !== '' ? $sid : null;
        return $this->sid;
    }

    public function logout(): void
    {
        if ($this->sid !== null) {
            $this->call('logout', [$this->sid]);
            $this->sid = null;
        }
    }

    public function sendUserMail(string $to, string $subject, string $message): bool
    {
        if ($this->sid === null) {
            return false;
        }
        return (bool) $this->call('sendUserMail', [
            $this->sid,
            $to,
            '',
            '',
            'anonymous',
            $subject,
            $message,
            '',
            'system',
            false
        ]);
    }
}
// fau.
