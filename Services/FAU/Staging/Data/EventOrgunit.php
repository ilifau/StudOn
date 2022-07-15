<?php  declare(strict_types=1);

namespace FAU\Staging\Data;

class EventOrgunit extends DipData
{
    protected const tableName = 'campo_event_orgunit';
    protected const hasSequence = false;
    protected const keyTypes = [
        'event_id' => 'integer',
        'fauorg_nr' => 'text',
        'relation_id' => 'integer'
    ];
    protected const otherTypes = [
        'orgunit' => 'text',
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
    protected ?string $orgunit;

    public function __construct(
        int $event_id,
        string $fauorg_nr,
        ?int $relation_id,
        ?string $orgunit
    )
    {
        $this->event_id = $event_id;
        $this->fauorg_nr = $fauorg_nr;
        $this->relation_id = $relation_id;
        $this->orgunit = $orgunit;
    }

    public static function model(): self
    {
        return new self(0, '', 0, null);
    }

    /**
     * @return int
     */
    public function getEventId() : int
    {
        return $this->event_id;
    }

    /**
     * @return ?int
     */
    public function getRelationId() : ?int
    {
        return $this->relation_id;
    }


    /**
     * @return string
     */
    public function getFauorgNr() : string
    {
        return $this->fauorg_nr;
    }


    /**
     * @return string|null
     */
    public function getOrgunit() : ?string
    {
        return $this->orgunit;
    }
}