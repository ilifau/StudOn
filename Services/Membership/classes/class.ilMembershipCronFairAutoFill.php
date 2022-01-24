<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once "Services/Cron/classes/class.ilCronJob.php";

// fau: fairSub - new class ilMembershipCronFairAutoFill.
/**
 * Cron for auto-filling course/group after fair period
 *
 * @author Fred Neumann <fred.neumann@fau.de>
 * @ingroup ServicesMembership
 */
class ilMembershipCronFairAutoFill extends ilCronJob
{
    public function getId()
    {
        return "mem_autofill_fair";
    }
    
    public function getTitle()
    {
        global $lng;
        
        return $lng->txt("mem_cron_autofill_fair");
    }
    
    public function getDescription()
    {
        global $lng;
        
        return $lng->txt("mem_cron_autofill_fair_info");
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
            $message = sprintf($lng->txt('mem_cron_autofill_fair_result'), $filled) ;
        }
        
        $result = new ilCronJobResult();
        $result->setStatus($status);
        $result->setMessage($message);
        
        return $result;
    }
    
    protected function fillCourses()
    {
        include_once "Modules/Course/classes/class.ilObjCourse.php";

        $filled = 0;
        foreach (ilObjCourse::findFairAutoFill() as $obj_id) {
            $ref_id = array_pop(ilObject::_getAllReferences($obj_id));
            $course = new ilObjCourse($ref_id);
            $filled += count($course->handleAutoFill(false, true));
            unset($course);
        }
        return $filled;
    }
    
    protected function fillGroups()
    {
        include_once "Modules/Group/classes/class.ilObjGroup.php";

        $filled = 0;
        foreach (ilObjGroup::findFairAutoFill() as $obj_id) {
            $ref_id = array_pop(ilObject::_getAllReferences($obj_id));
            $group = new ilObjGroup($ref_id);
            $filled += count($group->handleAutoFill(false, true));
            unset($group);
        }
        return $filled;
    }
}
