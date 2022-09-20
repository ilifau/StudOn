<?php

use ILIAS\DI\Container;
use FAU\Setup\Setup;
use FAU\Study\Data\Term;

/**
 * fau: fauService - patches to handle fau data
 */
class ilFauPatches
{

    protected Container $dic;


    public function __construct()
    {
        global $DIC;
        $this->dic = $DIC;
    }

    public function syncCampoData()
    {
        $service = $this->dic->fau()->sync()->campo();
        $service->synchronize();
    }

    public function syncRestrictions()
    {
        $service = $this->dic->fau()->sync()->campo();
        $service->syncModuleRestrictions();
        $service->syncEventRestrictions();
    }

    /**
     * todo: move to cron job if performance is ok
     */
    public function syncPersonData()
    {
        $service = $this->dic->fau()->sync()->idm();
        $service->synchronize();
    }

    /**
     * Migrate the conditions from the old study tables to the new fau_study tables
     */
    public function migrateConditions()
    {
        Setup::instance($this->dic->database())->cond()->fillCosConditionsFromStudydata($this->dic->fau()->staging()->database());
        Setup::instance($this->dic->database())->cond()->fillDocConditionsFromStudydata();
    }


    public function syncWithIlias($params = ['orgunit_id' => null, 'negate' => false])
    {
        $service = $this->dic->fau()->sync()->ilias();
        $service->synchronize($params['orgunit_id'], $params['negate']);
    }

    /**
     * todo: move to cron job when finished
     */
    public function checkOrgUnitRelations()
    {
        $service = $this->dic->fau()->sync()->trees();
        $service->checkOrgUnitRelations();
    }

    /**
     * Create the courses of a term or with specific ids
     */
    public function createCourses($params = ['term' => '20222', 'course_ids' => null, 'test_run' => true])
    {
        $service = $this->dic->fau()->sync()->ilias();
        $service->createCourses(Term::fromString($params['term']), $params['course_ids'], $params['test_run']);
    }

    /**
     * Create the courses of a term or with specific ids
     */
    public function updateCourses($params = ['term' => '20222', 'course_ids' => null, 'test_run' => true])
    {
        $service = $this->dic->fau()->sync()->ilias();
        $service->updateCourses(Term::fromString($params['term']), $params['course_ids'], $params['test_run']);
    }

    /**
     * Move courses from the faculties ot the fallback category to their correct destination, if possible
     */
    public function moveLostCourses($params = ['term' => '20222'])
    {
        $service = $this->dic->fau()->sync()->ilias();
        $service->moveLostCourses(Term::fromString($params['term']));
    }

    /**
     * Create the emissing manager and author roles in a category
     */
    public function createMissingOrgRoles($params = ['exclude' => []])
    {
        $service = $this->dic->fau()->sync()->ilias();
        $service->createMissingOrgRoles($params['exclude']);
    }
}