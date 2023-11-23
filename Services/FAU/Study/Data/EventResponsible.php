<?php  declare(strict_types=1);

namespace FAU\Study\Data;

use FAU\RecordData;

class EventResponsible extends RecordData
{
    protected const tableName = 'fau_study_event_resps';
    protected const hasSequence = false;
    protected const keyTypes = [
        'event_id' => 'integer',
        'person_id' => 'integer',
    ];
    protected const otherTypes = [
    ];

    protected int $event_id;
    protected int $person_id;

    public function __construct(
        int $event_id,
        int $person_id
    )
    {
        $this->event_id = $event_id;
        $this->person_id = $person_id;
    }

    public static function model(): self
    {
        return new self(0,0);
    }

    /**
     * @return int
     */
    public function getEventId() : int
    {
        return $this->event_id;
    }

    /**
     * @return int
     */
    public function getPersonId() : int
    {
        return $this->person_id;
    }
}