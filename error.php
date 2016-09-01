<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once("Services/Init/classes/class.ilInitialisation.php");
ilInitialisation::initILIAS();

$tpl->addBlockFile("CONTENT", "content", "tpl.error.html");
$lng->loadLanguageModule("error");
// #13515 - link back to "system" [see ilWebAccessChecker::sendError()]
$nd = $tree->getNodeData(ROOT_FOLDER_ID);
// fim: [portal] changed button text to home and set link for button and logo
$tpl->SetVariable("TXT_LINK",  $lng->txt('to_home'));
$tpl->SetVariable("LINK", ILIAS_HTTP_PATH. '/ilias.php?baseClass=ilRepositoryGUI&amp;client_id='.CLIENT_ID);
// fim.
$tpl->setCurrentBlock("content");
$tpl->setVariable("ERROR_MESSAGE",($_SESSION["failure"]));
$tpl->setVariable("MESSAGE_HEADING", $lng->txt('error_sry_error'));

//$tpl->parseCurrentBlock();

ilSession::clear("referer");
ilSession::clear("message");
$tpl->show();
?>