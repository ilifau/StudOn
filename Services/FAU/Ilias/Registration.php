<?php

namespace FAU\Ilias;

use ILIAS\DI\Container;
use ilParticipants;
use ilWaitingList;
use ilObjUser;
use ilObjGroup;
use ilObjCourse;
use FAU\Ilias\Data\ContainerInfo;
use ilObjCourseGrouping;
use ilObjectFactory;
use ilObject;
use ilForumNotification;

/**
 * Base class handling course or group registrations
 * Used by the Registration GUIs
 */
abstract class Registration extends AbstractRegistration
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

    /** @see getRegistrationAction() */
    protected ?string $nextAction = null;

    /**
     * Info objects about contained parallel groups
     * @var ContainerInfo[]
     * @see getParallelGroupsInfos()
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
            $this->groups = $this->dic->fau()->ilias()->objects()->getParallelGroupsInfos(
                $this->object->getRefId(), true, true);
        }

        // call object type specific initialisation of the subscription type
        $this->initSubType();
    }


    /**
     * Simulate a parallel membership
     */
    protected function simulateParallelMembership()
    {
        $query = "INSERT INTO rbac_ua (rol_id, usr_id) ".
            "VALUES (".
            $this->dic->database()->quote($this->getMemberRoleId() ,'integer').", ".
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
            $this->dic->database()->quote('simulated parallel request' ,'text')." ".
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
        // ensure proper types for selected groups
        $group_ref_ids = array_map('intval', $group_ref_ids);

        ///////
        // 1. Check Parallel Group Selection (may override the next action)
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
        if (!empty($directGroups && $this->getRegistrationAction() == self::addToWaitingList)) {
            $this->setRegistrationAction(self::addAsMember);
        }
        // force adding to the waiting list if no selected group can be directly joined
        if (!empty($waitingGroups) && empty($directGroups) && $this->getRegistrationAction() == self::addAsMember) {
            $this->setRegistrationAction(self::addToWaitingList);
        }

        /////
        // 2. Try a direct join to the course and avoid race conditions
        ////
        if ($this->getRegistrationAction() == self::addAsMember) {

            // Test of race condition
            // $this->simulateParallelMembership();

            if ($this->participants->addLimited($this->user->getId(), $this->getMemberRoleConstant(), $this->getRegistrationLimit())) {
                // member could be added
                $this->setRegistrationAction(self::notifyAdded);
            } elseif ($this->dic->rbac()->review()->isAssigned($this->user->getId(), $this->getMemberRoleId())) {
                // may have been added by a parallel request
                $this->setRegistrationAction(self::showAlreadyMember);
            } elseif ($this->isWaitingListActive()) {
                // direct join failed but subscription is possible
                $this->setRegistrationAction(self::addToWaitingList);
            } else {
                // maximum members reached and no list active
                $this->setRegistrationAction(self::showLimitReached);
            }
        }

        /////
        // 3. Try a direct join to a parallel group and avoid race conditions
        ////
        if ($this->getRegistrationAction() == self::notifyAdded && !empty($directGroups)) {

            $addedGroup = null;
            foreach ($directGroups as $group) {
                if ($group->getParticipants()->addLimited($this->user->getId(), IL_GRP_MEMBER, $group->getRegistrationLimit())) {
                    $addedGroup = $group;
                    $addedGroup->getParticipants()->addLimitedSuccess($this->user->getId(), IL_GRP_MEMBER);
                    break;
                }
                elseif ($this->dic->rbac()->review()->isAssigned($this->user->getId(),
                    $group->getParticipants()->getRoleId(IL_GRP_MEMBER))) {
                    $this->setRegistrationAction(self::showAlreadyMember);
                    break;
                }
            }
            // handle failed direct adding to a group - revert the course join
            if (empty($addedGroup) && $this->getRegistrationAction() == self::notifyAdded) {
                $this->dic->rbac()->admin()->deassignUser($this->getMemberRoleId(), $this->user->getId());
                if (!empty($waitingGroups)) {
                    $this->setRegistrationAction(self::addToWaitingList);
                }
                else {
                    $this->setRegistrationAction(self::showLimitReached);
                }
            }
        }


        //////
        /// 4. handle adding success for the main object if succeeded
        /////
        if ($this->getRegistrationAction() == self::notifyAdded) {
            $this->participants->addLimitedSuccess($this->user->getId(), $this->getMemberRoleConstant());

            // set the learning progress of the user
            $this->checkLPStatusSync($this->user->getId());

            // removes also subscriptions from all parallel groups
            $this->removeUserSubscription($this->user->getId());

            // remove the user from the waiting list of grouped courses
            $grouping_ref_ids = (array) ilObjCourseGrouping::_getGroupingItems($this->object);
            foreach ($grouping_ref_ids as $ref_id) {
                ilWaitingList::deleteUserEntry($this->user->getId(), ilObject::_lookupObjId($ref_id));
            }
        }


        /////
        // 5. perform the adding to the waiting list
        ////
        if ($this->getRegistrationAction() == self::addToWaitingList) {
            $to_confirm = $this->getNewToConfirm();
            $sub_time = $this->getNewSubTime();

            // Test of race condition
            // $this->simulateParallelWaitingList();

            // add to waiting list and avoid race condition
            $added = $this->waitingList->addWithChecks($this->user->getId(), $this->getMemberRoleId(),
                $subject, $to_confirm, $sub_time);

            // may have been directly added as member in a parallel request
            if ($this->dic->rbac()->review()->isAssigned($this->user->getId(), $this->getMemberRoleId())) {
                $this->setRegistrationAction(self::showAlreadyMember);
            } // may have been directly added to the waiting list in a parallel request
            elseif (ilWaitingList::_isOnList($this->user->getId(), $this->object->getId())) {

                // was already on list, so update the subject
                if (!$added) {
                    $this->waitingList->updateSubject($this->user->getId(), $subject);
                }

                // eventually update the waiting list of enclosed groups
                $this->updateGroupWaitingLists($subject, $group_ref_ids, $module_id, $to_confirm, $sub_time);

                if (!$added) {
                    $this->setRegistrationAction(self::showUpdatedWaitingList);
                }
                elseif ($this->object->inSubscriptionFairTime($sub_time)) {
                    // show info about adding in fair time
                    $this->setRegistrationAction(self::showAddedToWaitingListFair);
                }
                else {
                    // maximum members reached
                    $this->setRegistrationAction(self::notifyAddedToWaitingList);
                }
            }
            else {
                // show an unspecified error
                $this->setRegistrationAction(self::showGenericFailure);
            }
        }


        /////
        // 6. Send notifications
        ////
        switch ($this->getRegistrationAction()) {
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
            $groupParticipants = $group->getParticipants();
            $groupList = $group->getWaitingList();

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
        // remove user from main object (will recalculate the positions)
        $this->waitingList->removeFromList($user_id);

        // remove user from parallel groups
        foreach ($this->groups as $group) {
            if ($group->getWaitingList()->isOnList($user_id)) {
                $group->getWaitingList()->removeFromList($user_id);
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
    public function doAutoFill(bool $manual = false, bool $initial = false) : array
    {
        $added_users = [];
        $last_fill = $this->object->getSubscriptionLastFill();

        // check filling is allowed, parallel groups will not be filled directly
        if (!$this->canBeFilled() || $this->object->isParallelGroup()) {
            return [];
        }

        // check the conditions for autofill
        if ($manual || $initial || ($this->isWaitingListEnabled() && $this->object->hasWaitingListAutoFill())) {

            $grouping_ref_ids = (array) ilObjCourseGrouping::_getGroupingItems($this->object);

            while (!empty($user_id = $this->getNextAssignableUserId())) {

                // check conditions for adding the member
                if (
                    // user is already assigned
                    $this->participants->isAssigned($user_id) == true
                    // user does no longer exist
                    || ilObjectFactory::getInstanceByObjId($user_id, false) == false
                    // user is already assigned to a grouped course
                    || ilObjCourseGrouping::_checkGroupingDependencies($this->object, $user_id) == false
                ) {
                    // user can't be added - so remove from waiting lists
                    $this->removeUserSubscription($user_id);
                    continue;
                }

                // add the user as member
                $this->participants->add($user_id, $this->getMemberRoleConstant());
                foreach ($this->getFillableGroups($user_id) as $group) {
                    $group->getParticipants()->add($user_id, IL_GRP_MEMBER);
                    break;
                }
                $added_users[] = $user_id;
                $this->checkLPStatusSync($user_id);

                // delete user from this and grouped waiting lists
                $this->removeUserSubscription($user_id);
                foreach ($grouping_ref_ids as $ref_id) {
                    ilWaitingList::deleteUserEntry($user_id, ilObject::_lookupObjId($ref_id));
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
                && (!$this->hasMaxMembers() || $this->getFreePlaces() > 0)
            ) {
                $mail->setType(self::notificationAdminAutofillToConfirm);
                $mail->setRecipients($this->participants->getNotificationRecipients());
                $mail->send();
            }
        }
        // remember the fill date
        // this prevents further calls from the cron job
        $this->object->saveSubscriptionLastFill(time());

        return $added_users;
    }

    /**
     * Is it possible to enter the object without waiting list
     */
    public function isDirectJoinPossible() : bool
    {
        return $this->getRegistrationAction() == self::addAsMember;
    }

    /**
     * Is it possible to enter a parallel group without waiting list
     */
    public function isDirectJoinPossibleForGroup(ContainerInfo $group) : bool
    {
       return ($group->isDirectJoinPossible() && $this->isDirectJoinPossible()) ||
           (!$group->hasMaxMembers() && $this->subType != self::subConfirmation);
    }

    /**
     * @see ilRegistrationGUI::isWaitingListActive()
     */
    public function isWaitingListActive()
    {
        if ($this->object->inSubscriptionFairTime()) {
            return true;
        }
        if (!$this->hasMaxMembers()) {
            return false;
        }
        if (!$this->isWaitingListEnabled()) {
            return false;
        }

        return $this->getFreePlaces() == 0 || $this->waitingList->getCountUsers() > 0;
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
    }

    /**
     * Get the next registration action
     */
    public function getRegistrationAction() : string
    {
        if (isset($this->nextAction)) {
            return $this->nextAction;
        }

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
        elseif (!$this->hasMaxMembers()) {
            // add as member if there is no limit
            $this->nextAction = self::addAsMember;
        }
        elseif ($this->getFreePlaces() > $this->waitingList->getCountUsers()) {
            // allow jump over waiting candidates if enough places are free
            $this->nextAction = self::addAsMember;
        }
        elseif ($this->isWaitingListEnabled()) {
            // add to waiting list if it is enabled
            $this->nextAction = self::addToWaitingList;
        }
        else {
            $this->nextAction = self::showLimitReached;
        }

        return $this->nextAction;
    }

    /**
     * Set the next registration action
     */
    public function setRegistrationAction(string $action)
    {
        $this->nextAction = $action;
    }

    /**
     * Get the limit of members that should not be exceeded at registration
     * @return ?int     limit or null, if there is no limit
     * @see ilParticipants::addLimited()
     */
    public function getRegistrationLimit() : ?int
    {
        if (!$this->hasMaxMembers()) {
            return null;
        }
        if ($this->isWaitingListActive()) {
            return max(0, $this->getMaxMembers() - $this->waitingList->getCountUsers());
        }
        else {
            return $this->getMaxMembers();
        }
    }

    /**
     * Get the number of free places
     */
    public function getFreePlaces() : int
    {
        return max(0, $this->getMaxMembers() - $this->participants->getCountMembers());
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
        return !$this->object->inSubscriptionFairTime()
            && (!$this->hasMaxMembers() || $this->getFreePlaces() > $this->waitingList->getCountToConfirm());
    }

    /**
     * Check if a parallel group can get free places assigned
     */
    protected function canGroupBeFilled(ContainerInfo $group) : bool
    {
        return !$this->object->inSubscriptionFairTime()
            && (!$group->hasMaxMembers() || $group->getFreePlaces() > $group->getWaitingList()->getCountToConfirm());
    }

    /**
     * Get the next user id that can be used for autofill
     * @return ?int user_id or null, if no assignable user is found
     */
    protected function getNextAssignableUserId() : ?int
    {
        if (!$this->canBeFilled()) {
            return null;
        }

        foreach ($this->waitingList->getAllPositions() as $pos) {

            // fault tolerance - normally each position should have users
            // call recalculate() of the waiting list after each manipulation
            if (empty($user_ids = $this->waitingList->getPositionUsers($pos))) {
                continue;
            }
            // all users on the same position should have the same chance
            shuffle($user_ids);

            foreach ($user_ids as $user_id) {
                // don't assign a user that needs confirmation
                if ($this->waitingList->isToConfirm($user_id)) {
                    continue;
                }
                // if course has groups, check if one group can be filled
                if (empty($this->groups) || !empty($this->getFillableGroups($user_id))) {
                    return $user_id;
                }
            }
        }
        return null;
    }

    /**
     * Get the groups that a user on the waiting list can be assigned
     * @return ContainerInfo[]
     */
    public function getFillableGroups(int $user_id) : array
    {
        $groups = [];
        foreach ($this->groups as $group) {
            if ($this->canGroupBeFilled($group)
                && $group->getWaitingList()->isOnList($user_id)
                && !$group->getWaitingList()->isToConfirm($user_id)
            ) {
                // use number of members as sort key for found groups
                // this will cause an equal filling if the first found group is taken
                $key = sprintf('%09d', $group->getMembers()) . '.' . $group->getRefId();
                $groups[$key] = $group;
            }
        }
        ksort($groups);
        return array_values($groups);
    }
}