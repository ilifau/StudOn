<?php

require_once(__DIR__ . '/class.ilCampoDataService.php');

/**
* fau: campoData - new class for campo data update.
*/
class ilCampoDataUpdate
{
    protected $courses_added = 0;
    protected $courses_updated = 0;
    protected $courses_deleted = 0;

    protected $errors = [];


    /**
     * Get the number of added course
     */
    public function getCoursesAdded() : int
    {
        return $this->courses_added;
    }

    /**
     * Get the number of updated course
     */
    public function getCoursesUpdated() : int
    {
        return $this->courses_updated;
    }

    /**
     * Get the number of deleted courses
     */
    public function getCoursesDeleted() : int
    {
        return $this->courses_deleted;
    }

    /**
     * Check if the call produced an error
     */
    public function hasErrors() : bool
    {
        return !empty($this->errors);
    }

    /**
     * Get a list of error messages
     */
    public function getErrors() : array
    {
        return $this->errors;
    }

    /**
     * Helper function for Update Campo: StudOn-Database with Staging-Database
     * @param $stagingClass
     * @param $studonClass
     */
    protected function writeStagingVarsToStudonVars($stagingClass, $studonClass) {
        $studon_variables = get_object_vars($studonClass);

        if ($stagingClass->isDipDeleted()) {
            $studonClass->delete();
        }
        else {
            foreach ($studon_variables as $key=>$value) {
                $studonClass->$key = $stagingClass->$key;
            }
            $studonClass->save();
        }
    }

    /**
     * updating the studon-table-contents with staging-table-contents
     * @param false $complete
     * @param $stagingClassName
     * @param $studonClassName
     * @param $primaryKeyName
     */

    protected function updateStudonWithStagingValues($complete = false, $stagingClassName, $studonClassName, $primaryKeyName)
    {
        if ($complete) {
            $stagings = $stagingClassName::getAllRecords();
        }
        else {
            $stagings = $stagingClassName::getRecordsToProcess();
        }

        foreach ($stagings as $staging) {
            $studon = $studonClassName::findOrGetInstance($staging->$primaryKeyName);
            $this->writeStagingVarsToStudonVars($staging, $studon);
            $staging->markProcessed();
        }
    }

    /**
     * Update the events data from the staging database
     * @param bool $complete
     */
    protected function updateEvents($complete = false)
    {
        if ($complete) {
            $stagingEvents = ilCampoStagingEvent::getAllRecords();
        }
        else {
            $stagingEvents = ilCampoStagingEvent::getRecordsToProcess();
        }

        foreach ($stagingEvents as $stagingEvent) {
            $studonEvent = ilCampoEvent::findOrGetInstance($stagingEvent->event_id);
            $this->writeStagingVarsToStudonVars($stagingEvent, $studonEvent);
            $stagingEvent->markProcessed();
        }
    }
    /**
     * Update the studon tables from the staging tables
     * @param bool $complete
     */
    public function updateDataFromStaging($complete = false)
    {
        // load all active record classes
        ilCampoDataService::initStagingDataAccess();
        ilCampoDataService::initStudOnDataAccess();

        $this->updateEvents($complete); //is just still here for seeing if writing etc, is possible
        $this->updateStudonWithStagingValues($complete, 'ilCampoStagingCOS', 'ilCampoCOS', 'cos_id');
        $this->updateStudonWithStagingValues($complete, 'ilCampoStagingCourse', 'ilCampoCourse', 'course_id');
        $this->updateStudonWithStagingValues($complete, 'ilCampoStagingCourseResponsible', 'ilCampoCourseResponsible', 'course_id');
        $this->updateStudonWithStagingValues($complete, 'ilCampoStagingEventModule', 'ilCampoEventModule', 'module_id');
        $this->updateStudonWithStagingValues($complete, 'ilCampoStagingEventOrgUnit', 'ilCampoEventOrgUnit', 'event_id');
        $this->updateStudonWithStagingValues($complete, 'ilCampoStagingIndividualDate', 'ilCampoIndividualDate', 'individual_dates_id');
        $this->updateStudonWithStagingValues($complete, 'ilCampoStagingIndividualDateInstructor', 'ilCampoIndividualDateInstructor', 'individual_dates_id');
        $this->updateStudonWithStagingValues($complete, 'ilCampoStagingModule', 'ilCampoModule', 'module_id');
        $this->updateStudonWithStagingValues($complete, 'ilCampoStagingModuleRestrictions', 'ilCampoModuleRestrictions', 'restriction');
        $this->updateStudonWithStagingValues($complete, 'ilCampoStagingPlannedDateInstructor', 'ilCampoPlannedDateInstructor', 'planned_dates_id');
        $this->updateStudonWithStagingValues($complete, 'ilCampoStagingRequirement', 'ilCampoRequirement', 'requirement_id');
        $this->updateStudonWithStagingValues($complete, 'ilCampoStagingRestriction', 'ilCampoRestriction', 'restriction');
        //$this->updateStudonWithStagingValues($complete, 'ilCampoStagingStudentCOS', 'ilCampoStudentCOS', 'cos_id');
        return true;
    }
}

