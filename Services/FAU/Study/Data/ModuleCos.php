<?php declare(strict_types=1);

namespace FAU\Study\Data;


use FAU\RecordData;

/**
 * Record of the fau_campo_module_cos table
 */
class ModuleCos extends RecordData
{
    protected int $module_id;
    protected int $cos_id;

    public function __construct (
        int $module_id,
        int $cos_id
    ) {
        $this->module_id = $module_id;
        $this->cos_id = $cos_id;
    }

    public function info() : string
    {
        return ('module_id: ' . $this->module_id . ' | cos_id: ' . $this->cos_id);
    }

    public static function model(): self
    {
        return new self(0,0);
    }

    public static function getTableName() : string
    {
        return 'fau_study_module_cos';
    }

    public static function getTableKeyTypes() : array
    {
        return [
            'module_id' => 'integer',
            'cos_id' => 'integer'
        ];
    }

    public static function getTableOtherTypes() : array
    {
        return [
        ];
    }

    public function getTableRow() : array {
        return  [
            'module_id' => $this->module_id,
            'cos_id' => $this->cos_id,
        ];
    }

    public function withTableRow(array $row) : self
    {
        $clone = clone $this;
        $clone->module_id = (int) $row['module_id'];
        $clone->cos_id = (int) $row['module_id'];
        return $clone;
    }

    public function getModuleId() : int
    {
        return $this->module_id;
    }

    public function getCosId() : int
    {
        return $this->cos_id;
    }

    public function withModuleId(int $module_id) : ModuleCos
    {
        $clone = clone $this;
        $clone->module_id = $module_id;
        return $clone;
    }

    public function withCosId(int $cos_id) : ModuleCos
    {
        $clone = clone $this;
        $clone->cos_id = $cos_id;
        return $clone;
    }

}