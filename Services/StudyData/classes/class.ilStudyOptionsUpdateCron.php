<?php
/* Copyright (c) 1998-2020 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once "Services/Cron/classes/class.ilCronJob.php";

// fau: campoData - new class ilStudyOptionsUpdateCron.
/**
 * Cron for update of option lists for the study data
 *
 * @author Fred Neumann <fred.neumann@fau.de>
 * @ingroup ServicesStudyData
 */
class ilStudyOptionsUpdateCron extends ilCronJob
{
    public function getId()
    {
        return "study_options_update";
    }
    
    public function getTitle()
    {
        global $DIC;
        
        return $DIC->language()->txt("studydata_options_update");
    }
    
    public function getDescription()
    {
        global $DIC;
        
        return $DIC->language()->txt("studydata_options_update_info");
    }
    
    public function getDefaultScheduleType()
    {
        return self::SCHEDULE_TYPE_DAILY;
    }
    
    public function getDefaultScheduleValue()
    {
        return 1;
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

        $status = ilCronJobResult::STATUS_NO_ACTION;
        $messages = [];

        require_once('Services/Idm/classes/class.ilIdmData.php');
        $idmData = new ilIdmData();

        $degrees = $idmData->updateStudyDegrees();
        $schools = $idmData->updateStudySchools();
        $subjects = $idmData->updateStudySubjects();
        $programs = $idmData->updateDocPrograms();

        $messages[] = $DIC->language()->txt('studydata_degree') . ': ' . $degrees;
        $messages[] = $DIC->language()->txt('studydata_school') . ': ' . $schools;
        $messages[] = $DIC->language()->txt('studydata_subject') . ': ' . $subjects;
        $messages[] = $DIC->language()->txt('studydata_promotion_program') . ': ' . $programs;

        if ($degrees == 0 || $schools == 0 || $subjects == 0 || $programs == 0) {
            $status = ilCronJobResult::STATUS_FAIL;
        } else {
            $status = ilCronJobResult::STATUS_OK;
        }

        $result = new ilCronJobResult();
        $result->setStatus($status);
        $result->setMessage(implode(', ', $messages));
        
        return $result;
    }
}
