<?php

namespace FAU\Study\Data;
use FAU\RecordData;

class SearchResultEvent extends RecordData
{
    protected const otherTypes = [
        'event_id' => 'integer',
        'event_title' => 'text',
        'event_type' => 'text',
        'event_shorttext' => 'text',
        'event_guest' => 'integer',
    ];

    // from initial query
    protected int $event_id;
    protected ?string $event_type;
    protected ?string $event_title;
    protected ?string $event_shorttext;
    protected ?int $guest;

    // later added
    protected array $courses = [];
    protected ?int $ilias_ref_id;
    protected ?string $ilias_title;
    protected ?string $ilias_description;
    protected bool $visible = false;
    protected bool $moveable = false;

    public function __construct (
        int $event_id,
        ?string $event_type,
        ?string $event_title,
        ?string $event_shorttext,
        ?int $guest
    ) {
        $this->event_id = $event_id;
        $this->event_type = $event_type;
        $this->event_title = $event_title;
        $this->event_shorttext = $event_shorttext;
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
        return $this->event_title;
    }

    /**
     * @return string|null
     */
    public function getEventShorttext() : ?string
    {
        return $this->event_shorttext;
    }

    /**
     * @return int|null
     */
    public function getGuest() : ?int
    {
        return $this->guest;
    }

    /**
     * @return SearchResultCourse[]
     */
    public function getCourses() : array
    {
        return $this->courses;
    }

    /**
     * @return ?int
     */
    public function getIliasRefId() : ?int
    {
        return $this->ilias_ref_id;
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
     * @param SearchResultCourse $course
     * @return $this
     */
    public function withCourse(SearchResultCourse $course) : self
    {
        $clone = clone $this;
        $clone->courses[] = $course;
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

}