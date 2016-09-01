<?php
/**
 * StudOn web server status check
 *
 * Define here all necessary status checks
 * All checks are applied if status.php is called without parameter
 * A single check can be called with status.php?check=check_name
 * 
 * Complete success will produce HTTP status 200
 * One failure will produce HTTP status 503
 * Check results will be added as html page
 *  
 * @author <fred.neumann@fim.uni-erlangen.de>
 */
$studon_checks = array
(	
	'studon_mysql' 	=> array 
	(
		'type' 		=> 'mysql',
		'server' 	=> '131.188.13.50',
		'port'		=> '3306',
		'username' 	=> 'ilias',
		'password' 	=> 'agamemnon',
		'database' 	=> 'studon_dev' 		// optional
	),

	'studon_nfs' 	=> array 
	(
		'type' 		=> 'file',
		'file'		=> '/nfs/iliasdata',
		'check'		=> 'readable'			// exists, readable, writable
	)
);


/**
 * Execution
 * 
 * Adapt the include path on the target servers
 */
require_once('./classes/class.ilServerStatusCheck.php');
$check_obj = new ilServerStatusCheck($studon_checks);
$check_obj->execute($_GET['check']);
$check_obj->sendResultPage();

?>