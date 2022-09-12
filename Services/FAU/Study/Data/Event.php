<?php  declare(strict_types=1);

namespace FAU\Study\Data;

use FAU\Cond\Data\HardRestriction;
use FAU\RecordData;

/**
 * An event represents a generic lecture in campo
 * It has "Courses" as instances in different terms
 */
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
    ];

    protected int $event_id;
    protected ?string $eventtype;
    protected ?string $title;
    protected ?string $shorttext;
    protected ?string $comment;
    protected ?int $guest;

    /**
     * Restrictions are not queried by default but later added
     * @var HardRestriction
     */
    protected $restrictions = [];

    public function __construct(
        int $event_id,
        ?string $eventtype,
        ?string $title,
        ?string $shorttext,
        ?string $comment,
        ?int $guest
    )
    {
        $this->event_id = $event_id;
        $this->eventtype = $eventtype;
        $this->title = $title;
        $this->shorttext = $shorttext;
        $this->comment = $comment;
        $this->guest = $guest;
    }

    public static function model(): self
    {
        return new self(0, null, null, null, null, null);
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
     * Get the restriction for joining events of this module
     * @return HardRestriction[]
     */
    public function getRestrictions() : array
    {
        return $this->restrictions;
    }

    /**
     * Add a restriction for joining events of this module
     * @param HardRestriction $restriction
     * @return Module
     */
    public function withRestriction(HardRestriction $restriction) : self
    {
        $clone = clone $this;
        $clone->restrictions[$restriction->getRestriction()] = $restriction;
        return $clone;
    }

    /**
     * Clear the restrictions for joining events of this module
     * @return Module
     */
    public function withoutRestrictions() : self
    {
        $clone = clone $this;
        $clone->restrictions = [];
        return $clone;
    }

    /**
     * Check if a restriction with a certain name is added to the module
     */
    public function hasRestriction(string $name) : bool
    {
        return isset($this->restrictions[$name]);
    }
}