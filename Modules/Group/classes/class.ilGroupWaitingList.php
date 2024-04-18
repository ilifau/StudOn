<?php
/*
        +-----------------------------------------------------------------------------+
        | ILIAS open source                                                           |
        +-----------------------------------------------------------------------------+
        | Copyright (c) 1998-2006 ILIAS open source, University of Cologne            |
        |                                                                             |
        | This program is free software; you can redistribute it and/or               |
        | modify it under the terms of the GNU General Public License                 |
        | as published by the Free Software Foundation; either version 2              |
        | of the License, or (at your option) any later version.                      |
        |                                                                             |
        | This program is distributed in the hope that it will be useful,             |
        | but WITHOUT ANY WARRANTY; without even the implied warranty of              |
        | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
        | GNU General Public License for more details.                                |
        |                                                                             |
        | You should have received a copy of the GNU General Public License           |
        | along with this program; if not, write to the Free Software                 |
        | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
        +-----------------------------------------------------------------------------+
*/

include_once('./Services/Membership/classes/class.ilWaitingList.php');

/**
* Waiting list for groups
*
* @author Stefan Meyer <smeyer.ilias@gmx.de>
* @version $Id$
*
* @ingroup ModulesGroup
*/

class ilGroupWaitingList extends ilWaitingList
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

    {
        global $DIC;

        $ilAppEventHandler = $DIC['ilAppEventHandler'];
        $ilLog = $DIC['ilLog'];
        
        if (!parent::addToList($a_usr_id, $a_subject, $a_to_confirm, $a_sub_time)) {
            return false;
        }

        $GLOBALS['DIC']->logger()->grp()->info('Raise new event: Modules/Group addToWaitingList.');
        $ilAppEventHandler->raise(
            "Modules/Group",
            'addToWaitingList',
            array(
                    'obj_id' => $this->getObjId(),
                    'usr_id' => $a_usr_id,
                    'subject' => $a_subject,
                    'to_confirm' => $a_to_confirm,
                    'sub_time' => $a_sub_time
                )
            );
        return true;
    }
    // fau.

    // fau: regLog - override addWithChecks to raise a course event
    public function addWithChecks($a_usr_id, $a_rol_id, $a_subject = '', $a_to_confirm = self::REQUEST_NOT_TO_CONFIRM, $a_sub_time = null, $a_module_id = null)
    {
        global $DIC;

        $ilAppEventHandler = $DIC['ilAppEventHandler'];
        $ilLog = $DIC['ilLog'];

        if (!parent::addWithChecks($a_usr_id, $a_rol_id, $a_subject , $a_to_confirm, $a_sub_time, $a_module_id)) {
            return false;
        }

        $ilLog->write(__METHOD__ . ': Raise new event: Modules/Group addToWaitingList');
        $ilAppEventHandler->raise(
            "Modules/Group",
            'addToWaitingList',
            array(
                'obj_id' => $this->getObjId(),
                'usr_id' => $a_usr_id,
                'subject' => $a_subject,
                'to_confirm' => $a_to_confirm,
                'sub_time' => $a_sub_time,
                'module_id' => $a_module_id
            )
        );
        return true;
    }
    // fau.


    // fau: regLog - add function removeFromList to raise an event
    /**
     * Remove from waiting list and raise event
     * @param int $a_usr_id
     */
    public function removeFromList($a_usr_id)
    {
        global $DIC;

        $ilAppEventHandler = $DIC['ilAppEventHandler'];
        $ilLog = $DIC['ilLog'];

        if (!parent::removeFromList($a_usr_id)) {
            return false;
        }

        $ilLog->write(__METHOD__ . ': Raise new event: Modules/Group removeFromWaitingList');
        $ilAppEventHandler->raise(
            "Modules/Group",
            'removeFromWaitingList',
            array(
                'obj_id' => $this->getObjId(),
                'usr_id' => $a_usr_id,
            )
        );
        return true;
    }
    // fau.

}
