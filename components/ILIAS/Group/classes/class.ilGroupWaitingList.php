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

declare(strict_types=1);
/**
* Waiting list for groups
*
* @author Stefan Meyer <smeyer.ilias@gmx.de>
*
* @ingroup components\ILIASGroup
*/
class ilGroupWaitingList extends ilWaitingList
{
    // fau: fairSub - add subject, to_confirm and sub_time as parameter, avoid re-reading
    public function addToList(int $a_usr_id, string $a_subject = '', int $a_to_confirm = WaitingListConstantsHelper::REQUEST_NOT_TO_CONFIRM, ?int $a_sub_time = null): bool
    {
        global $DIC;

        $ilAppEventHandler = $DIC['ilAppEventHandler'];
        $ilLog = $DIC['ilLog'];

        if (!parent::addToList($a_usr_id, $a_subject, $a_to_confirm, $a_sub_time)) {
            return false;
        }

        $GLOBALS['DIC']->logger()->grp()->info('Raise new event: Modules/Group addToList.');
        $ilAppEventHandler->raise(
            "components/ILIAS/Group",
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
}
