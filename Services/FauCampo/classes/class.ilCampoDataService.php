<?php

/**
* fau: campoData - new class for campo data service.
*/
class ilCampoDataService
{
    protected $courses_added = 0;
    protected $courses_updated = 0;
    protected $courses_deleted = 0;

    protected $errors = [];

    /**
     * Init access to staging data
     * These active records use a different database connector which has to be registered
     */
    public static function initStagingDataAccess()
    {
        require_once (__DIR__ . '/StagingData/class.ilCampoStagingDataConnector.php');
        require_once (__DIR__ . '/StagingData/class.ilCampoStagingEvent.php');
		require_once (__DIR__ . '/StagingData/class.ilCampoStagingModule.php');
		require_once (__DIR__ . '/StagingData/class.ilCampoStagingAchievement.php');
		require_once (__DIR__ . '/StagingData/class.ilCampoStagingCOS.php');
		require_once (__DIR__ . '/StagingData/class.ilCampoStagingCourse.php');
		require_once (__DIR__ . '/StagingData/class.ilCampoStagingCourseResponsible.php');
		require_once (__DIR__ . '/StagingData/class.ilCampoStagingEventModule.php');
		require_once (__DIR__ . '/StagingData/class.ilCampoStagingEventOrgUnit.php');
		require_once (__DIR__ . '/StagingData/class.ilCampoStagingIndividualDate.php');
		require_once (__DIR__ . '/StagingData/class.ilCampoStagingIndividualDateInstructor.php');
		require_once (__DIR__ . '/StagingData/class.ilCampoStagingModuleRestrictions.php');
		require_once (__DIR__ . '/StagingData/class.ilCampoStagingPlannedDate.php');
		require_once (__DIR__ . '/StagingData/class.ilCampoStagingPlannedDateInstructor.php');
		require_once (__DIR__ . '/StagingData/class.ilCampoStagingRequirement.php');
		require_once (__DIR__ . '/StagingData/class.ilCampoStagingRestriction.php');
		require_once (__DIR__ . '/StagingData/class.ilCampoStagingStudentCOS.php');

        $connector = new ilCampoStagingDataConnector();
        arConnectorMap::register(new ilCampoStagingEvent(), $connector);
		arConnectorMap::register(new ilCampoStagingModule(), $connector);
		arConnectorMap::register(new ilCampoStagingAchievement(), $connector);
		arConnectorMap::register(new ilCampoStagingCOS(), $connector);
		arConnectorMap::register(new ilCampoStagingCourse(), $connector);
		arConnectorMap::register(new ilCampoStagingCourseResponsible(), $connector);
		arConnectorMap::register(new ilCampoStagingEventModule(), $connector);
		arConnectorMap::register(new ilCampoStagingEventOrgUnit(), $connector);
		arConnectorMap::register(new ilCampoStagingIndividualDate(), $connector);
		arConnectorMap::register(new ilCampoStagingIndividualDateInstructor(), $connector);
		arConnectorMap::register(new ilCampoStagingModuleRestrictions(), $connector);
		arConnectorMap::register(new ilCampoStagingPlannedDate(), $connector);
		arConnectorMap::register(new ilCampoStagingPlannedDateInstructor(), $connector);
		arConnectorMap::register(new ilCampoStagingRequirement(), $connector);
		arConnectorMap::register(new ilCampoStagingRestriction(), $connector);
		arConnectorMap::register(new ilCampoStagingStudentCOS(), $connector);


    }

    /**
     * Init access to studon data
     * These records use the standard database connector, so the just have to be included
     */
    public static function initStudOnDataAccess()
    {
        require_once (__DIR__ . '/StudOnData/class.ilCampoEvent.php');
		require_once (__DIR__ . '/StudOnData/class.ilCampoModule.php');
		require_once (__DIR__ . '/StudOnData/class.ilCampoAchievement.php');
		require_once (__DIR__ . '/StudOnData/class.ilCampoCOS.php');
		require_once (__DIR__ . '/StudOnData/class.ilCampoCourse.php');
		require_once (__DIR__ . '/StudOnData/class.ilCampoCourseResponsible.php');
		require_once (__DIR__ . '/StudOnData/class.ilCampoEventModule.php');
		require_once (__DIR__ . '/StudOnData/class.ilCampoEventOrgUnit.php');
		require_once (__DIR__ . '/StudOnData/class.ilCampoIndividualDate.php');
		require_once (__DIR__ . '/StudOnData/class.ilCampoIndividualDateInstructor.php');
		require_once (__DIR__ . '/StudOnData/class.ilCampoModuleRestrictions.php');
		require_once (__DIR__ . '/StudOnData/class.ilCampoPlannedDate.php');
		require_once (__DIR__ . '/StudOnData/class.ilCampoPlannedDateInstructor.php');
		require_once (__DIR__ . '/StudOnData/class.ilCampoRequirement.php');
		require_once (__DIR__ . '/StudOnData/class.ilCampoRestriction.php');
		require_once (__DIR__ . '/StudOnData/class.ilCampoStudentCOS.php');
    }


    /**
     * Delete the data tables in studon
     */
    public static function deleteStudOnDataTables()
    {
        global $DIC;
        $ilDB = $DIC->database();

        $ilDB->dropTable('campo_event', false);
        $ilDB->dropTable('campo_achievement', false);
        $ilDB->dropTable('campo_cos', false);
        $ilDB->dropTable('campo_course', false);
        $ilDB->dropTable('campo_course_responsible', false);
        $ilDB->dropTable('campo_event_module', false);
        $ilDB->dropTable('campo_event_org_unit', false);
        $ilDB->dropTable('campo_individual_date', false);
        $ilDB->dropTable('campo_individual_date_instructor', false);
        $ilDB->dropTable('campo_module', false);
        $ilDB->dropTable('campo_module_restrictions', false);
        $ilDB->dropTable('campo_planned_date', false);
        $ilDB->dropTable('campo_planned_date_instructor', false);
        $ilDB->dropTable('campo_requirement', false);
        $ilDB->dropTable('campo_restriction', false);
        $ilDB->dropTable('campo_student_cos', false);
    }


    /**
     * Create the data tables in studon
     */
    public static function createStudOnDataTables()
    {
        ilCampoEvent::createTable();
        ilCampoAchievement::createTable();
        ilCampoCOS::createTable();
        ilCampoCourse::createTable();
        ilCampoCourseResponsible::createTable();
        ilCampoEventModule::createTable();
        ilCampoEventOrgUnit::createTable();
        ilCampoIndividualDate::createTable();
        ilCampoIndividualDateInstructor::createTable();
        ilCampoModule::createTable();
        ilCampoModuleRestrictions::createTable();
        ilCampoPlannedDate::createTable();
        ilCampoPlannedDateInstructor::createTable();
        ilCampoRequirement::createTable();
        ilCampoRestriction::createTable();
        ilCampoStudentCOS::createTable();
    }
}
