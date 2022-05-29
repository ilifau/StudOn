<?php declare(strict_types=1);

namespace FAU\Campo\Data;


use FAU\RecordData;

/**
 * Record of the fau_campo_module_cos table
 */
class ModuleCos extends RecordData
{
    protected int $module_id;
    protected int $cos_id;


    public static function getTableName() : string
    {
        return 'fau_campo_module_cos';
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
        $clone->module_id = $row['module_id'] ?? 0;
        $clone->cos_id = $row['module_id'] ?? 0;
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