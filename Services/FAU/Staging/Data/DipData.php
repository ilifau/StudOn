<?php

namespace FAU\Staging\Data;

use FAU\RecordData;

/**
 * Data object representing the record of a table filled by the Data Integration Platform (DIP)
 */
abstract class DipData extends RecordData
{
    protected const otherTypes = [
        'dip_status' => 'text',
        'dip_timestamp' => 'text'
    ];

    public const INSERTED = 'inserted';
    public const CHANGED = 'changed';
    public const DELETED = 'deleted';
    public const MARKED = 'marked';

    protected ?string $dip_status;
    protected ?string $dip_timestamp;


    public static function tableOtherTypes() : array
    {
        // add the DIP fields defined here to the other fields defined in the child class
        return array_merge(static::otherTypes, self::otherTypes);
    }

    /**
     * Get the status of the last change by DIP
     */
    public function getDipStatus() : ?string
    {
        return $this->dip_status;
    }

    /**
     * Get the timestamp of the last change by DIP
     */
    public function getDipTimestamp() : ?string
    {
        return $this->dip_timestamp;
    }


    /**
     * Get the record with processed status
     * @return static
     */
    public function asProcessed()
    {
        $clone = clone $this;
        $clone->dip_status = null;
        $clone->dip_timestamp = null;
        return $clone;
    }

}