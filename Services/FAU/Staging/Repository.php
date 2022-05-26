<?php

namespace FAU\Staging;

use FAU\Staging\Data\Education;
use FAU\Staging\Data\DipData;
use FAU\RecordRepo;

/**
 * Repository for accessing the staging database
 */
class Repository extends RecordRepo
{
    public const DIP_INSERTED = 'inserted';
    public const DIP_CHANGED = 'changed';
    public const DIP_DELETED = 'deleted';
    public const DIP_MARKED = 'marked';


    /**
     * @return Education[]
     */
    public function getEducationsToDo() : array
    {
        return $this->getDipRecords(new Education());
    }

    public function setEducationDone(Education $record)
    {
        $this->setDipRecordAsProcessed($record);
    }



    /**
     * Get the record objects for DIP table rows with a certain status
     * @return DipData[]
     */
    protected function getDipRecords(DipData $prototype, string $dip_status = self::DIP_MARKED) : array
    {
        $query = "SELECT * FROM " . $this->db->quoteIdentifier($prototype::getTableName())
            . " WHERE " . $this->getDipStatusCondition($dip_status);
        return $this->queryRecords($query, $prototype);
    }

    /**
     * @param DipData $record
     */
    protected function setDipRecordAsProcessed(DipData $record)
    {
        switch ($record->getDipStatus()) {
            case self::DIP_INSERTED:
            case self::DIP_CHANGED:
                $this->updateRecord($record->asProcessed());
                break;
            case self::DIP_DELETED:
                $this->deleteRecord($record);
        }
    }


    /**
     * Get the SQL condition to query for a DIP status
     */
    private function getDipStatusCondition(string $dip_status) : string
    {
        switch ($dip_status) {
            case self::DIP_INSERTED:
                return "dip_status = 'inserted'";
            case self::DIP_CHANGED:
                return "dip_status = 'changed'";
            case self::DIP_DELETED:
                return "dip_status = 'deleted'";
            case self::DIP_MARKED:
            default:
                return "dip_status IS NOT NULL";
        }
    }
}