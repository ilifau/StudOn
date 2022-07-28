<?php

namespace FAU\Ilias;

use ilObjCourse;
use ilCourseParticipants;
use ilCourseWaitingList;
use ILIAS\DI\Container;

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
     * Constructor
     */
    public function __construct(Container $dic, $object, $participants = null, $waitingList = null)
    {
       parent::__construct($dic, $object, $participants, $waitingList);

       if (!isset($this->participants)) {
           $this->participants = ilCourseParticipants::_getInstanceByObjId($this->object->getId());
       }
       if (!isset($this->waitingList)) {
           $this->waitingList = new ilCourseWaitingList($this->object->getId());
       }
    }

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

    public function isMembershipLimited() : bool
    {
        return (bool) $this->object->isSubscriptionMembershipLimited();
    }

    public function getMaxMembers() : bool
    {
        return (int) $this->object->getSubscriptionMaxMembers();
    }

    public function isWaitingListEnabled() : bool
    {
        return (bool) $this->object->enabledWaitingList();
    }

    protected function getMemberRoleId() : int
    {
        return (int) $this->participants->getRoleId(IL_CRS_MEMBER);
    }
}