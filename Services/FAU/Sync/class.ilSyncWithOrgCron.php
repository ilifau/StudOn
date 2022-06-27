<?php


/**
 * new class for fau.org data update cron job.
 */
class ilSyncWithOrgCron extends ilCronJob
{
    public function getId()
    {
        return "fau_sync_with_org";
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
        return self::SCHEDULE_TYPE_IN_HOURS;
    }
    
    public function getDefaultScheduleValue()
    {
        return 24;
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

        $service = $DIC->fau()->sync()->org();
        $result = new \ilCronJobResult();

        $service->synchronize();

        if ($service->hasErrors()) {
            $result->setStatus(\ilCronJobResult::STATUS_FAIL);
            $result->setMessage(implode(', ', $service->getErrors()));
        } else {
            $result->setStatus(\ilCronJobResult::STATUS_OK);
            $result->setMessage('Added Units: ' . $service->getItemsAdded() . ', '
                . 'Updated Units: ' . $service->getItemsUpdated() . ', '
                . 'Deleted Units: ' . $service->getItemsDeleted()
            );
        }
        
        return $result;
    }
}
