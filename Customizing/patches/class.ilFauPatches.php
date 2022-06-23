<?php

use ILIAS\DI\Container;

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

    public function createFauTables()
	{
        $this->dic->fau()->cond()->migration()->createTables(true);
        $this->dic->fau()->org()->migration()->createTables(true);
        $this->dic->fau()->study()->migration()->createTables(true);
        $this->dic->fau()->user()->migration()->createTables(true);
	}


    public function migrateConditions()
    {
        $this->dic->fau()->cond()->migration()->fillCosConditionsFromStudydata($this->dic->fau()->staging()->database());
        $this->dic->fau()->cond()->migration()->fillDocConditionsFromStudydata();
    }

    /**
     * todo: temporary
     */
    public function createPersonsTable()
    {
        $this->dic->fau()->user()->migration()->createUserPersonsTable(true);
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
     * todo: temporary
     */
    public function createOrgTable()
    {
        $this->dic->fau()->org()->migration()->createTables(true);
    }


    /**
     * todo: temporary
     */
    public function syncOrgTable()
    {
        $service = $this->dic->fau()->sync()->org();
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