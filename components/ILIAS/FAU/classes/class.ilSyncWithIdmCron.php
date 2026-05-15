<?php

use ILIAS\Cron\Schedule\CronJobScheduleType;
use ILIAS\Cron\Job\JobResult;
use ILIAS\Cron\CronJob;

/**
 * fau: syncWithIdm - new class for idm data update cron job.
 */
class ilSyncWithIdmCron extends CronJob
{
    public function getId(): string
    {
        return "fau_sync_with_idm";
    }
    
    public function getTitle(): string
    {
        global $DIC;
        
        return $DIC->language()->txt("fau_idm_data_update");
    }
    
    public function getDescription(): string
    {
        global $DIC;
        
        return $DIC->language()->txt("fau_idm_data_update_info");
    }
    
    public function getDefaultScheduleType(): \ILIAS\Cron\Job\Schedule\JobScheduleType
    {
        return \ILIAS\Cron\Job\Schedule\JobScheduleType::IN_MINUTES;
    }
    
    public function getDefaultScheduleValue(): ?int
    {
        return 1;
    }
    
    public function hasAutoActivation(): bool
    {
        return false;
    }
    
    public function hasFlexibleSchedule(): bool
    {
        return true;
    }
    
    public function run(): JobResult
    {
        global $DIC;

        $service = $DIC->fau()->sync()->idm();
        $result = new JobResult();

        $service->synchronize();

        if ($service->hasErrors()) {
            $result->setStatus(JobResult::STATUS_FAIL);
            $result->setMessage(implode(', ', $service->getErrors()));
        } else {
            $result->setStatus(JobResult::STATUS_OK);
            $result->setMessage('Updated Users: ' . $service->getItemsUpdated());
        }
        
        return $result;
    }
}
