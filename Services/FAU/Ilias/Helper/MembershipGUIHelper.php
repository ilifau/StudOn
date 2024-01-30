<?php

namespace FAU\Ilias\Helper;
use ilConfirmationGUI;
use ilObjUser;
/**
 * trait for providing additional ilMembershipGUI methods
 */
trait MembershipGUIHelper 
{
    // fau: fairSub#87 - new function confirmAcceptOnListObject()
    /**
     * Confirm to accept subscription requests on the waiting list
     */
    public function confirmAcceptOnList()
    {
        global $DIC;

        if (!empty($_GET['member_id'])) {
            $_POST["waiting"] = array($_GET['member_id']);
        }

        /** @var ilWaitingList $wait */
        $wait = $this->initWaitingList();

        $requests = array();
        foreach ((array) $_POST["waiting"] as $user_id) {
            if ($wait->isToConfirm($user_id)) {
                $requests[] = (int) $user_id;
            }
        }

        if (empty($requests)) {
            ilUtil::sendFailure($this->lng->txt("sub_select_one_request"), true);
            $this->ctrl->redirect($this, 'participants');
        }

        $c_gui = new ilConfirmationGUI();

        // set confirm/cancel commands
        $c_gui->setFormAction($this->ctrl->getFormAction($this, "acceptOnList"));
        if ($DIC->fau()->ilias()->objects()->isParallelGroupOrParentCourse($this->getParentObject())) {
            $add_to_question = '<br><small>' . $this->lng->txt('fau_sub_accept_all_groups') . '</small>';
        }
        $c_gui->setHeaderText($this->lng->txt("sub_confirm_request_question") . $add_to_question);
        $c_gui->setCancel($this->lng->txt("cancel"), "participants");
        $c_gui->setConfirm($this->lng->txt("confirm"), "acceptOnList");

        foreach ($requests as $waiting) {
            $name = ilObjUser::_lookupName($waiting);

            $c_gui->addItem(
                'waiting[]',
                $name['user_id'],
                $name['lastname'] . ', ' . $name['firstname'] . ' [' . $name['login'] . ']',
                ilUtil::getImagePath('icon_usr.svg')
            );
        }

        $this->tpl->setContent($c_gui->getHTML());
    }
    // fau.
    // fau: fairSub#88 - accept subscription request on the waiting list
    /**
     * Accept subscription request(s) on the waiting list
     * try to fill free places with these users
     */
    public function acceptOnList()
    {
        global $DIC;

        if (!count($_POST['waiting'])) {
            ilUtil::sendFailure($this->lng->txt("sub_select_one_request"), true);
            $this->ctrl->redirect($this, 'participants');
            return false;
        }

        // get the affected waiting lists
        // get the registration object for further processing
        // for parallel groups get the registration of the parent course
        // this will send a notification related to the course because the group is not yet accessible to the user
        if ($DIC->fau()->ilias()->objects()->isParallelGroupOrParentCourse($this->getParentObject())) {
            $waiting_lists = $DIC->fau()->ilias()->objects()->getCourseAndParallelGroupsWaitingLists($this->getParentObject()->getRefId());
            if ($this->getParentObject()->isParallelGroup()) {
                $course_ref_id = $DIC->fau()->ilias()->objects()->findParentIliasCourse($this->getParentObject()->getRefId());
                $registration = $DIC->fau()->ilias()->getRegistration(new ilObjCourse($course_ref_id));
            } else {
                $registration = $DIC->fau()->ilias()->getRegistration($this->getParentObject());
            }
        }
        else {
            $waiting_lists = [$this->initWaitingList()];
            $registration = $DIC->fau()->ilias()->getRegistration($this->getParentObject());
        }

        // accept users, but keep them on the waiting list
        $accepted = [];
        foreach ($_POST["waiting"] as $user_id) {
            foreach ($waiting_lists as $list) {
                if ($list->isOnList($user_id)) {
                    $list->acceptOnList($user_id);
                }
            }
            $accepted[] = $user_id;
        }

        // try to fill free places
        // call it with 'manual' mode to suppress the sending of admin notifications
        $added = $registration->doAutoFill(true);

        // notify all users that were accepted but kept on the waiting list
        $accepted_waiting = array_diff($accepted, $added);
        if (!empty($accepted_waiting)) {
            $mail = $registration->getMembershipMailNotification();
            $mail->setType(\FAU\Ilias\Registration::notificationAcceptedStillWaiting);
            $mail->setRefId($registration->getObject()->getRefId());
            $mail->setWaitingList($registration->getObject()->getWaitingList());
            $mail->setRecipients($accepted_waiting);
            $mail->send();
        }

        // show success about accepted and added users
        $messages = array();
        $messages[] = sprintf($this->lng->txt(count($accepted) == 1 ? 'sub_confirmed_request' : 'sub_confirmed_requests'), count($accepted));
        if (!empty($added)) {
            $messages[] = sprintf($this->lng->txt(count($added) == 1 ? 'sub_added_member' : 'sub_added_members'), count($added));
        }
        ilUtil::sendSuccess(implode('<br />', $messages), true);
        $this->ctrl->redirect($this, 'participants');
    }
    // fau.

    // fau: fairSub#89 - new function confirmFillFreePlacesObject
    /**
     * Confirm to fill the free places
     */
    public function confirmFillFreePlaces()
    {
        $c_gui = new ilConfirmationGUI();
        $c_gui->setFormAction($this->ctrl->getFormAction($this, "fillFreePlaces"));
        $c_gui->setHeaderText($this->lng->txt('sub_fill_free_places_question'));
        $c_gui->setCancel($this->lng->txt("cancel"), "participants");
        $c_gui->setConfirm($this->lng->txt("confirm"), "fillFreePlaces");

        $this->tpl->setContent($c_gui->getHTML());
    }
    // fau.


    // fau: fairSub#90 - new function fillFreePlacesObject()
    /**
     * Fill free places from the waiting list
     */
    public function fillFreePlaces()
    {
        global $DIC;
        /** @var ilObjCourse|ilObjGroup $object */
        $object = $this->getParentObject();
        $added = $DIC->fau()->ilias()->getRegistration($object)->doAutoFill(true);

        if (count($added)) {
            ilUtil::sendSuccess(sprintf($this->lng->txt(count($added) == 1 ? 'sub_added_member' : 'sub_added_members'), count($added)), true);
            $this->ctrl->redirect($this, 'participants');
        } else {
            ilUtil::sendFailure($this->lng->txt('sub_no_member_added'), true);
            $this->ctrl->redirect($this, 'participants');
        }
    }
    // fau.


}