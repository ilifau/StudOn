<?php  declare(strict_types=1);

namespace FAU\Study\Data;

use FAU\RecordData;

class Event extends RecordData
{
    protected const tableName = 'fau_study_events';
    protected const hasSequence = false;
    protected const keyTypes = [
        'event_id' => 'integer'
    ];
    protected const otherTypes = [
        'eventtype' => 'text',
        'title' => 'text',
        'shorttext' => 'text',
        'comment' => 'text',
        'guest' => 'integer',
        'ilias_obj_id' => 'integer'
    ];

    protected int $event_id;
    protected ?string $eventtype;
    protected ?string $title;
    protected ?string $shorttext;
    protected ?string $comment;
    protected ?int $guest;
    protected ?int $ilias_obj_id;

    public function __construct(
        int $event_id,
        ?string $eventtype,
        ?string $title,
        ?string $shorttext,
        ?string $comment,
        ?int $guest,
        ?int $ilias_obj_id
    )
    {
        $this->event_id = $event_id;
        $this->eventtype = $eventtype;
        $this->title = $title;
        $this->shorttext = $shorttext;
        $this->comment = $comment;
        $this->guest = $guest;
        $this->ilias_obj_id = $ilias_obj_id;
    }

    public static function model(): self
    {
        return new self(0, null, null, null, null, null, null);
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
    public function getEventtype() : ?string
    {
        return $this->eventtype;
    }

    /**
     * @return string|null
     */
    public function getTitle() : ?string
    {
        return $this->title;
    }

    /**
     * @return string|null
     */
    public function getShorttext() : ?string
    {
        return $this->shorttext;
    }

    /**
     * @return string|null
     */
    public function getComment() : ?string
    {
        return $this->comment;
    }

    /**
     * @return int|null
     */
    public function getGuest() : ?int
    {
        return $this->guest;
    }

    /**
     * @return int|null
     */
    public function getIliasObjId() : ?int
    {
        return $this->ilias_obj_id;
    }

    /**
     * @param int|null $ilias_obj_id
     * @return Event
     */
    public function withIliasObjId(?int $ilias_obj_id) : self
    {
        $clone = clone $this;
        $clone->ilias_obj_id = $ilias_obj_id;
        return $clone;
    }
}