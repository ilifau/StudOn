<?php

//include_once (__DIR__ . '/include/inc.debug.php');
//log_request();
//echo "Hello from ILIAS";
//exit;


$GLOBALS['USE_ILIAS_HTTP_PATH_FROM_INI'] = true;

include_once 'Services/Context/classes/class.ilContext.php';
ilContext::init(ilContext::CONTEXT_CRON);

require_once("Services/Init/classes/class.ilInitialisation.php");
ilInitialisation::initILIAS();

echo "<pre>\n";
echo "CLIENT_ID: " . CLIENT_ID . "\n";
echo "HTTP_PATH: " . IlUtil::_getHttpPath() . "\n";

