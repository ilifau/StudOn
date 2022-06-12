<?php declare(strict_types=1);

namespace FAU\Study\Data;


use FAU\RecordData;

/**
 * Record of the fau_study_module_event table
 */
class ModuleEvent extends RecordData
{
    protected const tableName = 'fau_study_mod_events';
    protected const hasSequence = false;
    protected const keyTypes = [
        'module_id' => 'integer',
        'event_id' => 'integer'
    ];
    protected const otherTypes = [
    ];

    protected int $module_id;
    protected int $event_id;

    public function __construct (
        int $module_id,
        int $event_id
    ) {
        $this->module_id = $module_id;
        $this->event_id = $event_id;
    }

    public static function model(): self
    {
        return new self(0,0);
    }

    public function getModuleId() : int
    {
        return $this->module_id;
    }

    public function getEventId() : int
    {
        return $this->event_id;
    }
}