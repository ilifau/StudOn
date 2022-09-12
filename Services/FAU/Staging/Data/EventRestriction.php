<?php  declare(strict_types=1);

namespace FAU\Staging\Data;

class EventRestriction extends DipData
{
    protected const tableName = 'campo_event_restrictions';
    protected const hasSequence = false;
    protected const keyTypes = [
        'event_id' => 'integer',
        'restriction' => 'text',
        'requirement_id' => 'integer',
    ];
    protected const otherTypes = [
        'requirement_name' => 'text',
        'compulsory' => 'text',
    ];

    protected int $event_id;
    protected string $restriction;
    protected int $requirement_id;
    protected ?string $requirement_name;
    protected ?string $compulsory;

    public function __construct(
        int $event_id,
        string $restriction,
        int $requirement_id,
        ?string $requirement_name,
        ?string $compulsory
    )
    {
        $this->event_id = $event_id;
        $this->restriction = $restriction;
        $this->requirement_id = $requirement_id;
        $this->requirement_name = $requirement_name;
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
    public function getRequirementName() : ?string
    {
        return $this->requirement_name;
    }

    /**
     * @return string|null
     */
    public function getCompulsory() : ?string
    {
        return $this->compulsory;
    }
}