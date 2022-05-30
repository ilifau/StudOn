<?php


/**
 * new class for campo data update cron job.
 */
class ilSyncWithCampoCron extends ilCronJob
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
        return self::SCHEDULE_TYPE_IN_HOURS;
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
        global $DIC;

        $service = $DIC->fau()->sync()->campo();
        $result = new \ilCronJobResult();

        if ($service->hasErrors()) {
            $result->setStatus(\ilCronJobResult::STATUS_FAIL);
            $result->setMessage(implode(', ', $service->getErrors()));
        } else {
            $result->setStatus(\ilCronJobResult::STATUS_OK);
        }
        
        return $result;
    }
}
