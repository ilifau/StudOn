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

// fau: relativeLink - goto hook for rewriting the target
if (isset($_GET['target']))
{
    if (substr($_GET['target'], 0, 6) == 'lcode_') {
        $relgui = new ilRelativeLinkGUI();
        $relgui->gotoHook();
    }
}
// fau.



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

// fau: numericLink - lookup the type when only the ref_id or obj_id is given
if (isset($_GET['target']))
{
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
}
// fau.

/*
 * fau: gotoLinks - explanation of target handling
 *
 * target:			crs_123_join
 *
 * target_arr: 		array(crs, 123, join)
 * target_type: 	crs
 * target_id: 		123
 * rest: 			123_join
 * additional: 		join
 *
 *
 * called from ilInitialisation:
 * ilStartUpGUI::_checkGoto($_GET["target"])
 * - returns true for target type 'studon'
 * - returns false for join command if user is anonymous
 *
 * called afterwards from goto.php:
 * ilObjXyzGUI::_goto($rest) 					(default implementation)
 * ilObjXyzGUI::_goto($target_id, $additional)	(specific implementation)
 *
 * fau.
 */

// fau: gotoLinks - studon specific goto requests
// fau: regCodes - add code to registration link
if (isset($_GET['target']))
{
    $target_arr = explode("_", $_GET["target"]);
    $target_type = $target_arr[0];
    $target_id = $target_arr[1];
    $additional = isset($target_arr[2]) ? $target_arr[2] : null;		// optional for pages

    if ($target_type == 'studon') {
        switch ($target_id) {
            case "exportrequest":
                $ilCtrl->setTargetScript("goto.php");
                //$ilCtrl->getCallStructure("ilstudyexportrequestgui");
                $ilCtrl->setParameterByClass("ilstudyexportrequestgui", "target", "studon_exportrequest");
                $ilCtrl->forwardCommand(new ilStudyExportRequestGUI());
                exit;

            case "agreement":
                ilUtil::redirect('ilias.php?baseClass=ilStartUpGUI&cmd=showTermsOfService');
                break;

            case "register":
                if ($additional) {
                    ilUtil::redirect('register.php?code=' . $additional);
                } else {
                    ilUtil::redirect('register.php');
                }
                break;
        }
    }
}
// fau.

/** @var Services $static_url */
$static_url = $DIC['static_url'];
$static_url->handler()->performRedirect(
    $static_url->builder()->getBaseURI()
);
