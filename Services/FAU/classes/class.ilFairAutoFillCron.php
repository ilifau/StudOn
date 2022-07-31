<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once "Services/Cron/classes/class.ilCronJob.php";

/**
 * Cron job for auto-filling course/group after fair period
 */
class ilFairAutofillCron extends ilCronJob
{
    public function getId()
    {
        return "fau_fair_autofill";
    }
    
    public function getTitle()
    {
        global $DIC;
        
        return $DIC->language()->txt("fair_autofill_cron");
    }
    
    public function getDescription()
    {
        global $DIC;
        
        return $DIC->language()->txt("fair_autofill_cron_info");
    }
    
    public function getDefaultScheduleType()
    {
        return self::SCHEDULE_TYPE_IN_MINUTES;
    }
    
    public function getDefaultScheduleValue()
    {
        return 10;
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
        global $lng;

        $status = ilCronJobResult::STATUS_NO_ACTION;
        $message = null;

        $filled = 0;
        $filled += $this->fillCourses();
        $filled += $this->fillGroups();
    
        if ($filled > 0) {
            $status = ilCronJobResult::STATUS_OK;
            $message = sprintf($lng->txt('fair_autofill_cron_result'), $filled) ;
        }
        
        $result = new ilCronJobResult();
        $result->setStatus($status);
        $result->setMessage($message);
        
        return $result;
    }
    
    protected function fillCourses()
    {
        global $DIC;

        $filled = 0;
        foreach ($DIC->fau()->ilias()->repo()->findFairAutoFillCourseIds() as $obj_id) {
            foreach (ilObject::_getAllReferences($obj_id) as $ref_id) {
                if (!ilObject::_isInTrash($ref_id)) {
                    $course = new ilObjCourse($ref_id);
                    $filled += count($DIC->fau()->ilias()->getRegistration($course)->handleAutoFill(false, true));
                    unset($course);
                    break;
                }
            }
        }
        return $filled;
    }
    
    protected function fillGroups()
    {
        global $DIC;

        $filled = 0;
        foreach ($DIC->fau()->ilias()->repo()->findFairAutoFillGroupIds() as $obj_id) {
            foreach (ilObject::_getAllReferences($obj_id) as $ref_id) {
                if (!ilObject::_isInTrash($ref_id)) {
                    $group = new ilObjGroup($ref_id);
                    $filled += count($DIC->fau()->ilias()->getRegistration($group)->handleAutoFill(false, true));
                    unset($group);
                    break;
                }
            }
        }
        return $filled;
    }
}
