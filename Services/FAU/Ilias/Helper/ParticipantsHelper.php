<?php

namespace FAU\Ilias\Helper;
use ilCourseUserData;
use ilMailMimeSenderUserById;
use ilObjUser;
use ilUtil;
use ilMimeMail;

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

    // fau: courseUdf - new function sendExternalNotification

    /**
     * @param ilObjCourse|ilObjGroup $a_object
     * @param ilObjUser	$a_user
     * @param bool	$a_changed	registration is changed, not added
     */
    public function sendExternalNotifications($a_object, $a_user, $a_changed = false)
    {
        global $ilSetting;

        $user_data = ilCourseUserData::getFieldsWithData($a_object->getId(), $a_user->getId());
        $notifications = array();

        /** @var ilCourseDefinedFieldDefinition $field */
        foreach ($user_data as $data) {
            $field = $data['field'];
            $value = $data['value'];
            if ($field->getType() == IL_CDF_TYPE_EMAIL && $field->getEmailAuto() && ilUtil::is_email($value)) {
                $notifications[$value] = $field->getEmailText();
            }
        }

        if (empty($notifications)) {
            return;
        }

        // prepare common data
        $sender = new ilMailMimeSenderUserById($ilSetting, $a_user->getId());

        $sender_address = $sender->getReplyToAddress();
        $cc_address = '';
        foreach ($this->getNotificationRecipients() as $admin_id) {
            $address = ilObjUser::_lookupEmail($admin_id);
            if (!empty($address)) {
                $cc_address = $address;
                break;
            }
        }
        if (!empty($cc_address)) {
            $reply_link = '<a href="mailto:' . $sender_address . '?cc=' . $cc_address . '">' . $sender_address . ', ' . $cc_address . '</a>';
        } else {
            $reply_link = '<a href="mailto:' . $sender_address . '">' . $sender_address . '</a>';
        }


        if ($a_changed) {
            $subject = sprintf($this->lng->txt('mem_external_notification_subject_changed'), $a_user->getFullname(), $a_object->getTitle());
        } else {
            $subject = sprintf($this->lng->txt('mem_external_notification_subject'), $a_user->getFullname(), $a_object->getTitle());
        }

        $sep = ":\n";
        $list = array();
        $list[] = $this->lng->txt('user') . $sep . $a_user->getFullname();
        $list[] = $this->lng->txt('email') . $sep . $a_user->getEmail();
        $list[] = $this->lng->txt('title') . $sep . $a_object->getTitle();
        if ($a_object->getType() == 'crs' && !empty($a_object->getSyllabus())) {
            $list[] = $this->lng->txt('crs_syllabus') . $sep . $a_object->getSyllabus();
        }

        foreach ($user_data as $data) {
            /** @var ilCourseDefinedFieldDefinition $field */
            $field = $data['field'];
            if (!empty($data['value'])) {
                $list[] = $field->getName() . $sep . $data['value'];
            }
        }

        $sub_text = implode("\n\n", $list);


        // send the notifications

        foreach ($notifications as $to_address => $text) {
            $body = str_replace('[reply-to]', $reply_link, $text) . "\n\n" . $sub_text;

            $mmail = new ilMimeMail();
            $mmail->To($to_address);
            $mmail->From($sender);
            $mmail->Subject($subject);
            $mmail->Body(nl2br($body));
            $mmail->Send();
        }
    }
    // fau.    
}