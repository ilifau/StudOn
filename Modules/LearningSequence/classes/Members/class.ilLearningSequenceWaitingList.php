<?php

declare(strict_types=1);
use FAU\Ilias\Helper\WaitingListConstantsHelper;
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

class ilLearningSequenceWaitingList extends ilWaitingList
{
    // fau: fairSub - add subject, to_confirm and sub_time as parameter
    public function addToList(int $usr_id, string $a_subject = '', int $a_to_confirm = WaitingListConstantsHelper::REQUEST_NOT_TO_CONFIRM, ?int $a_sub_time = null): bool
    // fau.
    {
        global $DIC;

        $app_event_handler = $DIC->event();
        $log = $DIC->logger();

        // fau: fairSub - add subject, to_confirm and sub_time as parameter
        if (!parent::addToList($usr_id, $a_subject, $a_to_confirm, $a_sub_time)) {
        // fau.
            return false;
        }

        /** @noinspection PhpUndefinedMethodInspection */
        $log->lso()->info('Raise new event: Modules/LearningSerquence addToList.');
        $app_event_handler->raise(
            "Modules/LearningSequence",
            'addToWaitingList',
            array(
                'obj_id' => $this->getObjId(),
                'usr_id' => $usr_id
            )
        );

        return true;
    }
}
