<?php
/* Copyright (c) 1998-2011 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once('./Services/Membership/classes/class.ilWaitingList.php');

/**
 * Session waiting list
 *
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 * @version $Id$
 *
 * @extends ilWaitingList
 */
class ilSessionWaitingList extends ilWaitingList
{

// fau: fairSub - add subject, to_confirm and sub_time as parameter, avoid re-reading
    /**
     * add to list
     *
     * @param 	int 		$a_usr_id
     * @param 	string		$a_subject
     * @param	int 		$a_to_confirm
     * @param	int			$a_sub_time
     * @return bool
     */
    public function addToList($a_usr_id, $a_subject = '', $a_to_confirm = self::REQUEST_NOT_TO_CONFIRM, $a_sub_time = null)
// fau.
    {
        global $DIC;

        $ilAppEventHandler = $DIC['ilAppEventHandler'];
        $ilLog = $DIC->logger()->sess();
        
        if (!parent::addToList($a_usr_id)) {
            return false;
        }
        
        $ilLog->info('Raise new event: Modules/Session addToWaitingList');
        $ilAppEventHandler->raise(
            "Modules/Session",
            'addToWaitingList',
            array(
                    'obj_id' => $this->getObjId(),
                    'usr_id' => $a_usr_id
                )
            );
        return true;
    }
}
