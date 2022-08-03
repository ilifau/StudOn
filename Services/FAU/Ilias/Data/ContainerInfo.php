<?php

namespace FAU\Ilias\Data;

use ilWaitingList;
use ilParticipants;

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
    private bool $has_mem_limit;
    private bool $has_waiting_list;
    private int $max_members;
    private int $members;
    private int $subscribers;
    private int $waiting_status;
    private bool $assigned;


    /** @var ListProperty[] */
    private array $props = [];


    private ?ilWaitingList $waitingList = null;
    private ?ilParticipants $participants = null;


    public function __construct (
        string $title,
        ?string $description,
        string $type,
        int $ref_id,
        int $obj_id,
        bool $has_mem_limit,
        bool $has_waiting_list,
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
        $this->has_mem_limit = $has_mem_limit;
        $this->max_members = $max_members;
        $this->has_waiting_list = $has_waiting_list;
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
     * Get if the object has a maximum of members
     */
    public function hasMaxMembers() : bool
    {
        return $this->has_mem_limit && !empty($this->max_members);
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
     * Get if the object has a waiting list enabled
     */
    public function hasWaitingList() : bool
    {
        return $this->has_waiting_list;
    }

    /**
     * Get the number of members
     */
    public function getMembers() : int
    {
        // take current value from participants, if possible
        if (isset($this->participants)) {
            return $this->participants->getCountMembers();
        }

        // fallback: take initial value from constructor
        return $this->members;
    }


    /**
     * Get the number of subscribers on the waiting list
     */
    public function getSubscribers() : int
    {
        // take current value rom waiting list, if possible
        if (isset($this->waitingList)) {
            return $this->waitingList->getCountUsers();
        }

        // fallback: take initial value from constructor
        return $this->subscribers;
    }

    /**
     * Get the number of free places
     */
    public function getFreePlaces() : int
    {
        return max(0, $this->getMaxMembers() - $this->getMembers());
    }

    /**
     * Get if the current user is a member
     */
    public function isAssigned() : bool
    {
        return $this->assigned;
    }


    /**
     * Get if the current user is on the waiting list
     */
    public function isOnWaitingList() : bool
    {
        return $this->waiting_status != ilWaitingList::REQUEST_NOT_ON_LIST;
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
        if ($this->hasWaitingList()) {
            return max(0, $this->getMaxMembers() - $this->getSubscribers());
        }
        else {
            return $this->getMaxMembers();
        }
    }

    /**
     * Get if a direct join to the object would be possible when the subscription type is direct
     */
    public function isDirectJoinPossible() : bool
    {
        return !$this->hasMaxMembers() || $this->getSubscribers() < $this->getFreePlaces();
    }

    /**
     * Get if a subscription would be possible when the subscription is active
     */
    public function isSubscriptionPossible() : bool
    {
        return $this->isDirectJoinPossible() || $this->hasWaitingList();
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
     * Get the properties
     */
    public function getProperties() : array
    {
        return $this->props;
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
     * @return ilWaitingList|null
     */
    public function getWaitingList() : ?ilWaitingList
    {
        return $this->waitingList;
    }


    /**
     * @param ilWaitingList|null $waitingList
     * @return ContainerInfo
     */
    public function withWaitingList(?ilWaitingList $waitingList) : ContainerInfo
    {
        $clone = clone $this;
        $clone->waitingList = $waitingList;
        return $clone;
    }

    /**
     * @return ilParticipants|null
     */
    public function getParticipants() : ?ilParticipants
    {
        return $this->participants;
    }

    /**
     * @param ilParticipants|null $participants
     * @return ContainerInfo
     */
    public function withParticipants(?ilParticipants $participants) : ContainerInfo
    {
        $clone = clone $this;
        $clone->participants = $participants;
        return $clone;
    }
}