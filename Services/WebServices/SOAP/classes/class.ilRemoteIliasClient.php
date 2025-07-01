<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

// fau: sendSimpleResults - new SOAP client class to call a remote ILIAS installation.

include_once 'Services/WebServices/SOAP/classes/class.ilSoapClient.php';

/**
* Client to call a remote ILIAS installation
*
* For calling function in StudOn main installation
* from the StudOn Exam installation (e.g. sending mails)
*
* @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
* @version $Id: $
*/
class ilRemoteIliasClient extends ilSoapClient
{
    private static $instance;

    public $user;
    public $password;
    public $client_id;
    public $sid = false;

    /**
    * singleton method
    */
    public static function _getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
    * private constructor
    */
    public function __construct($a_uri = '')
    {
        $this->user = ilCust::get('remote_soap_user');
        $this->password = ilCust::get('remote_soap_password');
        $this->client_id = ilCust::get('remote_soap_client_id');

        parent::__construct(ilCust::get('remote_soap_server'));
    }


    /**
    * login to remote service
    *
    * @return mixed		soap session id or false
    */
    public function login()
    {
        //already logged in
        if ($this->sid) {
            return $this->sid;
        }

        // init soap client
        if (!$this->init()) {
            return false;
        }

        // login to soap server
        $this->sid = $this->call('login', array($this->client_id, $this->user, $this->password));

        return $this->sid;
    }

    /**
    * logout from remote service
    */
    public function logout()
    {
        if ($this->call('logout', array($this->sid)));
        {
            $this->sid = false;
        }
    }
}
