<?php


/**
 * new class for campo data update cron job.
 */
class ilSyncToCampoCron extends ilCronJob
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
    
    public function getDefaultScheduleType(): int
    {
        return self::SCHEDULE_TYPE_IN_MINUTES;
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
    
    public function run(): ilCronJobResult
    {
        global $DIC;

        $result = new \ilCronJobResult();

        // Then create or update the ilias courses based on that data
        $service = $DIC->fau()->sync()->toCampo();
        $service->synchronize();

        if ($service->hasErrors()) {
            $result->setStatus(\ilCronJobResult::STATUS_FAIL);
            $result->setMessage(implode(', ', $service->getErrors()));
        } else {
            $result->setStatus(\ilCronJobResult::STATUS_OK);
            $result->setMessage('Added Members: ' . $service->getItemsAdded() . ', '
                . 'Updated Members: ' . $service->getItemsUpdated() . ', '
                . 'Deleted Members: ' . $service->getItemsDeleted()
            );
        }
        return $result;
    }
}
