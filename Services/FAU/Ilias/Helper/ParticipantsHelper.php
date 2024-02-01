<?php

namespace FAU\Ilias\Helper;

/**
 * trait for providing additional ilParticipants methods
 */
trait ParticipantsHelper 
{
    /**
     * fau: heavySub - Add user to a role with limited members
     * fau: fairSub -  Add user to a role with limited members
     * Note: check the result, then call addLimitedSuccess()
     *
     * @access public
     * @param 	int $a_usr_id	user id
     * @param 	int $a_role		role IL_CRS_MEMBER | IL_GRP_MEMBER
     * @param 	?int $a_max		maximum members (null, if no maximum defined)
     * @return  bool 	        user added (true) or not (false) or already assigned (null)
     */
    public function addLimited(int $a_usr_id, int $a_role, ?int $a_max = null) : ?bool
    {
        global $DIC;

        if ($this->isAssigned($a_usr_id)) {
            return null;
        } elseif (!$DIC->rbac()->admin()->assignUserLimitedCust((int) $this->role_data[$a_role], $a_usr_id, $a_max)) {
            return false;
        }
        return true;
    }
    // fau.
}