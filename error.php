<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

try
{
	require_once("Services/Init/classes/class.ilInitialisation.php");
	ilInitialisation::initILIAS();
	$tpl->addBlockFile("CONTENT", "content", "tpl.error.html");
	$lng->loadLanguageModule("error");
	// #13515 - link back to "system" [see ilWebAccessChecker::sendError()]
	$nd  = $tree->getNodeData(ROOT_FOLDER_ID);
// fau: rootAsLogin - changed button text to home, removed blocks
	$txt = $lng->txt('to_home');
	$tpl->SetVariable("TXT_LINK", $txt);
	$tpl->SetVariable("LINK", ilUtil::secureUrl(ILIAS_HTTP_PATH . '/ilias.php?baseClass=ilRepositoryGUI&amp;client_id=' . CLIENT_ID));
	$tpl->setVariable("ERROR_MESSAGE", ($_SESSION["failure"]));
	$tpl->setVariable("MESSAGE_HEADING", $lng->txt('error_sry_error'));
// fau.

	//$tpl->parseCurrentBlock();

	ilSession::clear("referer");
	ilSession::clear("message");
	$tpl->show();
}
catch(Exception $e)
{
	if(defined('DEVMODE') && DEVMODE)
	{
		throw $e;
	}

	if (!($e instanceof \PDOException)) {
		die($e->getMessage());
	}
}