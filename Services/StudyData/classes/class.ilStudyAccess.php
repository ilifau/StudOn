<?php
/* fau: studyData - new class ilStudyAccess. */

/**
 * This class handles study data and study conditions
*/
class ilStudyAccess
{
    /**
     * Require the data classes
     */
    public static function _requireData()
    {
        require_once "Services/StudyData/classes/class.ilStudyCourseData.php";
        require_once "Services/StudyData/classes/class.ilStudyDocData.php";
    }

    /**
     * Require the conditions classes
     */
    public static function _requireConditions()
    {
        require_once "Services/StudyData/classes/class.ilStudyCourseCond.php";
        require_once "Services/StudyData/classes/class.ilStudyDocCond.php";
    }

    /**
     * Check study data based access conditions
     *
     * This check is called from ilRbacSystem->checkAccessOfUser() for "read" operations
     * A positive result will overrule the rbac restrictions
     * Therefore this check requires a condition to exist and being fulfilled
     *
     * @param 	int		$ref_id
     * @param 	int		$user_id
     * @return  boolean		access is granted (true/false)
     */
    public static function _checkAccess($ref_id, $user_id)
    {
        // Performance improvement
        // only check a few objects which are listed in the custom config
        $ref_ids = explode(',', ilCust::get('studydata_check_ref_ids'));
        if (!in_array($ref_id, $ref_ids)) {
            return false;
        }

        $obj_id = ilObject::_lookupObjId($ref_id);

        // Don't grant access if the user does not have data
        if (!self::_hasData($user_id)) {
            return false;
        }

        // Don't grant access if the object does not have a condition
        if (!self::_hasConditions($obj_id)) {
            return false;
        }

        // Otherwise do the condition checks
        return self::_checkConditions($obj_id, $user_id);
    }


    /**
     * Check if subscription is allowed for user
     *
     * @param int   $obj_id
     * @param int   $user_id
     * @return bool
     */
    public static function _checkSubscription($obj_id, $user_id)
    {
        // Don't allow subscription if a user does not have data
        if (!self::_hasData($user_id)) {
            return false;
        }

        // Allow subscription of no conditions exist
        if (!self::_hasConditions($obj_id)) {
            return true;
        }

        // Otherwise do the condition checks
        return self::_checkConditions($obj_id, $user_id);
    }


    /**
     * Check if a user has study data
     * @param int $user_id
     * @return bool
     */
    public static function _hasData($user_id)
    {
        self::_requireData();
        return (ilStudyCourseData::_has($user_id) || ilStudyDocData::_has($user_id));
    }

    /**
     * Get the textual description of the study data
     * @param int $user_id
     * @return string
     */
    public static function _getDataText($user_id)
    {
        self::_requireData();
        $texts = [];
        foreach (ilStudyCourseData::_get($user_id) as $data) {
            $texts[] = $data->getText();
        }
        foreach (ilStudyDocData::_get($user_id) as $data) {
            $texts[] = $data->getText();
        }
        return implode(" \n\n", $texts);
    }

    /**
     * Delete the data of a user
     * (e.g. if the user is deleted)
     * @param int $user_id
     */
    public static function _deleteData($user_id)
    {
        self::_requireData();
        ilStudyDocData::_delete($user_id);
        ilStudyCourseData::_delete($user_id);
    }

    /**
     * Check if an object has conditions
     * @param $obj_id
     * @return bool
     */
    public static function _hasConditions($obj_id)
    {
        self::_requireConditions();
        return (ilStudyCourseCond::_has($obj_id) || ilStudyDocCond::_has($obj_id));
    }

    /**
     * Get the textual description of the conditions
     * @param int $obj_id
     * @return string
     */
    public static function _getConditionsText($obj_id)
    {
        global $DIC;
        $lng = $DIC->language();
        self::_requireConditions();

        $texts = [];
        foreach (ilStudyCourseCond::_get($obj_id) as $cond) {
            if ($text = $cond->getText()) {
                $texts[] = $text;
            }
        }
        foreach (ilStudyDocCond::_get($obj_id) as $cond) {
            if ($text = $cond->getText()) {
                $texts[] = $text;
            }
        }

        if (count($texts)) {
            return implode($lng->txt('studycond_condition_delimiter') . ' ', $texts);
        } else {
            return $lng->txt('studycond_no_condition_defined');
        }
    }

    /**
     * Clone the conditions for another object
     * @param $from_obj_id
     * @param $to_obj_id
     */
    public static function _cloneConditions($from_obj_id, $to_obj_id)
    {
        self::_requireConditions();
        ilStudyDocCond::_clone($from_obj_id, $to_obj_id);
        ilStudyCourseCond::_clone($from_obj_id, $to_obj_id);
    }

    /**
     * Delete the conditions of an object
     * (e.g. if the object is deleted)
     * @param int $obj_id
     */
    public static function _deleteConditions($obj_id)
    {
        self::_requireConditions();
        ilStudyDocCond::_delete($obj_id);
        ilStudyCourseCond::_delete($obj_id);
    }

    /**
     * Check the mapping of conditions data and study data
     * Returns true if one condition fits
     *
     * @param 	array		conditions data
     * @param 	array		study data
     * @return boolean
     */
    protected static function _checkConditions($obj_id, $usr_id)
    {
        self::_requireData();
        self::_requireConditions();

        // check the course conditions
        $data = ilStudyCourseData::_get($usr_id);
        foreach (ilStudyCourseCond::_get($obj_id) as $cond) {
            if ($cond->check($data)) {
                return true;
            }
        }

        // check the doc program conditions
        $data = ilStudyDocData::_get($usr_id);
        foreach (ilStudyDocCond::_get($obj_id) as $cond) {
            if ($cond->check($data)) {
                return true;
            }
        }

        return false;
    }


    /**
    * Get the offset of the running semester in relation to the given semester
    *
    * @param    string      year and semester, e.g. '20092' (ws) or '20101' (ss)
    * @return   int         offset (-1, 0, 1)
    */
    public static function _getRunningSemesterOffset($a_semester = '')
    {
        if (strlen($a_semester) != 5 or !is_numeric($a_semester)) {
            // not identifiably
            return 0;
        }

        $current = self::_getRunningSemesterString();
        $cur_year = (int) substr($current, 0, 4);
        $cur_sem = (int) substr($current, 4);
                
        $ref_year = (int) substr($a_semester, 0, 4);
        $ref_sem = (int) substr($a_semester, 4);

        return ($cur_year - $ref_year) * 2 + ($cur_sem - $ref_sem);
    }

    
    /**
     * Get a string representing the current semester
     *
     * @return	string 	year and semester year and semester, e.g. '20092' (ws) or '20101' (ss)
     */
    public static function _getRunningSemesterString()
    {
        $month = (int) date('m');
        if ($month <= 3) {
            // winter semester of last year
            $cur_year = (int) date('Y') - 1;
            $cur_sem = 2;
        } elseif ($month <= 9) {
            // summer semester
            $cur_year = (int) date('Y');
            $cur_sem = 1;
        } else {
            // winter semester of this year
            $cur_year = (int) date('Y');
            $cur_sem = 2;
        }
        
        return sprintf("%04d%01d", $cur_year, $cur_sem);
    }
}
