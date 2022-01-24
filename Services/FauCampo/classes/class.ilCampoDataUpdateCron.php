<?php

require_once(__DIR__ . '/class.ilCampoDataUpdate.php');

/**
 * fau: campoData - new class for campo data update cron job.
 *
 * @ingroup ServicesFauCampo
 */
class ilCampoDataUpdateCron extends ilCronJob
{
    public function getId()
    {
        return "campo_data_update_cron";
    }
    
    public function getTitle()
    {
        global $DIC;
        
        return $DIC->language()->txt("campo_data_update");
    }
    
    public function getDescription()
    {
        global $DIC;
        
        return $DIC->language()->txt("campo_data_update_info");
    }
    
    public function getDefaultScheduleType()
    {
        return self::SCHEDULE_TYPE_DAILY;
    }
    
    public function getDefaultScheduleValue()
    {
        return 1;
    }
    
    public function hasAutoActivation()
    {
        return false;
    }
    
    public function hasFlexibleSchedule()
    {
        return true;
    }
    
    public function run()
    {
        $result = new ilCronJobResult();

        // uncomment this to download the database update steps for an active record table
//        ilCampoDataService::initStagingDataAccess();
//        ilCampoDataService::initStudOnDataAccess();
//        $arBuilder = new arBuilder(new ilCampoEvent());
//        $arBuilder->generateDBUpdateForInstallation();
//        $result->setStatus(ilCronJobResult::STATUS_OK);
//        return $result;

        // uncomment this to recreate the database tables
//        ilCampoDataService::deleteStudOnDataTables();
//        ilCampoDataService::createStudOnDataTables();
//        $result->setStatus(ilCronJobResult::STATUS_OK);
//        return $result;


        // normal call
        $campoDataUpdate = new ilCampoDataUpdate();
        $campoDataUpdate->updateDataFromStaging();

        if ($campoDataUpdate->hasErrors()) {
            $result->setStatus(ilCronJobResult::STATUS_FAIL);
            $result->setMessage(implode(', ', $campoDataUpdate->getErrors()));
        } else {
            $result->setStatus(ilCronJobResult::STATUS_OK);
        }
        
        return $result;
    }
}
