<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * fim: [cust] Class for server status checks.
 * 
 * Currently support checks for mysql and file connections
 * Works independently from ILIAS and may be included anywhere
 *
 * @author	Fred Neumann <fred.neumann@fim.uni-erlangen.de>
 * @version $Id: $
*/
class ilServerStatusCheck
{
	/**
	 * Check definitions
	 * 
	 * The defined entries are just examples
	 * The actual configuration will be passed in the constructor
	 * 
	 * @var	array		nested array: 	name => (setting => string)
	 */
	private $checks = array 
	(
		'mysql_demo' 	=> array 
		(
			'type' 		=> 'mysql',
			'server' 	=> 'db.ilias.de',
			'port'		=> '3306',
			'username' 	=> 'testuser',
			'password' 	=> 'testpass',
			'database' 	=> 'ilias' 				// optional
		),
	
		'nfs_demo' 	=> array 
		(
			'type' 		=> 'file',
			'file'		=> '/nfs/iliasdata',
			'check'		=> 'readable'			// exists, readable, writable
		)
	);
		
	
	/**
	 * Results of the checks
	 * @var array		nested array: 	name => ('status' => boolean, 'message' => string)
	 */
	private $results = array();
	
	
	/**
	 * Constructor
	 @var	array		check definitions
	 */
	public function __construct($a_checks = array())
	{
		$this->checks = $a_checks;
	}
	
	/**
	 *  Executes one or all defined checks
	 *  and stores the results
	 *  
	 * @param 	string 		name of a single check to be applied (otherwise all)
	 */
	public function execute($a_name = '')
	{
		if ($a_name != '')
		{
			$this->results[$a_name] = $this->applyCheck($this->checks[$a_name]);
		}
		else
		{
			foreach ($this->checks as $name => $check)
			{
				$this->results[$name]= $this->applyCheck($check);
			}
		}
	}
	
	
	/**
	 * Send a page with the check results
	 * If all results are ok, the HTTP status code 200 (OK) is sent
	 * If one check failed, the the HTTP status code 503 (Service Unavailable) is sent
	 */
	public function sendResultPage()
	{
		$ok = true;
		$page = "<html>\n<body>\n<pre>\n";

		foreach ($this->results as $name => $result)
		{
			if ($result['status'])
			{
				$page .= $name . "\tOK\t". $result['message'] . "\n";
			}
			else
			{
				$ok = false;
				$page .= $name . "\tFAILED\t". $result['message'] . "\n";
			}
		}
		$page .= "</pre>\n</body>\n</html>\n";
		
		if ($ok)
		{
			header("HTTP/1.0 200 OK");
		}
		else
		{
			header("HTTP/1.0 503 Service Unavailable");	
		}
		
		echo $page;
		exit;
	}
		
	
	/**
	 * Apply a single check
	 * 
	 * @param 	array	configuration	(setting => string)
	 * @return	array	result 			('status' => boolean, 'message' => string)
	 */
	private function applyCheck($a_check)
	{
		switch ($a_check['type'])
		{
			case 'mysql':
				return $this->checkMySQL($a_check);
				
			case 'file':
				return $this->checkFile($a_check);
				
			default:
				return array ('status' => false, 'message' => 'unknown type '.$a_check['type']);
		}
	}
	
	
	/**
	 * Check a mysql connection
	 * 
	 * @param 	array	configuration	(setting => string)
	 * @return	array	result 			('status' => boolean, 'message' => string)
	 */
	private function checkMySQL($a_check)
	{
		// check connection
		$server = $a_check['server'] . ($a_check['port'] ? ':'. $a_check['port'] : '');		
		$conn = @mysql_connect($server, $a_check['username'], $a_check['password']);
		$message = @mysql_error();
		if (!$conn)
		{
			return array('status' => false, 'message' => $message);
		}
		
		// check database
		$ok = @mysql_select_db($a_check['database'], $conn);
		$message = @mysql_error();
		if (!$ok)
		{
			mysql_close($conn);
			return array('status' => false, 'message' => $message);
		}
		
		mysql_close($conn);		
		return array('status' => true, 'message' => '');
	}
	
	/**
	 * Check a file
	 * 
	 * @param 	array	configuration	(setting => string)
	 * @return	array	result 			('status' => boolean, 'message' => string)
	 */
	private function checkFile($a_check)
	{
		switch ($a_check['check'])
		{
			case 'readable':
				$ok = @is_readable($a_check['file']);
				$message = $a_check['file'] . ' not readable';
				break;
				
			case 'writable':	
				$ok = @is_writable($a_check['file']);
				$message = $a_check['file'] . ' not writable';
				break;

			case 'exists':
			default:
				$ok = @file_exists($a_check['file']);
				$message = $a_check['file'] . ' missing';
				break;
		}
		
		if (!$ok)
		{
			return array('status' => false, 'message' => $message);
		}
		else
		{
			return array('status' => true, 'message' => '');
		}
	}
}

?>
