<?php

namespace FAU\Ilias\Data;

use FAU\Ilias\Registration;
use ilWaitingList;

/**
 * Basic info for courses or groups
 * @todo: currently only for parallel groups in a course
 * @todo: add info about registration type and period and fair time
 */
class ContainerInfo
{
    const TYPE_GROUP = 'grp';
    const TYPE_COURSE = 'crs';

    private string $title;
    private ?string $description;
    private string $type;
    private int $ref_id;
    private int $obj_id;
    private bool $mem_limit;
    private bool $waiting_list;
    private int $max_members;
    private int $members;
    private int $subscribers;
    private int $waiting_status;
    private bool $assigned;


    /** @var ListProperty[] */
    private array $props = [];

    public function __construct (
        string $title,
        ?string $description,
        string $type,
        int $ref_id,
        int $obj_id,
        bool $mem_limit,
        bool $waiting_list,
        int $max_members,
        int $members,
        int $subscribers,
        int $waiting_status,
        bool $assigned
    ) {
        $this->title = $title;
        $this->description = $description;
        $this->type = $type;
        $this->ref_id = $ref_id;
        $this->obj_id = $obj_id;
        $this->mem_limit = $mem_limit;
        $this->max_members = $max_members;
        $this->waiting_list = $waiting_list;
        $this->members = $members;
        $this->subscribers = $subscribers;
        $this->waiting_status = $waiting_status;
        $this->assigned = $assigned;
    }

    /**
     * Get the object type ('crs' or 'grp')
     */
    public function getType() : string
    {
        return $this->type;
    }

    /**
     * Get the reference id
     */
    public function getRefId() : int
    {
        return $this->ref_id;
    }

    /**
     * Get the object id
     */
    public function getObjId() : int
    {
        return $this->obj_id;
    }

    /**
     * Get the object title
     */
    public function getTitle() : string
    {
        return $this->title;
    }

    /**
     * Get the object description
     */
    public function getDescription() : ?string
    {
        return $this->description;
    }

    /**
     * Get if the object has a membership limitation (max or min)
     */
    public function hasMemLimit() : bool
    {
        return $this->mem_limit;
    }

    /**
     * Get if the object has a waiting list enabled
     */
    public function hasWaitingList() : bool
    {
        return $this->waiting_list;
    }

    /**
     * Get the configured maximum number of members
     * 0 means no limit
     */
    public function getMaxMembers() : int
    {
        return $this->max_members;
    }

    /**
     * Get the number of members
     */
    public function getMembers() : int
    {
        return $this->members;
    }


    /**
     * Get the number of subscribers on the waiting list
     */
    public function getSubscribers() : int
    {
        return $this->subscribers;
    }

    /**
     * Get the status of the current user on the waiting list
     * @see \ilWaitingList::_getStatus()
     */
    public function getWaitingStatus() : int
    {
        return $this->waiting_status;
    }

    /**
     * Get if the current user is on the waiting list
     */
    public function isOnWaitingList() : bool
    {
        return $this->waiting_status != ilWaitingList::REQUEST_NOT_ON_LIST;
    }

    /**
     * Get the number of free places
     */
    public function getFreePlaces() : int
    {
        return max(0, $this->max_members - $this->members);
    }

    /**
     * Get the limit of members that should not be exceeded at registration
     * Used for ilParticipants::addLimited()
     * 0 means that there is no limit
     * @see Registration::doRegistration()
     */
    public function getRegistrationLimit() : int
    {
        if (!$this->hasMemLimit()) {
            return 0;
        }
        if ($this->hasWaitingList()) {
            return max(0, $this->max_members - $this->subscribers);
        }
        else {
            return $this->max_members;
        }
    }


    /**
     * Get the properties
     */
    public function getProperties() : array
    {
        return $this->props;
    }

    /**
     * Get a html code describing the properties
     */
    public function getInfoHtml() : string
    {
        $strings = [];
        if (!empty($this->description)) {
            $strings[] = (string) $this->description;
        }

        foreach ($this->props as $prop) {
            $strings[] = $prop->getHtml();
        }
        return implode('<br />', $strings);
    }


    /**
     * Get if a registration is possible
     */
    public function isDirectJoinPossible() : bool
    {
        return !$this->hasMemLimit()
            || $this->getSubscribers() < $this->getFreePlaces();
    }


    /**
     * Get if a registration is possible
     */
    public function isSubscriptionPossible() : bool
    {
        return $this->isDirectJoinPossible() || $this->hasWaitingList();
    }


    /**
     * Add a property
     */
    public function withProperty(ListProperty $property) : self
    {
        $clone = clone $this;
        $clone->props[] = $property;
        return $clone;
    }

    /**
     * @return bool
     */
    public function isAssigned() : bool
    {
        return $this->assigned;
    }
}