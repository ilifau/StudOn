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

/**
 * @author Alexander Killing <killing@leifos.de>
 */
class ilObjExerciseAccess extends ilObjectAccess implements ilConditionHandling
{
    /**
     * Get possible conditions operators
     * @return string[]
     */
    public static function getConditionOperators(): array
    {
        return array(
            ilConditionHandler::OPERATOR_PASSED,
            ilConditionHandler::OPERATOR_FAILED
        );
    }


    /**
     * check condition
     */
    public static function checkCondition(int $a_trigger_obj_id, string $a_operator, string $a_value, int $a_usr_id): bool
    {
        switch ($a_operator) {
            case ilConditionHandler::OPERATOR_PASSED:
                return ilExerciseMembers::_lookupStatus($a_trigger_obj_id, $a_usr_id) == "passed";

            case ilConditionHandler::OPERATOR_FAILED:
                return ilExerciseMembers::_lookupStatus($a_trigger_obj_id, $a_usr_id) == 'failed';

            default:
                return true;
        }
    }


    /**
     * get commands
     *
     * this method returns an array of all possible commands/permission combinations
     *
     * example:
     * $commands = array
     *	(
     *		array("permission" => "read", "cmd" => "view", "lang_var" => "show"),
     *		array("permission" => "write", "cmd" => "edit", "lang_var" => "edit"),
     *	);
     */
    public static function _getCommands(): array
    {
        return array(
            array("permission" => "read", "cmd" => "showOverview", "lang_var" => "show",
                "default" => true),
            array("permission" => "write", "cmd" => "listAssignments", "lang_var" => "edit_assignments"),
            array("permission" => "write", "cmd" => "edit", "lang_var" => "settings")
        );
    }

    /**
     * @throws ilDateTimeException
     */
    public static function _lookupRemainingWorkingTimeString(
        int $a_obj_id
    ): array {
        global $DIC;

        $ilDB = $DIC->database();

        // #14077 - mind peer deadline, too

        $dl = null;
        $cnt = array();

        $q = "SELECT id, time_stamp, deadline2, peer_dl" .
            " FROM exc_assignment WHERE exc_id = " . $ilDB->quote($a_obj_id, "integer") .
            " AND (time_stamp > " . $ilDB->quote(time(), "integer") .
            " OR (peer_dl > " . $ilDB->quote(time(), "integer") .
            " AND peer > " . $ilDB->quote(0, "integer") . "))";
        $set = $ilDB->query($q);
        while ($row = $ilDB->fetchAssoc($set)) {
            if ($row["time_stamp"] > time() &&
                ($row["time_stamp"] < $dl || !$dl)) {
                $dl = $row["time_stamp"];
            }
            /* extended deadline should not be presented anywhere
            if($row["deadline2"] > time() &&
                ($row["deadline2"] < $dl || !$dl))
            {
                $dl = $row["deadline2"];
            }
            */
            if ($row["peer_dl"] > time() &&
                ($row["peer_dl"] < $dl || !$dl)) {
                $dl = $row["peer_dl"];
            }
            $cnt[$row["id"]] = true;
        }

        // :TODO: mind personal deadline?

        if ($dl) {
            $dl = ilLegacyFormElementsUtil::period2String(new ilDateTime($dl, IL_CAL_UNIX));
        }

        return array(
            "mtime" => $dl,
            "cnt" => count($cnt)
        );
    }

    /**
    * check whether goto script will succeed
    */
    public static function _checkGoto($a_target): bool
    {
        global $DIC;

        $ilAccess = $DIC->access();

        $t_arr = explode("_", $a_target);

        if ($t_arr[0] != "exc" || ((int) $t_arr[1]) <= 0) {
            return false;
        }
        return $ilAccess->checkAccess("read", "", $t_arr[1]) ||
            $ilAccess->checkAccess("visible", "", $t_arr[1]);
    }

    // fau: exAssHook - new function checkExtendedGradingAccess()
    // fau: exAssTest - new function checkExtendedGradingAccess()
    // fau: exGradeTime - new function checkExtendedGradingAccess()
    // fau: exMemDelete - new function checkExtendedGradingAccess()
    // fau: exMemFilter - new function checkExtendedGradingAccess()
    // gau: exPlag - new function checkExtendedGradingAccess()
    // fau: exTeamRemove - new function checkExtendedGradingAccess()
    // fau: exTeamSingles - new function checkExtendedGradingAccess()
    /**
     * Check if a user has extended grading access to the exercise
     * @param  int  $a_id
     * @param bool $is_reference
     * @return bool
     */
    public static function d($a_id, $is_reference = true): bool
    {
        global $DIC;

        if ($is_reference) {
            $refs = [$a_id];
        }
        else {
            $refs = ilObject2::_getAllReferences($a_id);
        }

        foreach ($refs as $ref_id) {
            if ($DIC->access()->checkAccess('write', '', $ref_id)
            && $DIC->access()->checkAccess('edit_submissions_grades', '', $ref_id)) {
                return true;
            }
        }
        return false;
    }
    // fau.

    public function canBeDelivered(ilWACPath $ilWACPath): bool
    {
        return true;
    }
}
