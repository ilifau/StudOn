<?php

namespace FAU\Ilias\Helper;
use ilGroupMembershipMailNotification;

/**
 * trait for providing additional ilGroupParticipants methods
 */
trait GroupParticipantsHelper 
{
    
    // fau: fairSub - new function sendAddedToWaitingList()
    /**
     * Send notification to user about being added to the waiting list
     * @param int			$a_usr_id
     * @param ilWaitingList	$a_waiting_list
     * @return bool
     */
    public function sendAddedToWaitingList($a_usr_id, $a_waiting_list = null)
    {
        $mail = new ilGroupMembershipMailNotification();
        $mail->setType(ilGroupMembershipMailNotification::TYPE_WAITING_LIST_MEMBER);
        $mail->setRefId($this->ref_id);
        $mail->setWaitingList($a_waiting_list);
        $mail->setRecipients(array($a_usr_id));
        $mail->send();
        return true;
    }
    // fau.

    // fau: fairSub - new function sendSubscriptionRequestToAdmins()
    public function sendSubscriptionRequestToAdmins($a_usr_id)
    {
        $mail = new ilGroupMembershipMailNotification();
        $mail->setType(ilGroupMembershipMailNotification::TYPE_NOTIFICATION_REGISTRATION_REQUEST);
        $mail->setAdditionalInformation(array('usr_id' => $a_usr_id));
        $mail->setRefId($this->ref_id);
        $mail->setRecipients($this->getNotificationRecipients());
        $mail->send();
        return true;
    }
    // fau.
}