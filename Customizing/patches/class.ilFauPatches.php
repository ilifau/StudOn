<?php

use ILIAS\DI\Container;
use FAU\Setup\Setup;

/**
 * fau: fauService - patch to create the tables
 */
class ilFauPatches
{

    protected Container $dic;


    public function __construct()
    {
        global $DIC;
        $this->dic = $DIC;
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


    public function syncWithIlias()
    {
        $service = $this->dic->fau()->sync()->ilias();
        $service->synchronize();
    }

    /**
     * todo: move to cron job when finished
     */
    public function checkOrgUnitRelations()
    {
        $service = $this->dic->fau()->org();
        $service->checkOrgUnitRelations();
    }


}