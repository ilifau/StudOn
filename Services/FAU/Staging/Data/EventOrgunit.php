<?php  declare(strict_types=1);

namespace FAU\Staging\Data;

class EventOrgunit extends DipData
{
    protected const tableName = 'campo_event_orgunit';
    protected const hasSequence = false;
    protected const keyTypes = [
        'event_id' => 'integer',
        'fauorg_nr' => 'text',
    ];
    protected const otherTypes = [
        'orgunit' => 'text',
    ];

    protected int $event_id;
    protected string $fauorg_nr;
    protected ?string $orgunit;

    public function __construct(
        int $event_id,
        string $fauorg_nr,
        ?string $orgunit
    )
    {
        $this->event_id = $event_id;
        $this->fauorg_nr = $fauorg_nr;
        $this->orgunit = $orgunit;
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
     * @return string|null
     */
    public function getOrgunit() : ?string
    {
        return $this->orgunit;
    }
}