<?php

namespace FAU\Ilias;

use ilObjGroup;
use ilGroupParticipants;
use ilGroupWaitingList;
use ILIAS\DI\Container;
use ilMailNotification;
use ilGroupMembershipMailNotification;

/**
 * Extension of the registration with group specific functions
 */
class GroupRegistration extends Registration
{
    /** @var ilObjGroup */
    protected  $object;

    /** @var ilGroupParticipants */
    protected  $participants;

    /** @var ilGroupWaitingList */
    protected  $waitingList;


    /**
     * Init the subscription type from the group constant
     */
    protected function initSubType() : void
    {
        switch ($this->object->getRegistrationType()) {
            case GRP_REGISTRATION_DIRECT:
                $this->subType = self::subDirect;
                break;
            case GRP_REGISTRATION_PASSWORD:
                $this->subType = self::subPassword;
                break;
            case GRP_REGISTRATION_REQUEST:
                $this->subType = self::subConfirmation;
                break;
            case GRP_REGISTRATION_OBJECT:
                $this->subType = self::subObject;
                break;
            case GRP_REGISTRATION_DEACTIVATED:
            default:
                $this->subType = self::subDeactivated;
                break;
        }
    }

    public function isMembershipLimited() : bool
    {
        return (bool) $this->object->isMembershipLimited();
    }

    public function getMaxMembers() : bool
    {
        return (bool) $this->object->getMaxMembers();
    }

    public function isWaitingListEnabled() : bool
    {
        return (bool) $this->object->isWaitingListEnabled();
    }

    protected function getMemberRoleId() : int
    {
        return (int) $this->participants->getRoleId(IL_GRP_MEMBER);
    }

    protected function getAddedNotificationTypeAdmins() : int
    {
        return ilGroupMembershipMailNotification::TYPE_NOTIFICATION_REGISTRATION;
    }

    protected function getAddedNotificationTypeMember() : int
    {
        return ilGroupMembershipMailNotification::TYPE_SUBSCRIBE_MEMBER;
    }

    protected function getMembershipMailNotification() : ilMailNotification
    {
        return new ilGroupMembershipMailNotification();
    }

    protected function checkLPStatusSync(int $user_id) : void
    {
        // nothing to do for groups
    }

}