<?php
/* fim: [webform] new script. */

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

chdir("../..");
define("ILIAS_MODULE", "Services/WebForm");

// Define the cookie path to prevent a different session created 
// (see ilInitialisation::setCookieParams() for details)
$GLOBALS['COOKIE_PATH'] = substr($_SERVER['PHP_SELF'], 0,
						  strpos($_SERVER['PHP_SELF'], "/Services/WebForm/"));

require_once("Services/Init/classes/class.ilInitialisation.php");
ilInitialisation::initILIAS();

require_once "./Services/WebForm/classes/class.ilWebFormSenderGUI.php";
$fs = new ilWebFormSenderGUI();
$fs->execute();
?>
