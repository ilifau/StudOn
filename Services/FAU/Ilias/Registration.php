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
use FAU\Ilias\Data\ContainerInfo;
use ilObjCourseGrouping;
use ilObjectFactory;
use ilObject;
use ilMailNotification;
use ilForumNotification;

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
    const showUpdatedWaitingList = 'showUpdatedWaitingList';
    const showGenericFailure = 'showGenericFailure';

    // subscription types
    const subDeactivated = 'subDeactivated';
    const subDirect = 'subDirect';
    const subConfirmation = 'subConfirmation';
    const subPassword = 'subPassword';
    const subObject = 'subObject';

    // notification types
    const notificationAdmissionMember = 20;
    const notificationAcceptedStillWaiting = 51;
    const notificationAutofillStillWaiting = 52;
    const notificationAutofillStillToConfirm = 53;
    const notificationAdminAutofillToConfirm = 63;

    protected Container $dic;
    protected ilObjUser $user;
    protected Repository $repo;

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
     * Info objects about contained parallel groups
     * @var ContainerInfo[]
     */
    protected $groups = [];

    /**
     * Constructor
     * @param ilObjCourse|ilObjGroup $object
     */
    public function __construct(Container $dic, $object, $participants = null, $waitingList = null)
    {
        $this->dic = $dic;
        $this->user = $dic->user();
        $this->repo = $dic->fau()->ilias()->repo();

        $this->object = $object;
        $this->participants = $participants;
        $this->waitingList = $waitingList;

        if (!isset($this->participants)) {
            $this->participants = $object->getMembersObject();
        }
        if (!isset($this->waitingList)) {
            $this->waitingList = $object->getWaitingList();
        }

        // read the info about the parallel groups
        if ($this->object->hasParallelGroups()) {
            $this->groups = $this->dic->fau()->ilias()->objects()->getParallelGroupsInfos($this->object->getRefId());
        }

        $this->initSubType();
        $this->initActionAndLimit();
    }

    // Hiding of type (course/group) differences
    abstract public function isMembershipLimited() : bool;
    abstract public function getMaxMembers() : int;
    abstract public function isWaitingListEnabled() : bool;
    abstract public function getMembershipMailNotification(): ilMailNotification;
    abstract public function getNotificationTypeAddedAdmins(): int;
    abstract public function getNotificationTypeAddedMember(): int;
    abstract public function getNotificationTypeRefusedMember(): int;
    abstract protected function initSubType() : void;
    abstract protected function checkLPStatusSync(int $user_id): void;
    abstract protected function getMemberRoleConstant(): int;



    /**
     * Init the subscription action to be performed and the limit for adding to the member role
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
                if ($this->waitingList->getCountUsers() >= $this->getFreePlaces()) {
                    // add to waiting list if all free places have waiting candidates
                    $this->nextAction = self::addToWaitingList;
                }
                else {
                    // jump over the waiting candidates
                    $this->nextAction = self::addAsMember;
                    $this->addingLimit = $this->getMaxMembers() - $this->waitingList->getCountUsers();
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
     * Simulate a parallel membership
     */
    protected function simulateParallelMembership()
    {
        $mem_rol_id = $this->getMemberRoleId();
        $query = "INSERT INTO rbac_ua (rol_id, usr_id) ".
            "VALUES (".
            $this->dic->database()->quote($mem_rol_id ,'integer').", ".
            $this->dic->database()->quote($this->user->getId() ,'integer').
            ")";
        $this->dic->database()->manipulate($query);
     }

    /**
     * Simulate a parallel registration request
     */
    protected function simulateParallelWaitingList()
    {
       $query = "INSERT INTO crs_waiting_list (obj_id, usr_id, sub_time, subject) ".
            "VALUES (".
            $this->dic->database()->quote($this->object->getId() ,'integer').", ".
            $this->dic->database()->quote($this->user->getId() ,'integer').", ".
            $this->dic->database()->quote(time() ,'integer').", ".
            $this->dic->database()->quote($_POST['subject'] ,'text')." ".
            ")";
       $this->dic->database()->manipulate($query);
    }


    /**
     * Perform a registration request
     * @see \ilCourseRegistrationGUI::add()
     * @todo: handle module selection
     */
    public function doRegistration(string $subject, array $group_ref_ids, int $module_id)
    {
        // ensure proper types
        $group_ref_ids = array_map('intval', $group_ref_ids);

        ///////
        // 1. Handle Parallel Group Selection (may override the next action)
        //////
        $directGroups = [];     // selected groups that allow a direct join
        $waitingGroups = [];    // selected groups that allow an adding to the waiting list (including direct groups)

        foreach ($this->groups as $group) {
            if (in_array($group->getRefId(), $group_ref_ids)) {
                if ($group->isSubscriptionPossible()) {
                    $waitingGroups[] = $group;
                }
                if ($this->isDirectJoinPossibleForGroup($group)) {
                    $directGroups[] = $group;
                }
            }
        }
        // force a direct join if possible for a group
        if (!empty($directGroups)) {
            $this->nextAction = self::addAsMember;
        }
        // force adding to the waiting list if no selected group can be directly joined
        if (!empty($waitingGroups) && empty($directGroups)) {
            $this->nextAction = self::addToWaitingList;
        }

        /////
        // 2. Try a direct join to the course and avoid race conditions
        ////
        if ($this->nextAction == self::addAsMember) {

            // For test of race condition
            // $this->simulateParallelMembership();

            if ($this->participants->addLimited($this->user->getId(), $this->getMemberRoleConstant(), $this->addingLimit)) {
                // member could be added
                $this->nextAction = self::notifyAdded;
                $this->checkLPStatusSync($this->user->getId());
            } elseif ($this->dic->rbac()->review()->isAssigned($this->user->getId(), $this->getMemberRoleId())) {
                // may have been added by a parallel request
                $this->nextAction = self::showAlreadyMember;
            } elseif ($this->isWaitingListActive()) {
                // direct join failed but subscription is possible
                $this->nextAction = self::addToWaitingList;
            } else {
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
                if ($groupParticipants->addLimited($this->user->getId(), IL_GRP_MEMBER,
                    $group->getRegistrationLimit())) {
                    $addedGroup = $group;
                    // successfully added - remove from all waiting lists
                    foreach ($waitingGroups as $group2) {
                        ilWaitingList::deleteUserEntry($this->user->getId(), $group2->getObjId());
                    }
                    break;
                } elseif ($this->dic->rbac()->review()->isAssigned($this->user->getId(),
                    $groupParticipants->getRoleId(IL_GRP_MEMBER))) {
                    $this->nextAction = self::showAlreadyMember;
                    break;
                }
            }
            // handle failed direct adding to a group - revert the course join
            if (empty($addedGroup)) {
                $this->participants->delete($this->user->getId());
                if (!empty($waitingGroups)) {
                    $this->nextAction = self::addToWaitingList;
                } else {
                    $this->nextAction = self::showLimitReached;
                }
            }
        }

        /////
        // 4. perform the adding to the waiting list (this may set a new action)
        ////
        if ($this->nextAction == self::addToWaitingList) {
            $to_confirm = $this->getNewToConfirm();
            $sub_time = $this->getNewSubTime();

            // For test of race condition
            // $this->simulateParallelWaitingList();

            // add to waiting list and avoid race condition
            $added = $this->waitingList->addWithChecks($this->user->getId(), $this->getMemberRoleId(),
                $subject, $to_confirm, $sub_time);

            // may have been directly added as member in a parallel request
            if ($this->dic->rbac()->review()->isAssigned($this->user->getId(), $this->getMemberRoleId())) {
                $this->nextAction = self::showAlreadyMember;
            } // may have been directly added to the waiting list in a parallel request
            elseif (ilWaitingList::_isOnList($this->user->getId(), $this->object->getId())) {

                // was already on list, so update the subject
                if (!$added) {
                    $this->waitingList->updateSubject($this->user->getId(), $subject);
                }

                // eventually update the waiting list of enclosed groups
                $this->updateGroupWaitingLists($subject, $group_ref_ids, $module_id, $to_confirm, $sub_time);

                if (!$added) {
                    $this->nextAction = self::showUpdatedWaitingList;
                }
                elseif ($this->object->inSubscriptionFairTime($sub_time)) {
                    // show info about adding in fair time
                    $this->nextAction = self::showAddedToWaitingListFair;
                }
                else {
                    // maximum members reached
                    $this->nextAction = self::notifyAddedToWaitingList;
                }
            }
            else {
                // show an unspecified error
                $this->nextAction = self::showGenericFailure;
            }
        }

        // send notification to user
        // send notification to admins
        // send external notifications for courseUdf
        switch ($this->getNextAction()) {
            case Registration::notifyAdded:
                $this->participants->sendNotification($this->getNotificationTypeAddedAdmins(), $this->user->getId());
                $this->participants->sendNotification($this->getNotificationTypeAddedMember(), $this->user->getId());
                $this->participants->sendExternalNotifications($this->object, $this->user);
                ilForumNotification::checkForumsExistsInsert($this->object->getRefId(), $this->user->getId());
                break;

            case Registration::notifyAddedToWaitingList:
                $this->participants->sendAddedToWaitingList($this->user->getId(), $this->waitingList);    // mail to user
                if ($this->subType == self::subConfirmation) {
                    $this->participants->sendSubscriptionRequestToAdmins($this->user->getId());           // mail to admins
                }
                $this->participants->sendExternalNotifications($this->object, $this->user);
                break;

            case Registration::showAddedToWaitingListFair:
                // no e-mail to subscriber needed because the place on the list is not relevant
                $this->participants->sendExternalNotifications($this->object, $this->user);
                break;
        }
    }

    /**
     * Update a registration request
     * @todo: handle module selection
     */
    public function doUpdate(string $subject, array $group_ref_ids, int $module_id)
    {
        // ensure proper types
        $group_ref_ids = array_map('intval', $group_ref_ids);

        // update from main object
        $this->waitingList->updateSubject($this->user->getId(), $subject);

        // eventually update the waiting lists of parallel groups
        $this->updateGroupWaitingLists($subject, $group_ref_ids, $module_id, $this->getNewToConfirm(), $this->getNewSubTime());
    }

    /**
     * Update the waiting list entries of parallel groups when a registration request is updated
     * @todo: handle module selection
     */
    protected function updateGroupWaitingLists(string $subject, array $group_ref_ids, int $module_id, int $to_confirm, int $sub_time)
    {
        foreach ($this->groups as $group) {
            $groupParticipants = new ilGroupParticipants($group->getObjId());
            $groupList = new ilGroupWaitingList($group->getObjId());

            if (!$group->isOnWaitingList() && in_array($group->getRefId(), $group_ref_ids)) {
                $groupList->addWithChecks($this->user->getId(), $groupParticipants->getRoleId(IL_GRP_MEMBER), $subject, $to_confirm, $sub_time);
            }
            elseif ($group->isOnWaitingList() && in_array($group->getRefId(), $group_ref_ids)) {
                $groupList->updateSubject($this->user->getId(), $subject);
            }
            elseif ($group->isOnWaitingList() && !in_array($group->getRefId(), $group_ref_ids)) {
                $groupList->removeFromList($this->user->getId());
            }
        }
    }

    /**
     * Cancel a registration request
     * @todo: handle module selection
     */
    public function removeUserSubscription(int $user_id)
    {
        // remove user from main object
        $this->waitingList->removeFromList($user_id);

        // remove user from parallel groups
        foreach ($this->groups as $group) {
            $groupList = new ilGroupWaitingList($group->getObjId());
            if ($groupList->isOnList($user_id)) {
                $groupList->removeFromList($user_id);
            }
        }
    }

    /**
     * Autofill free places from the waiting list
     * Fill only assignable users, treat manual fill, return filled users
     *
     * @param bool 		$manual		called manually by admin
     * @param bool 		$initial	called initially by cron job after fair time
     * @return int[]	added user ids
     */
    public function handleAutoFill(bool $manual = false, bool $initial = false)
    {
        $added_users = [];
        $last_fill = $this->object->getSubscriptionLastFill();

        // never fill if subscriptions are still fairly collected, even if manual call (should not happen)
        if ($this->object->inSubscriptionFairTime()) {
            return [];
        }

        if ($this->object->hasParallelGroups()) {
            return [];
        }
        if ($this->object->isParallelGroup()) {
            return [];
        }

        // check the conditions for autofill
        if ($manual
            || $initial
            || ($this->isWaitingListEnabled() && $this->object->hasWaitingListAutoFill())
        ) {

            $max = (int) $this->getMaxMembers();
            $now = $this->getCountMembers();

            if ($max == 0 || $max > $now) {
                // see assignFromWaitingListObject()
                $grouping_ref_ids = (array) ilObjCourseGrouping::_getGroupingItems($this->object);

                foreach ($this->waitingList->getAssignableUserIds($max == 0 ? null :  $max - $now) as $user_id) {
                    // check conditions for adding the member
                    if (
                        // user does no longer exist
                        ilObjectFactory::getInstanceByObjId($user_id, false) == false
                        // user is already assigned to the course
                        || $this->participants->isAssigned($user_id) == true
                        // user is already assigned to a grouped course
                        || ilObjCourseGrouping::_checkGroupingDependencies($this->object, $user_id) == false
                    ) {
                        // user can't be added - so remove from waiting lsit
                        $this->waitingList->removeFromList($user_id);
                        continue;
                    }

                    // avoid race condition
                    if ($this->participants->addLimited($user_id, $this->getMemberRoleConstant(), $max)) {
                        // user is now member
                        $added_users[] = $user_id;
                        $this->checkLPStatusSync($user_id);

                        // delete user from this and grouped waiting lists
                        $this->waitingList->removeFromList($user_id);
                        foreach ($grouping_ref_ids as $ref_id) {
                            ilWaitingList::deleteUserEntry($user_id, ilObject::_lookupObjId($ref_id));
                        }
                    } else {
                        // last free places are taken by parallel requests, don't try further
                        break;
                    }

                    $now++;
                    if ($max > 0 && $now >= $max) {
                        break;
                    }
                }

                // get the user that remain on the waiting list
                $waiting_users = $this->waitingList->getUserIds();

                // prepare notifications
                // the waiting list object is injected to allow the inclusion of the waiting list position
                $mail = $this->getMembershipMailNotification();
                $mail->setRefId($this->object->ref_id);
                $mail->setWaitingList($this->waitingList);

                // send notifications to added users
                if (!empty($added_users)) {
                    $mail->setType(self::notificationAdmissionMember);
                    $mail->setRecipients($added_users);
                    $mail->send();
                }

                // send notifications to waiting users if waiting list is automatically filled for the first time
                // the distinction between requests and subscriptions is done by send()
                if (empty($last_fill) && !empty($waiting_users)) {
                    $mail->setType(self::notificationAutofillStillWaiting);
                    $mail->setRecipients($waiting_users);
                    $mail->send();
                }

                // send notification to course admins if waiting users have to be confirmed and places are free
                // this should be done only once after the end of the fair time
                if ($initial
                    && $this->waitingList->getCountToConfirm() > 0
                    && ($max == 0 || $max > $now)) {
                    $mail->setType(self::notificationAdminAutofillToConfirm);
                    $mail->setRecipients($this->participants->getNotificationRecipients());
                    $mail->send();
                }
            }
        }

        // remember the fill date
        // this prevents further calls from the cron job
        $this->object->saveSubscriptionLastFill(time());

        return $added_users;
    }

    /**
     * Is it possible to enter the course without waiting list
     */
    public function isDirectJoinPossible() : bool
    {
        return $this->nextAction == self::addAsMember;
    }

    /**
     * Is it possible to enter a parallel group without waiting list
     */
    public function isDirectJoinPossibleForGroup(ContainerInfo $group) : bool
    {
       return ($group->isDirectJoinPossible() && $this->isDirectJoinPossible()) ||
           (!$group->hasMemLimit() && $this->subType != self::subConfirmation);
    }

    /**
     * @see ilRegistrationGUI::isWaitingListActive()
     */
    public function isWaitingListActive()
    {
        if ($this->object->inSubscriptionFairTime()) {
            return true;
        }
        if (!$this->isWaitingListEnabled() or !$this->isMembershipLimited()) {
            return false;
        }
        if (!$this->getMaxMembers()) {
            return false;
        }

        return (!$this->getFreePlaces() || $this->waitingList->getCountUsers());
    }

    /**
     * Get the assigned object
     * @return ilObjCourse|ilObjGroup
     */
    public function getObject() {
        return $this->object;
    }

    /**
     * Get the info objects about the parallel groups
     * @return ContainerInfo[]
     */
    public function getParallelGroupsInfos() : array
    {
        return $this->groups;
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
     * Get the number of members
     */
    public function getCountMembers() : int
    {
        return $this->participants->getCountMembers();
    }

    /**
     * Get the number of free places
     */
    public function getFreePlaces() : int
    {
        return max(0, $this->getMaxMembers() - $this->getCountMembers());
    }

    /**
     * Get the confirmation status to be set for new waiting list entries
     */
    protected function getNewToConfirm() : int
    {
        return ($this->subType == self::subConfirmation) ? ilWaitingList::REQUEST_TO_CONFIRM : ilWaitingList::REQUEST_NOT_TO_CONFIRM;
    }

    /**
     * Get the subscription time for new waiting list entries
     */
    protected function getNewSubTime() : int
    {
        return $this->object->inSubscriptionFairTime() ? $this->object->getSubscriptionFair() : time();
    }

    /**
     * Get the actual role id for members
     */
    protected function getMemberRoleId() : int
    {
        return (int) $this->participants->getRoleId($this->getMemberRoleConstant());
    }

    /**
     * Get if free places can be filled
     */
    public function canBeFilled() : bool
    {
        return !$this->object->inSubscriptionFairTime() && (!$this->isMembershipLimited() || $this->getFreePlaces() > 0);
    }

}