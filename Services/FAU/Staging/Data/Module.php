<?php declare(strict_types=1);

namespace FAU\Staging\Data;

/**
 * Record of the campo_module table
 */
class Module extends DipData
{
    protected int $event_id;
    protected int $module_id;
    protected string $module_nr;
    protected string $module_name;


    public static function getTableName() : string
    {
        return 'campo_module';
    }

    public static function getTableKeyTypes() : array
    {
        return [
            'event_id' => 'integer',
            'module_id' => 'integer'
        ];
    }

    public static function getTableOtherTypes() : array
    {
        return array_merge(parent::getTableOtherTypes(), [
            'module_nr' => 'text',
            'module_name' => 'text',
        ]);
    }

    public function getTableRow() : array {
        return array_merge(parent::getTableRow(), [
            'event_id' => $this->event_id,
            'module_id' => $this->module_id,
            'module_nr' => $this->module_nr,
            'module_name' => $this->module_name
        ]);
    }

    public function withTableRow(array $row) : self
    {
        $clone = parent::withTableRow($row);
        $clone->event_id = $row['event_id'] ?? 0;
        $clone->module_id = $row['module_id'] ?? 0;
        $clone->module_nr =  $row['module_nr'] ?? null;
        $clone->module_name = $row['module_name'] ?? null;
        return $clone;
    }

    /**
     * @return int
     */
    public function getEventId() : int
    {
        return $this->event_id;
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
    public function getModuleNr() : string
    {
        return $this->module_nr;
    }

    /**
     * @return string
     */
    public function getModuleName() : string
    {
        return $this->module_name;
    }

}