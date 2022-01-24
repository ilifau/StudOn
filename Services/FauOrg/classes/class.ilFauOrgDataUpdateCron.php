<?php

/**
 * fau: orgData - new class for ord data update cron job.
 *
 * @ingroup ServicesFauCampo
 */
class ilFauOrgDataUpdateCron extends ilCronJob
{
    public function getId()
    {
        return "fau_org_data_update_cron";
    }
    
    public function getTitle()
    {
        global $DIC;
        
        return $DIC->language()->txt("fau_org_data_update");
    }
    
    public function getDescription()
    {
        global $DIC;
        
        return $DIC->language()->txt("fau_org_data_update_info");
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
        $result->setStatus(ilCronJobResult::STATUS_NO_ACTION);
        
        return $result;
    }
}
