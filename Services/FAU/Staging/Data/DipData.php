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
        return array_merge(static::tableOtherTypes(), [
            'dip_status' => 'text',
            'dip_timestamp' => 'text'
        ]);
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
     * Get an array of the DIP data from the object
     * This must be merged with other row data in the row() function
     */
    public function getDipData() : array
    {
        return [
            'dip_status' => $this->dip_status,
            'dip_timestamp' => $this->dip_timestamp
        ];
    }

    /**
     * Get the object with DIP data set from a database row
     * This must called in the from() function
     * @return static
     */
    public function withDipData(array $data)
    {
        $clone = clone $this;
        $clone->dip_status = $data['dip_status'] ?? null;
        $clone->dip_timestamp = $data['dip_timestamp'] ?? null;
        return $clone;
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