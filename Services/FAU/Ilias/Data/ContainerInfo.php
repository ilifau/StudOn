<?php

namespace FAU\ILIAS\Data;

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
    private int $free_places;
    private int $subscribers;

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
        int $subscribers
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
    }

    /**
     * @return string
     */
    public function getType() : string
    {
        return $this->type;
    }

    /**
     * @return int
     */
    public function getRefId() : int
    {
        return $this->ref_id;
    }

    /**
     * @return int
     */
    public function getObjId() : int
    {
        return $this->obj_id;
    }

    /**
     * @return string
     */
    public function getTitle() : string
    {
        return $this->title;
    }

    /**
     * @return string|null
     */
    public function getDescription() : ?string
    {
        return $this->description;
    }

    /**
     * @return bool
     */
    public function hasMemLimit() : bool
    {
        return $this->mem_limit;
    }

    /**
     * @return bool
     */
    public function hasWaitingList() : bool
    {
        return $this->waiting_list;
    }

    /**
     * @return int
     */
    public function getMaxMembers() : int
    {
        return $this->max_members;
    }

    /**
     * @return int
     */
    public function getMembers() : int
    {
        return $this->members;
    }


    /**
     * @return int
     */
    public function getSubscribers() : int
    {
        return $this->subscribers;
    }

    /**
     * @return int
     */
    public function getFreePlaces() : int
    {
        return max(0, $this->max_members - $this->members);
    }

    /**
     * Get the limit of members that should not be exceeded at registration
     * Zero means that there is no limit
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
            $strings[] = $prop->getString();
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
}