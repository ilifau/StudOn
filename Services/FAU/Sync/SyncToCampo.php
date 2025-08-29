<?php

namespace FAU\Sync;

use ILIAS\DI\Container;
use FAU\Study\Data\Term;
use FAU\Staging\Data\StudOnMember;
use FAU\Staging\Data\StudOnCourse;
use FAU\Study\Data\Course;
use ilLPMarks;
use ilObject;
use ilParticipants;
use ilObjUser;
use ilDatePresentation;

/**
 * Synchronisation of course settings and members from StudOn to campo
 */
class SyncToCampo extends SyncBase
{
    protected Container $dic;

    /**
     * Synchronize data (called by cron job)
     * Counted items are the members
     */
    public function synchronize() : void
    {
        foreach ($this->sync->getTermsToSync(true) as $term) {
            $this->syncCourses($term);
            $this->syncMembersInTerm($term);
        }

        // 2023-11-06 passed members should be synced for all terms
        $this->syncAllPassedMembers();

        // sync the date of changing passed status for all members in studon_members
        $this->syncStatusChanged();

        // sync the studon ref_id for courses in studon_courses
        foreach ($this->sync->getTermsToSync(true) as $term) {
        
            $this->syncStudOnRefId($term);
        }

        // sync the registration period for courses in studon_courses
        foreach ($this->sync->getTermsToSync(true) as $term) {
        
            $this->syncRegPeriod($term);
        }        

        // sync the registration period for courses in studon_courses
        foreach ($this->sync->getTermsToSync(true) as $term) {
        
            $this->syncTutors($term);
        }          
    }

    /**
     * sync passed status changed
     */
    public function syncStatusChanged() : void
    {
        $this->info('sync status_changed of StudOnMembers...');
        // get the members in the staging database
        $existing = $this->staging->repo()->getStudOnMembers();
        foreach($existing as $member)
        {
            $usr_id = $this->dic->fau()->user()->repo()->getUserIdOfPerson($member->getPersonId());
            $course = $this->dic->fau()->study()->repo()->getCourse($member->getCourseId());
            $object_id = ($course != null) ? $course->getIliasObjId() : null;
            if($usr_id != null && $object_id != null)
            {
                $lp = new ilLPMarks($object_id, $usr_id);
                $member = $member->withStatusChanged($lp->getStatusChanged());
                $this->increaseItemsUpdated();
                $this->staging->repo()->save($member);
            }
        }
    }

    /**
     * sync studon ref_id for parallel groups (courses)
     */
    public function syncStudOnRefId(Term $term) : void
    {
        $this->info('sync studon ref_id for parallel groups...');
        // get the courses in the staging database
        $existing = $this->staging->repo()->getStudOnCourses($term);
        foreach($existing as $course)
        {
            $study_course = $this->dic->fau()->study()->repo()->getCourse($course->getCourseId());
            if($study_course == null)
                continue;
            $obj_id = $study_course->getIliasObjId() ?? null;
            if($obj_id != null)
            {
                $ref_id = null;
                $ref_id = $this->ilias->objects()->getUntrashedReference(($obj_id));
                $course = $course->withStudOnRefId($ref_id);
                $this->staging->repo()->save($course);
            }
        }
    }    

    /**
     * sync studon registration period for parallel groups (courses)
     */
    public function syncRegPeriod(Term $term) : void
    {
        $this->info('sync registration period for parallel groups...');
        // get the courses in the staging database
        $existing = $this->staging->repo()->getStudOnCourses($term);
        foreach($existing as $course)
        {
            $reg_period = "Anmeldezeitraum konnte nicht ermittelt werden.";
            $study_course = $this->dic->fau()->study()->repo()->getCourse($course->getCourseId());  
            if($study_course == null)
            {
                $course = $course->withRegPeriod($reg_period);
                $this->staging->repo()->save($course);
                continue;
            }
            $obj_id = $study_course->getIliasObjId() ?? null;
            $reg_period = "";
            if($obj_id != null)
            {
                $obj_type = ilObject::_lookupType($obj_id);
                $ref_id = $this->ilias->objects()->getUntrashedReference(($obj_id));
                
                if ($ref_id == null)
                {
                    $course = $course->withRegPeriod($reg_period);
                    $this->staging->repo()->save($course);
                    continue;
                }

                if ($obj_type == 'grp') 
                {
                    $ref_id = $this->dic->fau()->ilias()->objects()->findParentIliasCourse($ref_id);
                    if ($ref_id == null)
                    {
                        $course = $course->withRegPeriod($reg_period);
                        $this->staging->repo()->save($course);
                        continue;
                    }
                }
                $container_info = $this->dic->fau()->ilias()->objects()->getContainerInfo($ref_id);
                
                // registration period
                if (!$container_info->getRegEnabled()) {
                    $reg_period = $this->lng->txt('crs_list_reg_noreg', 'de');
                }
                elseif (!$container_info->hasTimeLimit()) {
                    $this->lng->loadLanguageModule('crs');
                    $reg_period = $this->lng->txt('crs_unlimited', 'de');
                }
                else {
                    ilDatePresentation::setUseRelativeDates(false);
                    $reg_period = ilDatePresentation::formatDate($container_info->getRegStart()) . ' - ' . ilDatePresentation::formatDate($container_info->getRegEnd());
                    ilDatePresentation::setUseRelativeDates(true);
                }
            }
            $course = $course->withRegPeriod($reg_period);
            $this->staging->repo()->save($course);
        }
    }      

    /**
     * sync studon tutors for parallel groups (courses)
     */
    public function syncTutors(Term $term) : void
    {
        $this->info('sync tutors for parallel groups...');
        // get the courses in the staging database
        $existing = $this->staging->repo()->getStudOnCourses($term);
        foreach($existing as $course)
        {
            $tutors = "Tutoren konnten nicht ermittelt werden.";
            $study_course = $this->dic->fau()->study()->repo()->getCourse($course->getCourseId());
            if($study_course == null)
            {
                $course = $course->withTutors($tutors);
                $this->staging->repo()->save($course);
                continue;
            }
            $obj_id = $study_course->getIliasObjId() ?? null;

            if($obj_id != null)
            {
                $obj_type = ilObject::_lookupType($obj_id);
                $ref_id = $this->ilias->objects()->getUntrashedReference(($obj_id));
                $tutor_names = [];
                $tutors = "";

                if($ref_id != null)
                {
                    $tutor_ids = [];
                    if ($obj_type == 'crs') 
                        $tutor_ids = ilParticipants::getInstance($ref_id)->getTutors();
                    else if ($obj_type == 'grp') 
                        $tutor_ids = ilParticipants::getInstance($ref_id)->getAdmins();

                    foreach($tutor_ids as $tutor_id)
                    {
                        $tutor_fullname = ilObjUser::_lookupName($tutor_id);
                        if($tutor_fullname["user_id"] === $tutor_id)                        
                            $tutor_names[] = $tutor_fullname["firstname"] . " " . $tutor_fullname["lastname"];
                    }

                    // Join array elements with comma
                    $tutors = implode(", ", $tutor_names);
                }   
            }
            $course = $course->withTutors($tutors);
            $this->staging->repo()->save($course);
        }
    } 
    
    
    /**
     * Update all courses in a term in the staging table
     */
    public function syncCourses(Term $term) : void
    {
        $this->info('sync StudOnCourses for Term ' . $term->toString() . '...');
        $existing = $this->staging->repo()->getStudOnCourses($term);

        foreach ($this->sync->repo()->getCoursesToSyncBack($term) as $course) {
            $course = $this->rememberFromStaging($course);

            if (!isset($existing[$course->key()])) {
                $this->staging->repo()->save($course);
            }
            elseif ($existing[$course->key()]->hash() != $course->hash()) {
                $this->staging->repo()->save($course);
            }
            // record is still needed
            unset($existing[$course->key()]);
        }

        // delete existing records that are no longer needed
        foreach ($existing as $course) {
            $this->staging->repo()->delete($course);
        }
    }


    /**
     * Update all members of courses in a term in the staging table
     */
    public function syncMembersInTerm(Term $term) : void
    {
        $this->info('sync StudOnMembers for Term ' . $term->toString() . '...');
        // get the members noted in the staging database
        $existing = $this->staging->repo()->getStudOnMembersInTerm($term);
        // get the module ids of modules for which a 'passed' status of members should be sent to campo
        $passing_module_ids = $this->sync->repo()->getModuleIdsToSendPassed();
        
        foreach ($this->sync->repo()->getMembersOfCoursesInTermToSyncBack($term) as $member) {
            $member = $this->rememberStatusChangedFromStaging($member);

            if ($member->getStatus() == StudOnMember::STATUS_PASSED) {
                // don't send a 'passed' status if the module does not allow it and a module is set
                if (!in_array($member->getModuleId(), $passing_module_ids) && $member->getModuleId() != null)
                {                
                    $member = $member->withStatus(StudOnMember::STATUS_REGISTERED);
                }

                // if module is not set, we need to find out if the course has event_id fitting to passing modules
                if($member->getModuleId() == null)
                {
                    $course_id = $member->getCourseId();
                    if(!$this->sendPassedCourseId($course_id, $passing_module_ids)){
                        $member = $member->withStatus(StudOnMember::STATUS_REGISTERED);
                    }
                }                
            }
            
            if (!isset($existing[$member->key()]) || $existing[$member->key()]->hash() != $member->hash()) {
                $this->staging->repo()->save($member);
                $this->increaseItemsUpdated();
            }
            // existing member in campo is still assigned in studon
            unset($existing[$member->key()]);
        }

        // delete remaining existing members in campo that are no longer assigned in studon
        // don't delete those of older courses where the studon object is deleted or connected with another course
        foreach ($existing as $member) {
            $this->staging->repo()->delete($member);
            $this->increaseItemsDeleted();
        }
    }

    /**
     *  Find out if the course has event_id fitting to passing modules
     */
    private function sendPassedCourseId(int $course_id, array $passing_module_ids): bool 
    {
        $course_event_id = $this->study->repo()->getCourse($course_id)->getEventId();
        $event_modules = $this->staging->repo()->getEventModulesWithEventID($course_event_id);

        foreach($event_modules as $event_module)
        {
            if($event_module->getEventId() == $course_event_id)
            {
                if(in_array($event_module->getModuleId(), $passing_module_ids))
                    return true;
            }
        }

        return false;
    }    

    /**
     * Update all passed members in the staging table, regardless of the term
     */
    public function syncAllPassedMembers() : void
    {
        $this->info('sync passed StudOnMembers...');

        // get the passedmembers noted in the staging database
        $existing = $this->staging->repo()->getPassedStudOnMembers();

        // get the module ids of modules for which a 'passed' status of members should be sent to campo
        $passing_module_ids = $this->sync->repo()->getModuleIdsToSendPassed();
        foreach ($this->sync->repo()->getPassedMembersOfCoursesToSyncBack($passing_module_ids) as $member) {
            $member = $this->rememberStatusChangedFromStaging($member);

            if (!isset($existing[$member->key()]) || $existing[$member->key()]->hash() != $member->hash()) {
                $this->staging->repo()->save($member);
                $this->increaseItemsUpdated();
            }
        }
    }

    /**
     * avoid sync overwrites status_changed with NULL 
     */
    private function rememberStatusChangedFromStaging(StudOnMember $member): StudOnMember
    {
        $member_in_staging = $this->staging->repo()->getStudOnMember($member->getCourseId(), $member->getPersonId());
        $member = ($member_in_staging != null) ? $member->withStatusChanged($member_in_staging->getStatusChanged()) : $member;
        return $member;
    }    

    /**
     * avoid sync overwrites new fields
     * 
    */
    private function rememberFromStaging(StudOnCourse $course): StudOnCourse
    {
        $course_in_staging = $this->staging->repo()->getStudOnCourse($course->getCourseId());
   
        if ($course_in_staging != null) {
            $course = $course->withStudOnRefId($course_in_staging->getStudOnRefId())
                ->withRegPeriod($course_in_staging->getRegPeriod())
                ->withTutors($course_in_staging->getTutors());
        }
        return $course;
    }      
}

