<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* fim: [bugfix] allow a use of the deprecated repository.php
*
* If you want to use this script your base class must be declared
* within modules.xml.
*
* @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
* @version $Id:  $
*/

require_once("Services/Init/classes/class.ilInitialisation.php");
ilInitialisation::initILIAS();

global $ilCtrl, $ilBench;

if (DEVMODE)
{
	echo "Calling repository.php is deprecated.<br />";
	echo "Use ilias.php?baseClass=ilRepositoryGUI instead!";
}
$ilCtrl->setTargetScript("ilias.php");
$ilCtrl->initBaseClass("ilRepositoryGUI");
$ilCtrl->callBaseClass();
$ilBench->save();

?>