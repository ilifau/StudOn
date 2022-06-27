<?php declare(strict_types=1);

namespace FAU\Study\Data;
use FAU\RecordData;

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
}