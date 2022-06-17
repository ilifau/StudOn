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
        'ilias_obj_id' => 'integer',
        'ilias_dirty_since' => 'text'
    ];

    protected int $event_id;
    protected ?string $eventtype;
    protected ?string $title;
    protected ?string $shorttext;
    protected ?string $comment;
    protected ?int $guest;

    // not in constructor, added later
    protected ?int $ilias_obj_id = null;
    protected ?string $ilias_dirty_since = null;

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
     * @return int|null
     */
    public function getIliasObjId() : ?int
    {
        return $this->ilias_obj_id;
    }

    /**
     * @return string|null
     */
    public function getIliasDirtySince() : ?string
    {
        return $this->ilias_dirty_since;
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

    /**
     * Note that event data has changed
     * If there is an ILIAS course, this should force an update of the data
     * @param bool $changed
     * @return Event
     */
    public function asChanged(bool $changed) : self
    {
        $clone = clone $this;
        if ($changed) {
            if (isset($clone->ilias_obj_id) && !isset($clone->ilias_dirty_since)) {
                try {
                    $clone->ilias_dirty_since = (new \ilDateTime(time(), IL_CAL_UNIX))->get(IL_CAL_DATETIME);
                }
                catch (\Throwable $throwable) {
                }
            }
        }
        else {
            $clone->ilias_dirty_since = null;
        }

        return $clone;
    }

}