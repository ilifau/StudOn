<?php

/**
 * Trait ilCampoStagingData
 */
trait ilCampoStagingDataFunctions
{

    /**
     * Get all records with an "inserted" or "updated" DIP status
     * @return static[]
     */
    public static function getAllRecords()
    {
        return self::get();
    }


    /**
     * Get all records with an "inserted" or "updated" DIP status
     * @return static[]
     */
    public static function getRecordsToProcess()
    {
        /** @var ActiveRecordList $list */
        $list = self::where("dip_status IS NOT NULL");
        return $list->get();
    }

    /**
     * Get all records with an "inserted" DIP status
     * @return static[]
     */
    public static function getAddedRecords()
    {
        /** @var ActiveRecordList $list */
        $list = self::where("dip_status = 'inserted'");
        return $list->get();
    }

    /**
     * Get all records with an "updated" DIP status
     * @return static[]
     */
    public static function getUpdatedRecords()
    {
        /** @var ActiveRecordList $list */
        $list = self::where("dip_status = 'updated'");
        return $list->get();
    }

    /**
     * Get all records with a "deleted" DIP status
     * @return static[]
     */
    public function getDeletedRecords()
    {
        /** @var ActiveRecordList $list */
        $list = self::where("dip_status = 'deleted'");
        return $list->get();

    }

    /**
     * Note that the status change has been processed by studon
     */
    public function markProcessed()
    {
        if ($this->dip_status == 'deleted') {
            $this->delete();
        }
        else {
            $this->dip_status = null;
            $this->update();
        }
    }

    /**
     * Check if the record is deleted by DIP
     * @return bool
     */
    public function isDipAdded()
    {
        return $this->dip_status == 'inserted';
    }

    /**
     * Check if the record is deleted by DIP
     * @return bool
     */
    public function isDipUpdated()
    {
        return $this->dip_status == 'updated';
    }

    /**
     * Check if the record is deleted by DIP
     * @return bool
     */
    public function isDipDeleted()
    {
        return $this->dip_status == 'deleted';
    }

}