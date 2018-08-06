<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * fim: [univis] Handle a request from UnivIS
 *
 * @author	Fred Neumann <fred.neumann@fim.uni-erlangen.de>
 * @version $Id: $
 */

// fau: httpPath - use defined http path for univis
$GLOBALS['USE_ILIAS_HTTP_PATH_FROM_INI'] = true;
// fau.

/**
 * Do a minimalized initialisation like for web feeds
 * @see	feed.php
 */
if (isset($_GET["client_id"]))
{
	$cookie_domain = $_SERVER['SERVER_NAME'];
	$cookie_path = dirname( $_SERVER['PHP_SELF'] );

	/* if ilias is called directly within the docroot $cookie_path
	is set to '/' expecting on servers running under windows..
	here it is set to '\'.
	in both cases a further '/' won't be appended due to the following regex
	*/
	$cookie_path .= (!preg_match("/[\/|\\\\]$/", $cookie_path)) ? "/" : "";
		
	if($cookie_path == "\\") $cookie_path = '/';
	
	$cookie_domain = ''; // Temporary Fix
	
	setcookie("ilClientId", $_GET["client_id"], 0, $cookie_path, $cookie_domain);
	
	$_COOKIE["ilClientId"] = $_GET["client_id"];
}

include_once "Services/Context/classes/class.ilContext.php";
ilContext::init(ilContext::CONTEXT_RSS);

require_once("Services/Init/classes/class.ilInitialisation.php");
ilInitialisation::initILIAS();


/**
 * Handle the request by univis request class
 */
require_once("Services/UnivIS/classes/class.ilUnivisRequest.php");
$request = new ilUnivisRequest();
$request->execute(); 

?>