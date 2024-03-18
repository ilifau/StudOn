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

    /**
     * fau: heavySub - Notify the success of adding a user to a role with limited members
     * fau: fairSub - Notify the success of adding a user to a role with limited members
     *
     * @access public
     * @param 	int $a_usr_id	user id
     * @param 	int $a_role		role IL_CRS_MEMBER | IL_GRP_MEMBER
     */

     public function addLimitedSuccess(int $a_usr_id, int $a_role)
     {
         global $DIC;
 
         switch ($a_role) {
             case self::IL_CRS_MEMBER:
             case self::IL_GRP_MEMBER:
                 $this->members[] = $a_usr_id;
                 break;
         }
         $this->participants[] = $a_usr_id;
 
         // Delete subscription request
         $this->deleteSubscriber($a_usr_id);
         ilWaitingList::deleteUserEntry($a_usr_id, $this->obj_id);
 
         $DIC->event()->raise(
             $this->getComponent(),
             "addParticipant",
             array(
                 'obj_id' => $this->obj_id,
                 'usr_id' => $a_usr_id,
                 'role_id' => $a_role,
                 'type' => 'crs')
         );
     }
     // fau.    
}