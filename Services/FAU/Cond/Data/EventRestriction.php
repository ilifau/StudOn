<?php  declare(strict_types=1);

namespace FAU\Cond\Data;

use FAU\RecordData;

class EventRestriction extends RecordData
{
    protected const tableName = 'fau_cond_event_rests';
    protected const hasSequence = false;
    protected const keyTypes = [
        'event_id' => 'integer',
        'restriction' => 'text',
        'requirement_id' => 'integer',
    ];
    protected const otherTypes = [
        'compulsory' => 'text',
    ];

    protected int $event_id;
    protected string $restriction;
    protected int $requirement_id;
    protected ?string $compulsory;

    public function __construct(
        int $event_id,
        string $restriction,
        int $requirement_id,
        ?string $compulsory
    )
    {
        $this->event_id = $event_id;
        $this->restriction = $restriction;
        $this->requirement_id = $requirement_id;
        $this->compulsory = $compulsory;
    }

    public static function model(): self
    {
        return new self(0,'',0,null,null);
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
    public function getRestriction() : string
    {
        return $this->restriction;
    }

    /**
     * @return int
     */
    public function getRequirementId() : int
    {
        return $this->requirement_id;
    }


    /**
     * @return string|null
     */
    public function getCompulsory() : ?string
    {
        return $this->compulsory;
    }
}