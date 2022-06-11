<?php  declare(strict_types=1);

namespace FAU\Study\Data;

use FAU\RecordData;

class EventOrgunit extends RecordData
{
    protected const tableName = 'fau_study_event_orgunits';
    protected const hasSequence = false;
    protected const keyTypes = [
        'event_id' => 'integer',
        'fauorg_nr' => 'text',
    ];
    protected const otherTypes = [
    ];

    protected int $event_id;
    protected string $fauorg_nr;

    public function __construct(
        int $event_id,
        string $fauorg_nr
    )
    {
        $this->event_id = $event_id;
        $this->fauorg_nr = $fauorg_nr;
    }

    public static function model(): self
    {
        return new self(0, '', null);
    }

    /**
     * @return int
     */
    public function getEventId() : int
    {
        return $this->event_id;
    }

    /**
     * @return string
     */
    public function getFauorgNr() : string
    {
        return $this->fauorg_nr;
    }
}