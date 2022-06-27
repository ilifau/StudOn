<?php


/**
 * new class for campo data update cron job.
 */
class ilSyncWithCampoCron extends ilCronJob
{
    public function getId()
    {
        return "fau_sync_with_campo";
    }
    
    public function getTitle()
    {
        global $DIC;
        
        return $DIC->language()->txt("fau_campo_data_update");
    }
    
    public function getDescription()
    {
        global $DIC;
        
        return $DIC->language()->txt("fau_campo_data_update_info");
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

        $result = new \ilCronJobResult();

        // First synchronize the campo data from the staging database
        // this will not set the counters
        $service = $DIC->fau()->sync()->campo();
        $service->synchronize();
        if ($service->hasErrors()) {
            $result->setStatus(\ilCronJobResult::STATUS_FAIL);
            $result->setMessage(implode(', ', $service->getErrors()));
            return $result;
        }

        // Then create or update the ilias courses based on that data
//        $service = $DIC->fau()->sync()->ilias();
//        $service->synchronize();

        if ($service->hasErrors()) {
            $result->setStatus(\ilCronJobResult::STATUS_FAIL);
            $result->setMessage(implode(', ', $service->getErrors()));
        } else {
            $result->setStatus(\ilCronJobResult::STATUS_OK);
            $result->setMessage('Added Courses: ' . $service->getItemsAdded() . ', '
                . 'Updated Courses: ' . $service->getItemsUpdated() . ', '
            );
        }
        return $result;
    }
}
