<?php declare(strict_types=1);

namespace FAU\Study\Data;
use FAU\RecordData;
use FAU\Cond\Data\HardRestriction;

/**
 * Record of the fau_study_modules table
 */
class Module extends RecordData
{
    protected const tableName = 'fau_study_modules';
    protected const hasSequence = false;
    protected const keyTypes = [
        'module_id' => 'integer'
    ];
    protected const otherTypes = [
        'module_nr' => 'text',
        'module_name' => 'text',
    ];

    protected int $module_id;
    protected ?string $module_nr;
    protected ?string $module_name;

    /**
     * Restrictions are not queried by default but later added
     * @var HardRestriction
     */
    protected $restrictions = [];



    public function __construct(
        int $module_id,
        ?string $module_nr,
        ?string $module_name
    ) {
        $this->module_id = $module_id;
        $this->module_nr = $module_nr;
        $this->module_name = $module_name;
    }

    public static function model() : self
    {
        return new self(0,null,null);
    }

    public function getModuleId() : int
    {
        return $this->module_id;
    }

    public function getModuleNr() : ?string
    {
        return $this->module_nr;
    }

    public function getModuleName() : ?string
    {
        return $this->module_name;
    }

    /**
     * Get the restriction for joining events of this module
     * @return HardRestriction[]
     */
    public function getRestrictions() : array
    {
        return $this->restrictions;
    }

    /**
     * Add a restriction for joining events of this module
     * @param HardRestriction $restriction
     * @return Module
     */
    public function withRestriction(HardRestriction $restriction) : self
    {
        $clone = clone $this;
        $clone->restrictions[$restriction->getRestriction()] = $restriction;
        return $clone;
    }

    /**
     * Clear the restrictions for joining events of this module
     * @return Module
     */
    public function withoutRestrictions() : self
    {
        $clone = clone $this;
        $clone->restrictions = [];
        return $clone;
    }


    /**
     * Check if a restriction with a certain name is added to the module
     */
    public function hasRestriction(string $name) : bool
    {
        return isset($this->restrictions[$name]);
    }
}