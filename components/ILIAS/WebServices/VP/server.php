<?php
// fau: videoPortal - entry script for video portal REST service

chdir('../../..');

// we need access handling
include_once 'Services/Context/classes/class.ilContext.php';
ilContext::init(ilContext::CONTEXT_RSS);

require_once("Services/Init/classes/class.ilInitialisation.php");
ilInitialisation::initILIAS();

include_once './Services/WebServices/VP/classes/class.ilVideoPortalServer.php';
$server = new ilVideoPortalServer(
    [
        'settings' => [
            'displayErrorDetails' => DEVMODE ? true : false
        ]
    ]
);
$server->init();
$server->run();
