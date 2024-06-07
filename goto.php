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

require_once("libs/composer/vendor/autoload.php");
ilInitialisation::initILIAS();

global $DIC;

/** @var Services $static_url */
$static_url = $DIC['static_url'];
$static_url->handler()->performRedirect(
    $static_url->builder()->getBaseURI()
);

// fau: campoLink - treat course link from campo
if (substr($_GET['target'], 0, 6) == 'campo_') {
    global $DIC;
    $DIC->fau()->study()->redirectFromTarget($_GET['target']);
}
if (substr($_GET['target'], 0, 8) == 'orgunit_') {
    global $DIC;
    $DIC->fau()->org()->redirectFromTarget($_GET['target']);
}

// fau.

// fau: numericLink - lookup the type when only the ref_id or obj_id is given
if (is_numeric($_GET['target'])) {
    $type = ilObject::_lookupType((int) $_GET['target'], true);

    // check if obj_id is given
    if (empty($type)) {
        $ref_ids = ilObject::_getAllReferences($_GET['target']);
        foreach ($ref_ids as $ref_id) {
            if (!ilObject::_isInTrash($ref_id)) {
                $_GET['target'] = $ref_id;
                $type = ilObject::_lookupType((int) $_GET['target'], true);
                break;
            }
        }
    }

    if (!empty($type)) {
        $_GET['target'] = $type . '_' . (int) $_GET['target'];
    }
}
// fau.

