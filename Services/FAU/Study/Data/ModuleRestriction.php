<?php  declare(strict_types=1);

namespace FAU\Study\Data;

use FAU\RecordData;

class ModuleRestriction extends RecordData
{
    protected const tableName = 'fau_study_module_restrictions';
    protected const hasSequence = false;
    protected const keyTypes = [
        'module_id' => 'integer',
        'restriction' => 'text',
        'requirement_id' => 'integer',
    ];
    protected const otherTypes = [
        'compulsory' => 'text',
    ];

    protected int $module_id;
    protected string $restriction;
    protected int $requirement_id;
    protected ?string $compulsory;

    public function __construct(
        int $module_id,
        string $restriction,
        int $requirement_id,
        ?string $requirement_name,
        ?string $compulsory
    )
    {
        $this->module_id = $module_id;
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
    public function getModuleId() : int
    {
        return $this->module_id;
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