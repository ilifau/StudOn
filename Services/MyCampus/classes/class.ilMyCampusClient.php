<?php
// fau: campusSub - new class ilMyCampusClient.


/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once 'Services/WebServices/SOAP/classes/class.ilSoapClient.php';

/**
 * Client to call a my campus installation
 */
class ilMyCampusClient extends ilSoapClient
{
    private static $instance;

    public $user;
    public $password;
    public $client_id;
    public $sid = false;

    /**
     * singleton method
     * @return self
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
        $this->user = ilCust::get('mycampus_soap_user');
        $this->password = ilCust::get('mycampus_soap_password');
        $this->client_id = ilCust::get('mycampus_soap_client');

        parent::__construct(ilCust::get('mycampus_soap_url'));
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

    /**
     * Get an eror message from the nuSoap client
     * @return string
     */
    public function getClientError()
    {
        if (isset($this->client)) {
            return $this->client->getError();
        } else {
            return "";
        }
    }

    /**
     * get the participants of a lecture
     * @param string  univis_id
     */
    public function getParticipants($a_univis_id)
    {
        return $this->call('getParticipants', array($this->sid, $a_univis_id));
    }
}
