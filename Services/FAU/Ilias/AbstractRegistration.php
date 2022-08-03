<?php

namespace FAU\Ilias;

use ilMailNotification;

/**
 * Base class for registration classes to hide differences between courses and groups
 * Don't extend this class directly - extend the class Registration instead
 * @see Registration
 */
abstract class AbstractRegistration
{
    // defined subscription types
    const subDeactivated = 'subDeactivated';
    const subDirect = 'subDirect';
    const subConfirmation = 'subConfirmation';
    const subPassword = 'subPassword';
    const subObject = 'subObject';

    // actual subscription type
    protected string $subType = self::subDeactivated;

    abstract public function hasMaxMembers() : bool;
    abstract public function getMaxMembers() : int;
    abstract public function isWaitingListEnabled() : bool;
    abstract public function getMembershipMailNotification(): ilMailNotification;
    abstract public function getNotificationTypeAddedAdmins(): int;
    abstract public function getNotificationTypeAddedMember(): int;
    abstract public function getNotificationTypeRefusedMember(): int;

    abstract protected function initSubType() : void;
    abstract protected function checkLPStatusSync(int $user_id): void;
    abstract protected function getMemberRoleConstant(): int;
}