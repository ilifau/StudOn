<?php  declare(strict_types=1);

namespace FAU\Cond\Data;

use FAU\RecordData;

class Requirement extends RecordData
{
    protected const tableName = 'fau_cond_requirements';
    protected const hasSequence = false;
    protected const keyTypes = [
        'requirement_id' => 'integer',
    ];
    protected const otherTypes = [
        'requirement_name' => 'text',
    ];

    protected int $requirement_id;
    protected ?string $requirement_name;

    public function __construct(
        int $requirement_id,
        ?string $requirement_name
    )
    {
        $this->requirement_id = $requirement_id;
        $this->requirement_name = $requirement_name;
    }

    public static function model(): self
    {
        return new self(0,'');
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

}