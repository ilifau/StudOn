<?php declare(strict_types=1);

namespace FAU\Campo\Data;


use FAU\RecordData;

/**
 * Record of the fau_campo_modules table
 */
class Module extends RecordData
{
    protected int $module_id;
    protected ?string $module_nr;
    protected ?string $module_name;


    public static function getTableName() : string
    {
        return 'fau_campo_modules';
    }

    public static function getTableKeyTypes() : array
    {
        return [
            'module_id' => 'integer'
        ];
    }

    public static function getTableOtherTypes() : array
    {
        return [
            'module_nr' => 'text',
            'module_name' => 'text',
        ];
    }

    public function getTableRow() : array {
        return  [
            'module_id' => $this->module_id,
            'module_nr' => $this->module_nr,
            'module_name' => $this->module_name
        ];
    }

    public function withTableRow(array $row) : self
    {
        $clone = clone $this;
        $clone->module_id = $row['module_id'] ?? 0;
        $clone->module_nr =  $row['module_nr'] ?? null;
        $clone->module_name = $row['module_name'] ?? null;
        return $clone;
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

    public function withModuleId(int $module_id) : Module
    {
        $clone = clone $this;
        $clone->module_id = $module_id;
        return $clone;
    }

    public function withModuleNr(?string $module_nr) : Module
    {
        $clone = clone $this;
        $this->module_nr = $module_nr;
        return $clone;
    }

    public function withModuleName(?string $module_name) : Module
    {
        $clone = clone $this;
        $this->module_name = $module_name;
        return $clone;
    }

}