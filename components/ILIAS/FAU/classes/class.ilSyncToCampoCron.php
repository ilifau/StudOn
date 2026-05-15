<?php

use ILIAS\Cron\Schedule\CronJobScheduleType;
use ILIAS\Cron\Job\JobResult;
use ILIAS\Cron\CronJob;

/**
 * fau: syncToCampo - new class for campo data update cron job.
 */
class ilSyncToCampoCron extends CronJob
{
    public function getId(): string
    {
        return "fau_sync_to_campo";
    }
    
    public function getTitle(): string
    {
        global $DIC;
        
        return $DIC->language()->txt("fau_campo_members_update");
    }
    
    public function getDescription(): string
    {
        global $DIC;
        
        return $DIC->language()->txt("fau_campo_members_update_info");
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

        $result = new JobResult();

        // Then create or update the ilias courses based on that data
        $service = $DIC->fau()->sync()->toCampo();
        $service->synchronize();

        if ($service->hasErrors()) {
            $result->setStatus(JobResult::STATUS_FAIL);
            $result->setMessage(implode(', ', $service->getErrors()));
        } else {
            $result->setStatus(JobResult::STATUS_OK);
            $result->setMessage('Added Members: ' . $service->getItemsAdded() . ', '
                . 'Updated Members: ' . $service->getItemsUpdated() . ', '
                . 'Deleted Members: ' . $service->getItemsDeleted()
            );
        }
        return $result;
    }
}
