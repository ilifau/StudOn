<?php
/* fau: idmData - new class for connection to idm database. */

/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* MySQL Wrapper for a connection to the idm database
*
* This class extends the main ILIAS database wrapper ilDB.
*/
class ilDBIdm extends ilDBPdoMySQLInnoDB
{

	/** @var  ilDBIdm $instance */
	private static $instance;


	/**
	 * Get the idm database connection instance
	 * @return ilDBIdm | null
	 */
	public static function getInstance()
	{
		try
		{
			if (!ilCust::get('idm_host'))
			{
				return null;
			}

			if (!isset(self::$instance))
			{
				$instance = new ilDBIdm;

				$instance->setDBHost(ilCust::get('idm_host'));
				$instance->setDBPort(ilCust::get('idm_port'));
				$instance->setDBUser(ilCust::get('idm_user'));
				$instance->setDBPassword(ilCust::get('idm_pass'));
				$instance->setDBName(ilCust::get('idm_name'));
				if (!$instance->connect(true))
				{
					return null;
				}
				self::$instance = $instance;
			}

			return self::$instance;
		}
		catch (Exception $e)
		{
			return null;
		}
	}

	/**
	 * Connect
	 * set the parameter 'new_link' (allowed by patch in PEAR:MDB2)
	 * don't set the parameter 'use transactions'
	 */
	function doConnect()
	{
		$this->db = MDB2::factory($this->getDSN(), array("new_link" => true));
	}
}
