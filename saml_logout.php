<?php
/* fau: samlAuth - back channel logout for simpleSAMLphp. */

/* Copyright (c) 1998-2016 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once "Services/Context/classes/class.ilContext.php";
ilContext::init(ilContext::CONTEXT_REST);

// Load ILIAS libraries and initialise ILIAS in non-web context
require_once("Services/Init/classes/class.ilInitialisation.php");
ilInitialisation::initILIAS();


require_once('Services/Authentication/classes/class.ilSession.php');
if (ilSession::_exists($_POST['studonSessionId']))
{
    $data = ilSession::_getData($_POST['studonSessionId']);

    if (strpos($data, $_POST['samlSessionId']) > 0)
    {
        ilSession::_destroy($_POST['studonSessionId'], ilSession::SESSION_CLOSE_USER);
    }
}