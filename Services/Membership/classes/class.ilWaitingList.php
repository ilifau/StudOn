<?php

declare(strict_types=1);

use FAU\Ilias\Helper\WaitingListHelper;
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
use FAU\Ilias\Helper\WaitingListConstantsHelper;

/**
 * Base class for course and group waiting lists
 * @author  Stefan Meyer <smeyer.ilias@gmx.de>
 * @ingroup ServicesMembership
 */
abstract class ilWaitingList
{
    use WaitingListHelper;

    public static array $is_on_list = [];
    private int $obj_id = 0;
    private array $user_ids = [];
    private array $users = [];
    protected ilDBInterface $db;
    protected ilAppEventHandler $eventHandler;

    public function __construct(int $a_obj_id)
    {
        global $DIC;

        $this->db = $DIC->database();
        $this->eventHandler = $DIC->event();
        $this->obj_id = $a_obj_id;
        if ($a_obj_id) {
            $this->read();
        }
    }

    public static function lookupListSize(int $a_obj_id): int
    {
        global $DIC;

        $ilDB = $DIC->database();
        $query = 'SELECT count(usr_id) num from crs_waiting_list WHERE obj_id = ' . $ilDB->quote($a_obj_id, 'integer');
        $res = $ilDB->query($query);
        while ($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT)) {
            return (int) $row->num;
        }
        return 0;
    }

    public static function _deleteAll(int $a_obj_id): void
    {
        global $DIC;

        $ilDB = $DIC->database();

        $query = "DELETE FROM crs_waiting_list WHERE obj_id = " . $ilDB->quote($a_obj_id, 'integer') . " ";
        $res = $ilDB->manipulate($query);
    }

    public static function _deleteUser(int $a_usr_id): void
    {
        global $DIC;

        $ilDB = $DIC->database();
        $query = "DELETE FROM crs_waiting_list WHERE usr_id = " . $ilDB->quote($a_usr_id, 'integer');
        $res = $ilDB->manipulate($query);
    }

    public static function deleteUserEntry(int $a_usr_id, int $a_obj_id): void
    {
        global $DIC;

        $ilDB = $DIC->database();
        $query = "DELETE FROM crs_waiting_list " .
            "WHERE usr_id = " . $ilDB->quote($a_usr_id, 'integer') . ' ' .
            "AND obj_id = " . $ilDB->quote($a_obj_id, 'integer');
        $ilDB->query($query);
    }

    public function getObjId(): int
    {
        return $this->obj_id;
    }

    
    public function addToList(int $a_usr_id): bool
    {
        if ($this->isOnList($a_usr_id)) {
            return false;
        }
        $query = "INSERT INTO crs_waiting_list (obj_id,usr_id,sub_time) " .
            "VALUES (" .
            $this->db->quote($this->getObjId(), 'integer') . ", " .
            $this->db->quote($a_usr_id, 'integer') . ", " .
            $this->db->quote(time(), 'integer') . " " .
            ")";
        $res = $this->db->manipulate($query);
        $this->read();
        return true;
    }

    public function updateSubscriptionTime(int $a_usr_id, int $a_subtime): void
    {
        $query = "UPDATE crs_waiting_list " .
            "SET sub_time = " . $this->db->quote($a_subtime, 'integer') . " " .
            "WHERE usr_id = " . $this->db->quote($a_usr_id, 'integer') . " " .
            "AND obj_id = " . $this->db->quote($this->getObjId(), 'integer') . " ";
        $res = $this->db->manipulate($query);

        // fau: fairSub#73 - recalculate after updating time
        $this->users[$a_usr_id]['time'] = (int) $a_subtime;
        $this->recalculate();
        // fau.
    }

    public function removeFromList(int $a_usr_id): bool
    {
        $query = "DELETE FROM crs_waiting_list " .
            " WHERE obj_id = " . $this->db->quote($this->getObjId(), 'integer') . " " .
            " AND usr_id = " . $this->db->quote($a_usr_id, 'integer') . " ";
        $affected = $this->db->manipulate($query);

        // fau: fairSub#74 - avoid multiple reading
        unset($this->users[$a_usr_id]);
        $this->recalculate();
        // fau.
        return $affected > 0;
    }

    public function isOnList(int $a_usr_id): bool
    {
        return isset($this->users[$a_usr_id]);
    }

    public static function _isOnList(int $a_usr_id, int $a_obj_id): bool
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];
        if (isset(self::$is_on_list[$a_usr_id][$a_obj_id])) {
            return self::$is_on_list[$a_usr_id][$a_obj_id];
        }

        $query = "SELECT usr_id " .
            "FROM crs_waiting_list " .
            "WHERE obj_id = " . $ilDB->quote($a_obj_id, 'integer') . " " .
            "AND usr_id = " . $ilDB->quote($a_usr_id, 'integer');
        $res = $ilDB->query($query);
        return (bool) $res->numRows();
    }

    /**
     * Preload on list info. This is used, e.g. in the repository
     * to prevent multiple reads on the waiting list table.
     * The function is triggered in the preload functions of ilObjCourseAccess
     * and ilObjGroupAccess.
     */
    public static function _preloadOnListInfo(array $a_usr_ids, array $a_obj_ids): void
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];

        // fau: fairSub#75 - fill also to_confirm info at preload
        foreach ($a_usr_ids as $usr_id) {
            foreach ($a_obj_ids as $obj_id) {
                self::$is_on_list[$usr_id][$obj_id] = false;
                self::$to_confirm[$usr_id][$obj_id] = WaitingListConstantsHelper::REQUEST_NOT_ON_LIST;
            }
        }
        $query = "SELECT usr_id, obj_id, to_confirm " .
            "FROM crs_waiting_list " .
            "WHERE " .
            $ilDB->in("obj_id", $a_obj_ids, false, "integer") . " AND " .
            $ilDB->in("usr_id", $a_usr_ids, false, "integer");
        $res = $ilDB->query($query);
        while ($rec = $ilDB->fetchAssoc($res)) {
            self::$is_on_list[$rec["usr_id"]][$rec["obj_id"]] = true;
            self::$to_confirm[$rec["usr_id"]][$rec["obj_id"]] = $rec['to_confirm'];
        }
        // fau.
    }

    public function getCountUsers(): int
    {
        return count($this->users);
    }

    public function getPosition(int $a_usr_id): int
    {
        return isset($this->users[$a_usr_id]) ? $this->users[$a_usr_id]['position'] : -1;
    }

    /**
     * get all users on waiting list
     * @access public
     * @return array<int, array<{position: int, time: int, usr_id: int}>>
     */
    public function getAllUsers(): array
    {
        return $this->users;
    }

    /**
     * get user
     * @param int usr_id
     * @return array<{position: int, time: int, usr_id: int}>
     */
    public function getUser(int $a_usr_id): array
    {
        return $this->users[$a_usr_id] ?? [];
    }

    /**
     * @return int[]
     */
    public function getUserIds(): array
    {
        return $this->user_ids;
    }

    private function read(): void
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];
                
        $this->users = [];

        // fau: fairSub#76 - get subject and to_confirm
        // fau: fairSub#77 - recalculate after reading, sorting is done there
        // fau: campoSub - read the module id

        $query = "SELECT * FROM crs_waiting_list " .
            "WHERE obj_id = " . $ilDB->quote($this->getObjId(), 'integer');

        $res = $this->db->query($query);

        while ($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT)) {
            $this->users[$row->usr_id]['time'] = $row->sub_time;
            $this->users[$row->usr_id]['usr_id'] = $row->usr_id;
            $this->users[$row->usr_id]['subject'] = $row->subject;
            $this->users[$row->usr_id]['to_confirm'] = $row->to_confirm;
            $this->users[$row->usr_id]['module_id'] = $row->module_id;
        }
        $this->recalculate();
        // fau.
    }
}
