<?php

declare(strict_types=1);

/**
 * @author Daniel Weise <daniel.weise@concepts-and-training.de>
 */
class ilLearningSequenceWaitingList extends ilWaitingList
{
    // fau: fairSub - add subject, to_confirm and sub_time as parameter
    public function addToList($usr_id, $a_subject = '', $a_to_confirm = self::REQUEST_NOT_TO_CONFIRM, $a_sub_time = null)
    // fau.
    {
        global $DIC;

        $app_event_handler = $dic->event();
        $log = $dic->logger();

        // fau: fairSub - add subject, to_confirm and sub_time as parameter
        if (!parent::addToList($usr_id, $a_subject, $a_to_confirm, $a_sub_time)) {
        // fau.
            return false;
        }

        $log()->lso()->info('Raise new event: Modules/LearningSerquence addToList.');
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
