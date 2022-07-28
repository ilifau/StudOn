<?php

namespace FAU\Ilias;

use ILIAS\DI\Container;
use ilParticipants;
use ilWaitingList;
use ilObjUser;
use ilObjGroup;
use ilObjCourse;
use ilGroupParticipants;
use ilGroupWaitingList;

/**
 * Base class handling course or group registrations
 * Used by the Registration GUIs
 */
abstract class Registration
{
    // action constants
    const addAsMember = 'addAsMember';
    const addToWaitingList = 'addToWaitingList';
    const notifyAdded = 'notifyAdded';
    const notifyAddedToWaitingList = 'notifyAddedToWaitingList';
    const showLimitReached = 'showLimitReached';
    const showAlreadyMember = 'showAlreadyMember';
    const showAddedToWaitingListFair = 'showAddedToWaitingListFair';
    const showAlreadyOnWaitingList = 'showAlreadyOnWaitingList';
    const showGenericFailure = 'showGenericFailure';

    const subDeactivated = 'subDeactivated';
    const subDirect = 'subDirect';
    const subConfirmation = 'subConfirmation';
    const subPassword = 'subPassword';
    const subObject = 'subObject';


    protected Container $dic;
    protected ilObjUser $user;

    /** @var ilObjCourse|ilObjGroup */
    protected $object;

    /** @var ilParticipants|null  */
    protected $participants;

    /** @var ilWaitingList|null  */
    protected $waitingList;

    protected string $subType = self::subDeactivated;
    protected string $nextAction = self::showGenericFailure;
    protected int $addingLimit = 0;

    /**
     * Constructor
     */
    public function __construct(Container $dic,  $object, $participants = null, $waitingList = null)
    {
        $this->dic = $dic;
        $this->user = $dic->user();

        $this->object = $object;
        $this->participants = $participants;
        $this->waitingList = $waitingList;

        $this->initSubType();
        $this->initActionAndLimit();
    }

    abstract protected function initSubType() : void;
    abstract public function isMembershipLimited() : bool;
    abstract public function getMaxMembers() : bool;
    abstract public function isWaitingListEnabled() : bool;
    abstract protected function getMemberRoleId(): int;

    /**
     * Init the next action and the adding limit
     */
    protected function initActionAndLimit()
    {
        if ($this->participants->isAssigned($this->user->getId())) {
            // user is already a participant
            $this->nextAction = self::showAlreadyMember;
        }
        elseif ($this->subType == self::subConfirmation) {
            // always add requests to be confirmed to the waiting list (to keep them in the order)
            $this->nextAction = self::addToWaitingList;
        }
        elseif ($this->object->inSubscriptionFairTime()) {
            // always add to the waiting list if in fair time
            $this->nextAction = self::addToWaitingList;
        }
        elseif ($this->isMembershipLimited() && $this->getMaxMembers() > 0) {

            if ($this->isWaitingListActive()) {
                if ($this->getWaitingList()->getCountUsers() >= $this->getFreePlaces()) {
                    // add to waiting list if all free places have waiting candidates
                    $this->nextAction = self::addToWaitingList;
                }
                else {
                    // jump over the waiting candidates
                    $this->nextAction = self::addAsMember;
                    $this->addingLimit = $this->getMaxMembers() - $this->getWaitingList()->getCountUsers();
                }
            }
            else {
                // waiting list not active => try a direct join
                $this->nextAction = self::addAsMember;
                $this->addingLimit = $this->getMaxMembers();
            }
        }
        else {
            // no limit => do a direct join
            $this->nextAction = self::addAsMember;
            $this->addingLimit = 0;
        }
    }

    /**
     * Handle a subscription request
     */
    public function handleRequest(string $subject, array $group_ref_ids, int $module_id)
    {
        ///////
        // 1. Handle Parallel Group Selection (may override the next action)
        //////
        $directGroups = [];
        $waitingGroups = [];
        $groups = $this->dic->fau()->ilias()->objects()->getParallelGroupsInfos($this->object->getRefId());
        if ($this->object->hasParallelGroups() && !empty($group_ref_ids)) {
            foreach ($group_ref_ids as $ref_id) {
                foreach ($groups as $group) {
                    if ((int) $ref_id == $group->getRefId()) {
                        if ($group->isSubscriptionPossible()) {
                            $waitingGroups[] = $group;
                        }
                        if ($group->isDirectJoinPossible() && $this->isDirectJoinPossible()) {
                            $directGroups[] = $group;
                        }
                    }
                }
            }
        }
        if (!empty($waitingGroups) && empty($directGroups)) {
            // force adding to the waiting list if no selected group can be directly joined
            $this->nextAction = self::addToWaitingList;
        }

        /////
        // 2. Try a direct join to the course and avoid race conditions
        ////
        if ( $this->nextAction == self::addAsMember) {

            if ($this->participants->addLimited($this->user->getId(), IL_CRS_MEMBER, $this->addingLimit)) {
                // member could be added
                $this->nextAction = self::notifyAdded;
            }
            elseif ($this->dic->rbac()->review()->isAssigned($this->user->getId(), $this->getMemberRoleId())) {
                // may have been added by a parallel request
                $this->nextAction = self::showAlreadyMember;
            }
            elseif ($this->isWaitingListActive()) {
                // direct join failed but subscription is possible
                $this->nextAction = self::addToWaitingList;
            }
            else {
                // maximum members reached and no list active
                $this->nextAction = self::showLimitReached;
            }
        }

        /////
        // 3. Try a direct join to a parallel group and avoid race conditions
        ////
        if ($this->nextAction == self::notifyAdded && !empty($directGroups)) {

            $addedGroup = null;
            foreach ($directGroups as $group) {
                $groupParticipants = new ilGroupParticipants($group->getObjId());
                if ($groupParticipants->addLimited($this->user->getId(), IL_GRP_MEMBER, $group->getRegistrationLimit())) {
                    $addedGroup = $group;
                    break;
                }
                elseif ($this->dic->rbac()->review()->isAssigned($this->user->getId(), $groupParticipants->getRoleId(IL_GRP_MEMBER))) {
                    $this->nextAction = self::showAlreadyMember;
                    break;
                }
            }
            // handle failed direct adding to a group - revert the course join
            if (empty($addedGroup)) {
                $this->participants->delete($this->user->getId());
                if (!empty($waitingGroups)) {
                    $this->nextAction  = self::addToWaitingList;
                }
                else {
                    $this->nextAction  = self::showLimitReached;
                }
            }
        }

        /////
        // 4. perform the adding to the waiting list (this may set a new action)
        ////
        if ($this->nextAction == self::addToWaitingList) {
            $to_confirm = ($this->subType == self::subConfirmation) ? ilWaitingList::REQUEST_TO_CONFIRM : ilWaitingList::REQUEST_NOT_TO_CONFIRM;
            $sub_time = $this->object->inSubscriptionFairTime() ? $this->object->getSubscriptionFair() : time();

            if ($this->getWaitingList()->addWithChecks($this->user->getId(), $this->getMemberRoleId(), $subject, $to_confirm, $sub_time)) {
                if ($this->object->inSubscriptionFairTime($sub_time)) {
                    // show info about adding in fair time
                    $this->nextAction = self::showAddedToWaitingListFair;
                } else {
                    // maximum members reached
                    $this->nextAction = self::notifyAddedToWaitingList;
                }

                //add to the waiting lists of the selected groups
                foreach ($waitingGroups as $group) {
                    $groupParticipants = new ilGroupParticipants($group->getObjId());
                    $groupList = new ilGroupWaitingList($group->getObjId());
                    $groupList->addWithChecks($this->user->getId(), $groupParticipants->getRoleId(IL_GRP_MEMBER),
                        $_POST['subject'], $to_confirm, $sub_time);
                }


            } elseif ($this->dic->rbac()->review()->isAssigned($this->user->getId(), $this->getMemberRoleId())) {
                $this->nextAction = self::showAlreadyMember;
            }
            elseif (ilWaitingList::_isOnList($this->user->getId(), $this->object->getId())) {
                // check the failure of adding to the waiting list
                $this->nextAction = self::showAlreadyOnWaitingList;
            } else {
                // show an unspecified error
                $this->nextAction = self::showGenericFailure;
            }
        }


    }

    /**
     * Is it possible to enter the course without waiting list
     */
    public function isDirectJoinPossible()
    {
        return $this->nextAction == self::addAsMember;
    }


    /**
     * @see ilRegistrationGUI::isWaitingListActive()
     */
    public function isWaitingListActive()
    {
        // todo: always activate the waiting list for a parallel group

        if ($this->object->inSubscriptionFairTime()) {
            return true;
        }
        if (!$this->isWaitingListEnabled() or !$this->isMembershipLimited()) {
            return false;
        }
        if (!$this->getMaxMembers()) {
            return false;
        }

        return (!$this->getFreePlaces() || $this->getWaitingList()->getCountUsers());
    }


    /**
     * Get the waiting list object
     */
    public function getWaitingList(): ?ilWaitingList
    {
        return $this->waitingList;
    }

    /**
     * Get the participants object
     */
    public function getParticipants() : ?ilParticipants
    {
        return $this->participants;
    }

    /**
     * Get the subscription type
     */
    public function getSubType() : string
    {
        return $this->subType;
    }

    /**
     * Set the subscription type
     */
    public function setSubType(string $subType) : void
    {
        $this->subType = $subType;
        $this->initActionAndLimit();
    }

    /**
     * Get the next action
     */
    public function getNextAction() : string
    {
        return $this->nextAction;
    }

    /**
     * Get the number of free places
     */
    public function getFreePlaces() : int
    {
        return max(0, $this->getMaxMembers() - $this->participants->getCountMembers());
    }
}