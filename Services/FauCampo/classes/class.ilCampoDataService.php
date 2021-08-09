<?php

/**
* fau: campoData - new class for campo data service.
*/
class ilCampoDataService
{
    protected $courses_added = 0;
    protected $courses_updated = 0;
    protected $courses_deleted = 0;

    protected $errors = [];

    /**
     * Init access to staging data
     * These active records use a different database connector which has to be registered
     */
    public static function initStagingDataAccess()
    {
        require_once (__DIR__ . '/StagingData/class.ilCampoStagingDataConnector.php');
        require_once (__DIR__ . '/StagingData/class.ilCampoStagingEvent.php');

        $connector = new ilCampoStagingDataConnector();
        arConnectorMap::register(new ilCampoStagingEvent(), $connector);
    }

    /**
     * Init access to studon data
     * These records use the standard database connector, so the just have to be included
     */
    public static function initStudOnDataAccess()
    {
        require_once (__DIR__ . '/StudOnData/class.ilCampoEvent.php');
    }


    /**
     * Delete the data tables in studon
     */
    public static function deleteStudOnDataTables()
    {
        global $DIC;
        $ilDB = $DIC->database();

        $ilDB->dropTable('campo_event', false);
    }


    /**
     * Create the data tables in studon
     */
    public static function createStudOnDataTables()
    {
        ilCampoEvent::createTable();
    }
}
