<?php
/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* fim: [media] new script for limited media player.
*
* @author Jesus Copado <jesus.copado@fim.uni-erlangen.de>
* @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
* @version $Id$
*/


chdir("../../");
require_once "./include/inc.header.php";
require_once "./Services/MediaObjects/classes/class.ilLimitedMediaPlayerGUI.php";
$player = new ilLimitedMediaPlayerGUI();
$player->executeCommand();
?>