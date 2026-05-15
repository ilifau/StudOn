<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

use ILIAS\Cron\Schedule\CronJobScheduleType;
use ILIAS\Cron\Job\JobResult;
use ILIAS\Cron\CronJob;

/**
 * Cron job for auto-filling course/group after fair period
 */
class ilFairAutoFillCron extends CronJob
{
    public function getId(): string
    {
        return "fau_fair_autofill";
    }
    
    public function getTitle(): string
    {
        global $DIC;
        
        return $DIC->language()->txt("fair_autofill_cron");
    }
    
    public function getDescription(): string
    {
        global $DIC;
        
        return $DIC->language()->txt("fair_autofill_cron_info");
    }
    
    public function getDefaultScheduleType(): \ILIAS\Cron\Job\Schedule\JobScheduleType
    {
        return \ILIAS\Cron\Job\Schedule\JobScheduleType::IN_MINUTES;
    }
    
    public function getDefaultScheduleValue(): ?int
    {
        return 10;
    }
    
    public function hasAutoActivation(): bool
    {
        return true;
    }
    
    public function hasFlexibleSchedule(): bool
    {
        return true;
    }
    
    public function run(): JobResult
    {
        global $lng;

        $status = JobResult::STATUS_NO_ACTION;
        $message = "";

        $filled = 0;
        $filled += $this->fillCourses();
        $filled += $this->fillGroups();
    
        if ($filled > 0) {
            $status = JobResult::STATUS_OK;
            $message = sprintf($lng->txt('fair_autofill_cron_result'), $filled) ;
        }
        
        $result = new JobResult();
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
                    $filled += count($DIC->fau()->ilias()->getRegistration($course)->doAutoFill(false, true));
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
                    $filled += count($DIC->fau()->ilias()->getRegistration($group)->doAutoFill(false, true));
                    unset($group);
                    break;
                }
            }
        }
        return $filled;
    }
}
