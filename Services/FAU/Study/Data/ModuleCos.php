<?php declare(strict_types=1);

namespace FAU\Study\Data;


use FAU\RecordData;

/**
 * Record of the fau_campo_module_cos table
 */
class ModuleCos extends RecordData
{
    protected const tableName = 'fau_study_module_cos';
    protected const hasSequence = false;
    protected const keyTypes = [
        'module_id' => 'integer',
        'cos_id' => 'integer'
    ];
    protected const otherTypes = [
    ];

    protected int $module_id;
    protected int $cos_id;

    public function __construct (
        int $module_id,
        int $cos_id
    ) {
        $this->module_id = $module_id;
        $this->cos_id = $cos_id;
    }

    public static function model(): self
    {
        return new self(0,0);
    }

    public static function from(array $row) : self
    {
        return new self (
            (int) $row['module_id'],
            (int) $row['cos_id']
        );
    }

    public function row() : array {
        return  [
            'module_id' => $this->module_id,
            'cos_id' => $this->cos_id,
        ];
    }

    public function info() : string
    {
        return ('module_id: ' . $this->module_id . ' | cos_id: ' . $this->cos_id);
    }

    public function getModuleId() : int
    {
        return $this->module_id;
    }

    public function getCosId() : int
    {
        return $this->cos_id;
    }
}