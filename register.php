<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* registration form for new users
*
* @author Sascha Hofmann <shofmann@databay.de>
* @version $Id$
*
* @package ilias-core
*/

require_once("Services/Init/classes/class.ilInitialisation.php");
ilInitialisation::initILIAS();

$ilCtrl->initBaseClass("ilStartUpGUI");
// fau: fixLogoutBeforeRegister - logout before register to avoid redirect to register page
$GLOBALS['DIC']['ilAuthSession']->logout();
// fau.
$ilCtrl->setCmd("jumpToRegistration");
$ilCtrl->callBaseClass();
$ilBench->save();
