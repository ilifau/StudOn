<?php

namespace FAU\Ilias\Helper;

/**
 * trait for providing additional ilCourseWaitingList methods
 */
trait CourseWaitingListHelper 
{
    // fau: fairSub#70 - add subject, to_confirm and sub_time as parameter, avoid re-reading
    /**
     * add to list
     *
     * @param 	int 		$a_usr_id
     * @param 	string		$a_subject
     * @param	int 		$a_to_confirm
     * @param	int			$a_sub_time
     * @return bool
     */
    public function addToList($a_usr_id, $a_subject = '', $a_to_confirm = WaitingListConstantsHelper::REQUEST_NOT_TO_CONFIRM, $a_sub_time = null)
    {
        global $DIC;

        $ilAppEventHandler = $DIC['ilAppEventHandler'];
        $ilLog = $DIC['ilLog'];
        
        if (!parent::addToList($a_usr_id, $a_subject, $a_to_confirm, $a_sub_time)) {
            return false;
        }
        
        $ilLog->write(__METHOD__ . ': Raise new event: Modules/Course addToList');
        $ilAppEventHandler->raise(
            "Modules/Course",
            'addToWaitingList',
            array(
                    'obj_id' => $this->getObjId(),
                    'usr_id' => $a_usr_id
                )
            );
        return true;
    }
// fau.
}