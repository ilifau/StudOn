<?php
/* fau: samlAuth - new login script for authentication by SimpleSAMLphp. */

/* Copyright (c) 1998-2016 ILIAS open source, Extended GPL, see docs/LICENSE */

// first do the simpleSAMLphp authentication
require_once('/srv/www/simplesamlphp/lib/_autoload.php');
$GLOBALS['ilSimpleSAMLInterface'] = sspmod_studon_Interface::getInstance();
$GLOBALS['ilSimpleSAMLInterface']->requireAuth();

// then initialize ILIAS
// $ilAuth will be created as ilSimpleSamlAuthStudOn because the global ilSimpleSAMLInterface is set
require_once("Services/Init/classes/class.ilInitialisation.php");
ilInitialisation::initILIAS();

// process ILIAS login
// A direct call from ilAuth->start() is prevented by setAllowLogin(false) in ilSimpleSamlAuthStudOn

/** @var ilSimpleSamlAuthStudOn $ilAuth */
global $ilAuth;
$ilAuth->login();
