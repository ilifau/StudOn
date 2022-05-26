<?php

namespace FAU\Staging\Data;

use FAU\RecordData;

/**
 * Data object representing the record of a table filled by the Data Integration Platform (DIP)
 */
abstract class DipData extends RecordData
{
    protected ?string $dip_status;
    protected ?string $dip_timestamp;

    public static function getTableOtherTypes() : array
    {
        return [
            'dip_status' => 'text',
            'dip_timestamp' => 'text'
        ];
    }

    public function getTableRow() : array
    {
        return [
            'dip_status' => $this->dip_status,
            'dip_timestamp' => $this->dip_timestamp
        ];
    }

    public function withTableRow(array $row) : self
    {
        $clone = clone($this);
        $clone->dip_status = $row['value'] ?? null;
        $clone->dip_timestamp = $row['value'] ?? null;
        return $clone;
    }

    /**
     * Get the status of the last change by DIP
     */
    public function getDipStatus() : ?string {
        return $this->dip_status;
    }

    /**
     * Get the timestamp of the last change by DIP
     */
    public function getDipTimestamp() : ?string {
        return $this->dip_timestamp;
    }

    /**
     * Get the record with processed status
     */
    public function asProcessed(): self {
        $clone = clone($this);
        $clone->dip_status = null;
        $clone->dip_timestamp = null;
        return $clone;
    }

}