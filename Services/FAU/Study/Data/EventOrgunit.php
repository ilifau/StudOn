<?php  declare(strict_types=1);

namespace FAU\Study\Data;

use FAU\RecordData;

class EventOrgunit extends RecordData
{
    protected const tableName = 'fau_study_event_orgs';
    protected const hasSequence = false;
    protected const keyTypes = [
        'event_id' => 'integer',
        'fauorg_nr' => 'text',
    ];
    protected const otherTypes = [
        'relation_id' => 'integer'
    ];

    protected int $event_id;
    protected string $fauorg_nr;

    /**
     * Type of orgunit relation
     * 1: Unit (module) responsible
     * 2: Main Event responsible
     * 3: Other Event Responsible
     * @var ?int
     */
    protected ?int $relation_id;

    public function __construct(
        int $event_id,
        string $fauorg_nr,
        ?int $relation_id
    )
    {
        $this->event_id = $event_id;
        $this->fauorg_nr = $fauorg_nr;
        $this->relation_id = $relation_id;
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

    /**
     * @return int|null
     */
    public function getRelationId() : ?int
    {
        return $this->relation_id;
    }
}