<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once "Services/Cron/classes/class.ilCronJob.php";

// fau: campusSub - new class ilMyCampusCron.
/**
 * Cron for auto-filling course/group after fair period
 *
 * @author Fred Neumann <fred.neumann@fau.de>
 * @ingroup ServicesMyCampus
 */
class ilMyCampusCron extends ilCronJob
{
    public function getId()
    {
        return "mycampus_sync";
    }
    
    public function getTitle()
    {
        global $DIC;
        return $DIC->language()->txt("cron_mycampus_sync");
    }
    
    public function getDescription()
    {
        global $DIC;
        return $DIC->language()->txt("cron_mycampus_sync_info");
    }
    
    public function getDefaultScheduleType()
    {
        return self::SCHEDULE_TYPE_IN_MINUTES;
    }
    
    public function getDefaultScheduleValue()
    {
        return 60;
    }
    
    public function hasAutoActivation()
    {
        return true;
    }
    
    public function hasFlexibleSchedule()
    {
        return true;
    }
    
    public function run()
    {
        global $DIC;

        require_once("Services/MyCampus/classes/class.ilMyCampusSynchronisation.php");
        $syncObj = new ilMyCampusSynchronisation();
        $syncObj->start();

        if ($syncObj->hasError()) {
            $status = ilCronJobResult::STATUS_FAIL;
            $message = $syncObj->getError();
        } else {
            $added = $syncObj->getAdded();
            if ($added > 0) {
                $status = ilCronJobResult::STATUS_OK;
                $message = sprintf($DIC->language()->txt('cron_mycampus_sync_added'), $added) ;
            } else {
                $status = ilCronJobResult::STATUS_NO_ACTION;
                $message = null;
            }
        }
        
        $result = new ilCronJobResult();
        $result->setStatus($status);
        $result->setMessage($message);
        
        return $result;
    }
}
