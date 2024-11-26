<?php
/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * fau: relativeLink - new class ilRelativeLink
 *
 * @author Fred Neumann <fred.neumann@fau.de>
 * @version $Id: class.ilRelativeLink.php 60422 2015-08-14 12:17:57Z fneumann $
*/
class ilRelativeLink
{
    /**
     * Link types (determine how the id is interpreted)
     */
    const TYPE_REP_OBJECT = 'RepObject';
    const TYPE_LM_PAGE = 'LmPage';
    //	const TYPE_WIKI_PAGE = 'WikiPage';
    //	const TYPE_FRM_THREAD = 'FrmThread';
    //	const TYPE_GLO_TERM	= 'GloTerm';


    /* @var string	$target_type */
    public $target_type;

    /* @var integer	target_id */
    public $target_id;

    /* @var string link $code */
    public $code;


    /**
     * Related data
     * @see self::getForCode()
     */
    private $target_obj_id;
    private $target_ref_id;


    /**
     * Private constructor
     * use getForTarget() or getAllForCode() instead
     */
    private function __construct()
    {
    }


    /**
     * Get a link object for a target
     *
     * @param string	$a_type 		target type
     * @param integer	$a_target_id 	target id
     * @param boolean					create if not existent
     * @return ilRelativeLink
     */
    public static function getForTarget($a_type, $a_id, $a_create = false)
    {
        /* @var ilDB $ilDB */
        global $ilDB;

        $query = 'SELECT * FROM il_relative_link'
            . ' WHERE target_type = ' . $ilDB->quote($a_type, 'text')
            . ' AND target_id = ' . $ilDB->quote($a_id, 'integer');

        $res = $ilDB->query($query);
        if ($row = $ilDB->fetchAssoc($res)) {
            $linkObj = new ilRelativeLink;
            $linkObj->fillData($row);
            return $linkObj;
        } elseif ($a_create) {
            $linkObj = new ilRelativeLink;
            $linkObj->target_type = $a_type;
            $linkObj->target_id = $a_id;
            $linkObj->save();
            return $linkObj;
        } else {
            return null;
        }
    }


    /**
     * Get all links for a link code
     * (omitting deleted objects, sort by ref_id)
     *
     * @param 	string	$a_code 	link code
     * @return 	array				ilRelativeLink objects
     */
    public static function getAllForCode($a_code)
    {
        /* @var ilDB $ilDB */
        global $ilDB;

        // query for links
        $query = "SELECT * FROM il_relative_link"
            . " WHERE code = " . $ilDB->quote($a_code, 'text');
        $target_ids = array();
        $res = $ilDB->query($query);
        while ($row = $ilDB->fetchAssoc($res)) {
            // all targets of a code have the same type
            $target_type = $row['target_type'];
            $target_ids[] = $row['target_id'];
        }

        // no links found
        if (empty($target_ids)) {
            return array();
        }

        // query for targets, depending on type
        $linkObjects = array();
        $query = '';
        switch ($target_type) {
            case self::TYPE_REP_OBJECT:
                $query = "SELECT "
                    . $ilDB->quote($a_code, 'text') . " AS code, "
                    . $ilDB->quote($target_type, 'text') . " AS target_type, "
                    . " r.obj_id AS target_id, "
                    . " r.obj_id AS target_obj_id, "
                    . " r.ref_id AS target_ref_id "
                    . " FROM object_reference r"
                    . " WHERE " . $ilDB->in('r.obj_id', $target_ids, false, "integer")
                    . " AND r.deleted IS NULL"
                    . " ORDER BY r.ref_id ASC";
                break;

            case self::TYPE_LM_PAGE:
                $query = "SELECT "
                    . $ilDB->quote($a_code, 'text') . " AS code, "
                    . $ilDB->quote($target_type, 'text') . " AS target_type, "
                    . " l.obj_id AS target_id, "
                    . " r.obj_id AS target_obj_id, "
                    . " r.ref_id AS target_ref_id "
                    . " FROM lm_data l"
                    . " INNER JOIN object_reference r ON r.obj_id = l.lm_id"
                    . " WHERE " . $ilDB->in('l.obj_id', $target_ids, false, "integer")
                    . " AND r.deleted IS NULL"
                    . " ORDER BY r.ref_id ASC";
                break;
        }
        $res = $ilDB->query($query);
        while ($row = $ilDB->fetchAssoc($res)) {
            $linkObj = new ilRelativeLink;
            $linkObj->fillData($row);
            $linkObjects[] = $linkObj;
        }

        return $linkObjects;
    }


    /**
     * Fill the class variables with data from an array
     *
     * @param array 	$data	variable => value
     */
    protected function fillData($data)
    {
        // table data, always filled
        $this->code = $data['code'];
        $this->target_type = $data['target_type'];
        $this->target_id = $data['target_id'];

        // related data, filled in getForCode()
        if (!empty($data['target_obj_id'])) {
            $this->target_obj_id = $data['target_obj_id'];
        }
        if (!empty($data['target_ref_id'])) {
            $this->target_ref_id = $data['target_ref_id'];
        }
    }


    /**
     * Save a new link in the database
     * @return bool
     */
    private function save()
    {
        /* @var ilDB $ilDB */
        global $ilDB;

        if (!isset($this->target_type) or !isset($this->target_id)) {
            return false;
        }
        if (empty($this->code)) {
            $this->createRandomCode();
        }
        $ilDB->insert(
            'il_relative_link',
            array(
                'target_type' => array('text', $this->target_type),
                'target_id' => array('integer', $this->target_id),
                'code' => array('text', $this->code)
            )
        );
        return true;
    }


    /**
     * Clone an existing link for a target
     *
     * @param string	$a_target_type
     * @param integer	$a_old_target_id
     * @param integer	$a_new_target_id
     */
    public static function cloneLink($a_target_type, $a_old_target_id, $a_new_target_id)
    {
        $linkObj = self::getForTarget($a_target_type, $a_old_target_id);

        if (isset($linkObj)) {
            $linkObj->target_id = $a_new_target_id;
            $linkObj->save();
        }
    }

    /**
     * Delete a link for a target
     *
     * @param string	$a_type 		target type
     * @param integer	$a_target_id 	target id
     * @param boolean					create if not existent
     * @return ilRelativeLink
     */
    public static function deleteLink($a_type, $a_id)
    {
        /* @var ilDB $ilDB */
        global $ilDB;

        $query = 'DELETE FROM il_relative_link'
            . ' WHERE target_type = ' . $ilDB->quote($a_type, 'text')
            . ' AND target_id = ' . $ilDB->quote($a_id, 'integer');

        $res = $ilDB->manipulate($query);
    }


    /**
     * Get the Url for this link
     * @return string
     */
    public function getUrl()
    {
        return ILIAS_HTTP_PATH . '/goto.php?target=lcode_' . $this->code;
    }


    /**
     * Get a list of ref_ids for a target
     * @param string	$a_target_type	target type
     * @param string	$a_target_id	target id
     * @return array					list of ref_ids
     */
    public static function getRefIdsForTarget($a_target_type, $a_target_id)
    {
        global $ilDB;

        $ref_ids = array();
        switch ($a_target_type) {
            case self::TYPE_REP_OBJECT:
                $ref_ids = (ilObject::_getAllReferences($a_target_id));
                break;

            case self::TYPE_LM_PAGE:
                $query = "SELECT lm_id FROM lm_data WHERE obj_id = " . $ilDB->quote($a_target_id, 'integer');
                $res = $ilDB->query($query);
                while ($row = $ilDB->fetchAssoc($res)) {
                    $ref_ids = array_merge($ref_ids, ilObject::_getAllReferences($row['lm_id']));
                }
                break;
        }
        return array_values($ref_ids);
    }


    /**
     * Create and set a random link code
     */
    private function createRandomCode()
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
        $length = 8;

        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $pos = rand(0, strlen($chars) - 1);
            $code .= substr($chars, $pos, 1);
        }

        $this->code = $code;
    }


    /**
     * Get the nearest goto target for a link code
     * This checks:
     * - the permissions of the current user (read is needed)
     * - the users's current position in the repositioy
     *
     * @param 	string 	$a_code	link code
     * @return	string			goto target or empty string
     */
    public static function getNearestGotoTarget($a_code)
    {
        /* @var ilAccessHandler 	$ilAccess */
        /* @var ilNavigationHistory $ilNavigationHistory */
        /* @var ilRelativeLink 		$linkObj */

        global $ilAccess, $ilNavigationHistory, $ilLog;

        // get the user's current (or last) position in the repository
        if (isset($ilNavigationHistory)) {
            $items = $ilNavigationHistory->getItems();
        }
        if (empty($items)) {
            $current_ref_id = 1;	// root as default start
        } else {
            reset($items);
            $item = current($items);
            $current_ref_id = $item['ref_id'];
        }

        // get all links with readable target objects
        $all = self::getAllForCode($a_code);
        $checked = array();
        foreach ($all as $linkObj) {
            if ($ilAccess->checkAccess('read', '', $linkObj->target_ref_id)) {
                $distance = self::getTreeDistance($current_ref_id, $linkObj->target_ref_id);

                // overwrite a previous link with the same distance
                // getAllforCode() sorts by ref_id, so the newest wins
                $checked[$distance] = $linkObj;
            }
        }
        // get the goto target for the nearest link
        if (!empty($checked)) {
            ksort($checked);
            reset($checked);
            $linkObj = current($checked);
            $reptype = ilObject::_lookupType($linkObj->target_obj_id);

            switch ($linkObj->target_type) {
                case self::TYPE_REP_OBJECT:
                    return $reptype . '_' . $linkObj->target_ref_id;
                    break;

                case self::TYPE_LM_PAGE:
                    return 'pg_' . $linkObj->target_id . '_' . $linkObj->target_ref_id;
                    break;
            }
        }

        // no target found
        return '';
    }


    /**
     * Get a sortable distance between two objects in the repository
     *
     * @param 	integer 	$a_ref_from 	start ref_id
     * @param 	integer		$a_ref_to		end ref_id
     * @return	string						backsteps.forwardsteps, e.g. 0001.0003
     */
    public static function getTreeDistance($a_ref_from, $a_ref_to)
    {
        /* @var ilTree $tree */
        global $tree;

        $path_from = $tree->getPathId($a_ref_from);
        $path_to = $tree->getPathId($a_ref_to);

        // delete all common path ancestors
        // until a difference is found
        while (count($path_from) and count($path_to)
                and $path_from[0] == $path_to[0]) {
            array_shift($path_from);
            array_shift($path_to);
        }
        $backsteps = count($path_from);
        $forwardsteps = count($path_to);

        return sprintf('%04d.%04d', $backsteps, $forwardsteps);
    }
}
