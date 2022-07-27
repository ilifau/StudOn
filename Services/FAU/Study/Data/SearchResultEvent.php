<?php

namespace FAU\Study\Data;
use FAU\RecordData;

class SearchResultEvent extends RecordData
{
    protected const otherTypes = [
        'event_id' => 'integer',
        'eventtype' => 'text',
        'title' => 'text',
        'shorttext' => 'text',
        'guest' => 'integer',
    ];

    // from initial query
    protected int $event_id;
    protected ?string $event_type;
    protected ?string $title;
    protected ?string $shorttext;
    protected ?int $guest;

    // later added
    protected array $objects = [];
    protected ?int $ilias_ref_id = null;
    protected ?int $ilias_obj_id  = null;
    protected ?string $ilias_title = null;
    protected ?string $ilias_description = null;
    protected bool $visible = false;
    protected bool $moveable = false;
    protected bool $nested = false;

    public function __construct (
        int $event_id,
        ?string $eventtype,
        ?string $title,
        ?string $shorttext,
        ?int $guest
    ) {
        $this->event_id = $event_id;
        $this->eventtype = $eventtype;
        $this->title = $title;
        $this->shorttext = $shorttext;
        $this->guest = $guest;
    }

    public static function model() : self
    {
        return new self(0,null,null,null,null);
    }

    /**
     * @return int
     */
    public function getEventId() : int
    {
        return $this->event_id;
    }

    /**
     * @return string|null
     */
    public function getEventType() : ?string
    {
        return $this->event_type;
    }

    /**
     * @return string|null
     */
    public function getEventTitle() : ?string
    {
        return $this->title;
    }

    /**
     * @return string|null
     */
    public function getEventShorttext() : ?string
    {
        return $this->shorttext;
    }

    /**
     * @return int|null
     */
    public function getGuest() : ?int
    {
        return $this->guest;
    }

    /**
     * @return SearchResultObject[]
     */
    public function getObjects() : array
    {
        return $this->objects;
    }

    /**
     * @return ?int
     */
    public function getIliasRefId() : ?int
    {
        return $this->ilias_ref_id;
    }

    /**
     * @return ?int
     */
    public function getIliasObjId() : ?int
    {
        return $this->ilias_obj_id;
    }

    /**
     * @return ?string
     */
    public function getIliasTitle() : ?string
    {
        return $this->ilias_title;
    }

    /**
     * @return ?string
     */
    public function getIliasDescription() : ?string
    {
        return $this->ilias_description;
    }

    /**
     * Get a unique key for sorting the result list
     * @return string
     */
    public function getSortKey() : string
    {
        return ($this->ilias_title ?? $this->title) . $this->event_id . $this->ilias_ref_id;
    }

    /**
     * @return bool
     */
    public function isVisible() : bool
    {
        return $this->visible;
    }

    /**
     * @return bool
     */
    public function isMoveable() : bool
    {
        return $this->moveable;
    }


    /**
     * Object is an ilias course with nested parallel groups
     */
    public function isNested() : bool
    {
        return $this->nested;
    }

    /**
     * @param ?int $ilias_ref_id
     * @return SearchResultEvent
     */
    public function withIliasRefId(?int $ilias_ref_id) : self
    {
        $clone = clone $this;
        $clone->ilias_ref_id = $ilias_ref_id;
        return $clone;
    }

    /**
     * @param ?int $ilias_obj_id
     * @return SearchResultEvent
     */
    public function withIliasObjId(?int $ilias_obj_id) : self
    {
        $clone = clone $this;
        $clone->ilias_obj_id = $ilias_obj_id;
        return $clone;
    }


    /**
     * @param ?string $ilias_title
     * @return SearchResultEvent
     */
    public function withIliasTitle(?string $ilias_title) : self
    {
        $clone = clone $this;
        $clone->ilias_title = $ilias_title;
        return $clone;
    }

    /**
     * @param ?string $ilias_description
     * @return SearchResultEvent
     */
    public function withIliasDescription(?string $ilias_description) : self
    {
        $clone = clone $this;
        $clone->ilias_description = $ilias_description;
        return $clone;
    }

    /**
     * @param SearchResultObject $object
     * @return $this
     */
    public function withObject(SearchResultObject $object) : self
    {
        $clone = clone $this;
        $clone->objects[] = $object;
        return $clone;
    }

    /**
     * @param bool $visible
     * @return SearchResultEvent
     */
    public function withVisible(bool $visible) : SearchResultEvent
    {
        $clone = clone $this;
        $clone->visible = $visible;
        return $clone;
    }

    /**
     * @param bool $moveable
     * @return SearchResultEvent
     */
    public function withMoveable(bool $moveable) : SearchResultEvent
    {
        $clone = clone $this;
        $clone->moveable = $moveable;
        return $clone;
    }

    /**
     * @param bool $nested
     * @return SearchResultEvent
     */
    public function withNested(bool $nested) : SearchResultEvent
    {
        $clone = clone $this;
        $clone->nested = $nested;
        return $clone;
    }

}