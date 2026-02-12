<?php

namespace FAU\Ilias\Helper;
use ilCourseMembershipMailNotification;
use ilWaitingList;

/**
 * trait for providing additional ilCourseParticipants methods
 */
trait CourseParticipantsHelper 
{
    // fau: fairSub#78 - new function sendAddedToWaitingList()
    /**
     * Send notification to user about being added to the waiting list
     * @param int			$a_usr_id
     * @param ilWaitingList	$a_waiting_list
     * @return bool
     */
    public function sendAddedToWaitingList($a_usr_id, $a_waiting_list = null)
    {
        $mail = new ilCourseMembershipMailNotification();
        $mail->setType(ilCourseMembershipMailNotification::TYPE_WAITING_LIST_MEMBER);
        $mail->setRefId($this->ref_id);
        $mail->setWaitingList($a_waiting_list);
        $mail->setRecipients(array($a_usr_id));
        $mail->send();
        return true;
    }
    // fau.
}