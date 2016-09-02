<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */



include_once 'Services/WebServices/SOAP/classes/class.ilSoapClient.php';

/**
* fim: [campus] Client to call a my campus installation
*
* @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
* @version $Id: $
*/
class ilMyCampusClient extends ilSoapClient
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

		$this->user = $ilCust->getSetting('mycampus_soap_user');
		$this->password = $ilCust->getSetting('mycampus_soap_password');
		$this->client_id = $ilCust->getSetting('mycampus_soap_client');

		$this->ilSoapClient($ilCust->getSetting('mycampus_soap_url'));
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


	/** 
	 * get the participants of a lecture
	 * @param string  univis_id
	 */
	function getParticipants($a_univis_id)
	{
		return $this->call('getParticipants',array($this->sid, $a_univis_id));
	}
}
		
?>