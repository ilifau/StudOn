<?php

namespace FAU\Ilias\Helper;
use ilLDAPRoleGroupMapping;

/**
 * trait for providing additional iiRbacAdmin methods
 */
trait RbacAdminHelper 
{
    /**
     * fau: heavySub - Assigns a user to a role with a limit of maximum members
     * fau: fairSub - Assigns a user to a role with a limit of maximum members
     * @param	int 	$a_role_id	        object_id of role
     * @param	int 	$a_usr_id	        object_id of user
     * @param	?int	$a_limit	        maximum members (null, if not set)
     * @param	int[]   $a_limited_roles    ids of roles whose count of union participants should be compared with the maximum members
     * @return	bool     user assigned (true), not assigned (false)
     */
    public function assignUserLimitedCust(int $a_role_id, int $a_usr_id, ?int $a_limit = null, array $a_limited_roles = []) : bool
    {
        global $DIC;

        if ($a_limit === null) {
            // don't check maximum members
            // but check existing membership
            $query = "INSERT INTO rbac_ua (usr_id, rol_id) "
                . "SELECT %s usr_id, %s rol_id FROM DUAL "
                . "WHERE NOT EXISTS (SELECT 1 FROM rbac_ua WHERE usr_id = %s and rol_id = %s) ";

            $res = $DIC->database()->manipulateF(
                $query,
                array(	'integer', 'integer',
                        'integer', 'integer'
                ),
                array(	$a_usr_id, $a_role_id,
                        $a_usr_id, $a_role_id
                )
            );
        }
        else {

            // use at least the assigned role to check the limit
            if (empty($a_limited_roles)) {
                $a_limited_roles = [$a_role_id];
            }

            // check max members and add member in one statement
            // check also whether member is already assigned
            $query = "INSERT INTO rbac_ua(usr_id, rol_id) "
                . "SELECT %s usr_id, %s rol_id FROM DUAL "
                . "WHERE NOT EXISTS (SELECT 1 FROM rbac_ua WHERE usr_id = %s and rol_id = %s) "
                . "AND %s > (SELECT COUNT(*) FROM rbac_ua WHERE "
                . $DIC->database()->in('rol_id', (array) $a_limited_roles, false, 'integer') . ")";

            $res = $DIC->database()->manipulateF(
                $query,
                array(	'integer', 'integer',
                        'integer', 'integer',
                        'integer'
                ),
                array(	$a_usr_id, $a_role_id,
                        $a_usr_id, $a_role_id,
                        $a_limit
                )
            );
        }

        if ($res == 0) {
            return false;
        }

        $DIC->rbac()->review()->setAssignedCacheEntry($a_role_id, $a_usr_id, true);

        $mapping = ilLDAPRoleGroupMapping::_getInstance();
        $mapping->assign($a_role_id, $a_usr_id);
        return true;
    }
    // fau.
}