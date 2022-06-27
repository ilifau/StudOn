<?php declare(strict_types=1);

namespace FAU\Staging\Data;

class Achievement extends DipData
{
    protected const tableName = 'campo_achievements';
    protected const hasSequence = false;
    protected const keyTypes = [
        'requirement_id' => 'integer',
        'person_id' => 'integer'
    ];
    protected const otherTypes = [
    ];

    protected int $requirement_id;
    protected int $person_id;

    public function __construct(
        int $requirement_id,
        int $person_id
    ) {
        $this->requirement_id = $requirement_id;
        $this->person_id = $person_id;
    }

    public static function model(): self
    {
        return new self(0,0);
    }

    /**
     * @return int
     */
    public function getRequirementId() : int
    {
        return $this->requirement_id;
    }

    /**
     * @return int
     */
    public function getPersonId() : int
    {
        return $this->person_id;
    }

}