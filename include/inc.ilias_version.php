<?php
/* Copyright (c) 1998-2020 ILIAS open source e-Learning e.V., Extended GPL, see docs/LICENSE */

/**
* sets ILIAS version (this file shouldn't be merged between cvs branches)
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @package ilias-core
*/
define("ILIAS_VERSION", "7.10 2022-04-28");
define("ILIAS_VERSION_NUMERIC", "7.10");			// since version ILIAS 6 this must be always x.y: x and y are numbers

// fau: versionSuffix - define a version with suffix for including css and js files
// please increase a suffix number if a css or js file is locally changed!
define("ILIAS_VERSION_SUFFIX", ILIAS_VERSION_NUMERIC . ".41");
// fau.
