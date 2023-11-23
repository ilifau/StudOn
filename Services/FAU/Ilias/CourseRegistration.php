<?php

namespace FAU\Ilias;

use ilObjCourse;
use ilCourseParticipants;
use ilCourseWaitingList;
use ilMailNotification;
use ilCourseMembershipMailNotification;

/**
 * Extension of the registration with course specific functions
 */
class CourseRegistration extends Registration
{
    /** @var ilObjCourse */
    protected  $object;

    /** @var ilCourseParticipants */
    protected  $participants;

    /** @var ilCourseWaitingList */
    protected  $waitingList;


    /**
     * Init the subscription type from course constant
     */
    protected function initSubType() : void
    {
        switch ($this->object->getSubscriptionType()) {
            case IL_CRS_SUBSCRIPTION_DIRECT:
                $this->subType = self::subDirect;
                break;
            case IL_CRS_SUBSCRIPTION_PASSWORD:
                $this->subType = self::subPassword;
                break;
            case IL_CRS_SUBSCRIPTION_CONFIRMATION:
                $this->subType = self::subConfirmation;
                break;
            case IL_CRS_SUBSCRIPTION_OBJECT:
                $this->subType = self::subObject;
                break;
            case IL_CRS_SUBSCRIPTION_DEACTIVATED:
            default:
                $this->subType = self::subDeactivated;
                break;
        }
    }

    public function hasMaxMembers() : bool
    {
        return (bool) $this->object->isSubscriptionMembershipLimited() && !empty($this->object->getSubscriptionMaxMembers());
    }

    public function getMaxMembers() : int
    {
        return (int) $this->object->getSubscriptionMaxMembers();
    }

    public function isWaitingListEnabled() : bool
    {
        return (bool) $this->object->enabledWaitingList();
    }

    protected function getMemberRoleConstant() : int
    {
        return IL_CRS_MEMBER;
    }

    public function getNotificationTypeAddedAdmins() : int
    {
        return $this->participants->NOTIFY_ADMINS;
    }

    public function getNotificationTypeAddedMember() : int
    {
        return $this->participants->NOTIFY_REGISTERED;
    }

    public function getNotificationTypeRefusedMember() : int
    {
        return $this->participants->NOTIFY_DISMISS_SUBSCRIBER;
    }

    public function getMembershipMailNotification() : ilMailNotification
    {
        return new ilCourseMembershipMailNotification();
    }

    protected function checkLPStatusSync(int $user_id) : void
    {
        $this->object->checkLPStatusSync($user_id);
    }

}