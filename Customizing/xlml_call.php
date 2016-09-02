<?php
/* fim: [xlml] call xlml pages and perception assessments. */ 

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* Call external software
*
* @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
* @version $Id$
*
* @package fim
*/

define("ILIAS_MODULE", "content");
chdir("..");

require_once "include/inc.header.php";

switch ($_REQUEST["call"])
{
	case "perception":
	    
	    $call = "ilias.pip";
	    $name = $ilUser->login;
	    $group = $ilias->getClientId();
	    $session = $_REQUEST["id"];
		$key = "fn70";
		
	    $temp = $call.$name.$group.$session.$key;
		$sum = 0;
	    for ($i = 0; $i < strlen($temp); $i++)
	    {
			$sum = $sum + ord(substr($temp,$i,1));
		}
		$access = $sum;
		
		//$url = ILIAS_HTTP_PATH. "/fim/wartung.html";

		
		$url = "http://codd.fim.uni-erlangen.de/q-dev/session.dll"
		    . "?CALL=". $call
		    . "&NAME=". $name
		    . "&GROUP=". $group
		    . "&SESSION=". $session
		    . "&ACCESS=". $access;
		
		ilUtil::redirect($url);
		
	    break;
	    
	case "xlml":
		$project = $_REQUEST["project"];
		$namespace = $_REQUEST["namespace"];
		$type = $_REQUEST["type"];
		$id = $_REQUEST["id"];
		
		if ($project)
		{
			$import_id = $project . "/" . $namespace;
	   	}
	   	else
	   	{
	   		$import_id = $namespace;
		}
	    
	    $q = "SELECT o.obj_id, r.ref_id "
			."FROM object_data o, object_reference r "
	        ."WHERE o.obj_id = r.obj_id "
			."AND o.import_id = ". $ilDB->quote($import_id);
		$result = $ilDB->query($q);
		
		while ($row = $result->fetchRow(DB_FETCHMODE_ASSOC))
		{
			if ($ilAccess->checkAccess("read", "view", $row["ref_id"]))
			{
				$lm_obj_id = $row["obj_id"];
				break;
			}
		}
		if (!$lm_obj_id)
		{
			$tpl->addBlockFile("CONTENT", "message", "tpl.message.html");
			$tpl->setCurrentBlock("message");
			$tpl->setVariable("INFO","Die Lerneinheit '". $import_id. " 'wurde nicht gefunden!");
			$tpl->parseCurrentBlock();
			$tpl->show();
			exit;
		}

		$url = ILIAS_HTTP_PATH
			."/".ILIAS_WEB_DIR
			."/".CLIENT_ID
			."/lm_data/lm_".$lm_obj_id
			."/_autogen/linkResolver.html"
			."?namespace=$namespace&type=$type&id=$id";
		ilUtil::redirect($url);
		
		break;

	default:
		exit();
}

