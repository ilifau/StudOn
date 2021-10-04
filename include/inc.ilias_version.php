<?php
/* Copyright (c) 1998-2018 ILIAS open source e-Learning e.V., Extended GPL, see docs/LICENSE */

/**
* sets ILIAS version (this file shouldn't be merged between cvs branches)
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @package ilias-core
*/
define("ILIAS_VERSION", "5.4.22 2021-05-14");
define("ILIAS_VERSION_NUMERIC", "5.4.22");			// must be always x.y.z: x, y and z are numbers

// fau: versionSuffix - define a version with suffix for including css and js files
// please increase a suffix number if a css or js file is locally changed!
define("ILIAS_VERSION_SUFFIX", ILIAS_VERSION_NUMERIC . ".40");
// fau.
