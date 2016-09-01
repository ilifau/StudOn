<?php
/* fim: [webform] new script. */

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* Goto script for web forms (deprecated)
*
* @author Fred neumann <fred.neumann@fim.uni-erlangen.de>
* @version $Id $
*/

chdir("../..");

require_once "include/inc.header.php";
require_once "Services/WebForm/classes/class.ilWebForm.php";

// possible parameters for this script
$form = new ilWebForm($_REQUEST["form_id"]);
$form->read();

switch ($_REQUEST["target"])
{
	case "start":
		$url = $form->makeLMLink();

	case "form":
		$url = $form->makeFormLink($_REQUEST["form_save_id"], $_REQUEST["form_print"]);
		break;
	
	case "form_solution":
		$url = $form->makeSolutionLink();

	default:
	    // TODO: make nicer
		header("HTTP/1.0: 404 Not Found");
		exit("Unknown command");
}
ilUtil::redirect($url);
exit;


