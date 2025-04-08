<?php

namespace FAU\Sync;

use ILIAS\DI\Container;
use FAU\Study\Data\Term;
use FAU\Staging\Data\StudOnMember;
use FAU\Staging\Data\StudOnCourse;
use FAU\Study\Data\Course;
use ilLPMarks;
use ilObject;

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
     * sync studon ref_id flag for parallel groups (courses)
     */
    public function syncStudOnRefId(Term $term) : void
    {
        $this->info('sync studon ref_id flag for parallel groups...');
        // get the courses in the staging database
        $existing = $this->staging->repo()->getStudOnCourses($term);
        foreach($existing as $course)
        {
            $study_course = $this->dic->fau()->study()->repo()->getCourse($course->getCourseId());
            $obj_id = $study_course->getIliasObjId() ?? null;
            if($obj_id != null)
            {
                $ref_ids = ilObject::_getAllReferences((int) $obj_id);
                $ref_id = null;
                if(isset($ref_ids) && count($ref_ids) == 1)
                {
                    $ref_id = end($ref_ids);      
                }     
                $course = $course->withStudOnRefId($ref_id);
                $this->staging->repo()->save($course);
            }
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
            $course = $this->rememberStudOnRefIdFromStaging($course);

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

    private function rememberStudOnRefIdFromStaging(StudOnCourse $course): StudOnCourse
    {
        $course_in_staging = $this->staging->repo()->getStudOnCourse($course->getCourseId());
        $course = ($course_in_staging != null) ? $course->withStudOnRefId($course_in_staging->getStudOnRefId()) : $course;
        return $course;
    }      
}

