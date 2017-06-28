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

	var $user;
	var $password;
	var $client_id;
	var $sid = false;

	/**
	* singleton method
	*/
	public static function _getInstance()
	{
	   if (!isset(self::$instance))
		{
	       $c = __CLASS__;
	       self::$instance = new $c;
	   }

	   return self::$instance;
	}

	/**
	* private constructor
	*/
	private function __construct()
	{
		global $ilCust;

		$this->user = $ilCust->getSetting('remote_soap_user');
		$this->password = $ilCust->getSetting('remote_soap_password');
		$this->client_id = $ilCust->getSetting('remote_soap_client_id');

		$this->ilSoapClient($ilCust->getSetting('remote_soap_server'));
		$this->setTimeout(DEFAULT_TIMEOUT);
		$this->setResponseTimeout(DEFAULT_RESPONSE_TIMEOUT);
		$this->enableWSDL(true);
	}


	/**
	* login to remote service
	*
	* @return mixed		soap session id or false
	*/
	function login()
	{
		//already logged in
		if ($this->sid)
		{
	        return $this->sid;
		}

		// init soap client
		if (!$this->init())
		{
	        return false;
		}

		// login to soap server
		$this->sid = $this->call('login', array($this->client_id, $this->user, $this->password));

		return $this->sid;
	}

	/**
	* logout from remote service
	*/
	function logout()
	{
		if ($this->call('logout', array($this->sid)));
		{
	        $this->sid = false;
		}
	}
}
		
?>
