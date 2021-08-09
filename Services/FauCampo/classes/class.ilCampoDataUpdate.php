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
     * Update the studon tables from the staging tables
     * @param bool $complete
     */
    public function updateDataFromStaging($complete = false)
    {
        // load all active record classes
        ilCampoDataService::initStagingDataAccess();
        ilCampoDataService::initStudOnDataAccess();

        $this->updateEvents($complete);
        return true;
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
            if ($stagingEvent->isDipDeleted()) {
                $studonEvent->delete();
            }
            else {
                $studonEvent->eventtype = $stagingEvent->eventtype;
                $studonEvent->title = $stagingEvent->title;
                $studonEvent->shorttext = $stagingEvent->shorttext;
                $studonEvent->comment = $stagingEvent->comment;
                $studonEvent->guest = $stagingEvent->guest;
                $studonEvent->save();
            }
            $stagingEvent->markProcessed();
        }
    }
}
