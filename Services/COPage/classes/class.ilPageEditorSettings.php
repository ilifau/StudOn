<?php

/* Copyright (c) 1998-2021 ILIAS open source, GPLv3, see LICENSE */

/**
 * Page editor settings
 *
 * @author Alexander Killing <killing@leifos.de>
 */
class ilPageEditorSettings
{
    // settings groups. each group contains one or multiple
    // page parent types
    protected static $option_groups = array(
        "lm" => array("lm"),
        "wiki" => array("wpg"),
        "scorm" => array("sahs"),
        "glo" => array("gdf"),
        "test" => array("qpl"),
        "rep" => array("cont"),
        "copa" => array("copa"),
        "frm" => array("frm"),
        );
        
    /**
    * Get all settings groups
    */
    public static function getGroups()
    {
        return self::$option_groups;
    }
    
    /**
    * Write Setting
    */
    public static function writeSetting($a_grp, $a_name, $a_value)
    {
        global $DIC;

        $ilDB = $DIC->database();
        
        $ilDB->manipulate(
            "DELETE FROM page_editor_settings WHERE " .
            "settings_grp = " . $ilDB->quote($a_grp, "text") .
            " AND name = " . $ilDB->quote($a_name, "text")
        );
        
        $ilDB->manipulate("INSERT INTO page_editor_settings " .
            "(settings_grp, name, value) VALUES (" .
            $ilDB->quote($a_grp, "text") . "," .
            $ilDB->quote($a_name, "text") . "," .
            $ilDB->quote($a_value, "text") .
            ")");
    }
    
    /**
    * Lookup setting
    */
    public static function lookupSetting($a_grp, $a_name, $a_default = false)
    {
        global $DIC;

        $ilDB = $DIC->database();
        
        $set = $ilDB->query(
            "SELECT value FROM page_editor_settings " .
            " WHERE settings_grp = " . $ilDB->quote($a_grp, "text") .
            " AND name = " . $ilDB->quote($a_name, "text")
        );
        if ($rec = $ilDB->fetchAssoc($set)) {
            return $rec["value"];
        }
        
        return $a_default;
    }
    
    /**
    * Lookup setting by parent type
    */
    public static function lookupSettingByParentType($a_par_type, $a_name, $a_default = false)
    {
        foreach (self::$option_groups as $g => $types) {
            if (in_array($a_par_type, $types)) {
                $grp = $g;
            }
        }
        
        if ($grp != "") {
            return ilPageEditorSettings::lookupSetting($grp, $a_name, $a_default);
        }
        
        return $a_default;
    }
}
