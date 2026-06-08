<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

/** @noRector */
use ILIAS\StaticURL\Services;

require_once("../vendor/composer/vendor/autoload.php");
require_once __DIR__ . '/../artifacts/bootstrap_default.php';
entry_point('ILIAS Legacy Initialisation Adapter');

global $DIC;

// fau: campoLink - treat course link from campo
if (isset($_GET['target']))
{
    if (substr($_GET['target'], 0, 6) == 'campo_') {
        global $DIC;
        $DIC->fau()->study()->redirectFromTarget($_GET['target']);
    }
    if (substr($_GET['target'], 0, 8) == 'orgunit_') {
        global $DIC;
        $DIC->fau()->org()->redirectFromTarget($_GET['target']);
    }
}
// fau.

/** @var Services $static_url */
$static_url = $DIC['static_url'];
$static_url->handler()->initHandler();
$static_url->handler()->performRedirect(
    $static_url->builder()->getBaseURI()
);
