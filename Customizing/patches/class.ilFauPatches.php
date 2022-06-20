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
     * temporary
     */
    public function createOrgTable()
    {
        $this->dic->fau()->org()->migration()->createTables(true);
    }


    /**
     * temporary
     */
    public function syncOrgTable()
    {
        $service = $this->dic->fau()->sync()->org();
        $service->synchronize();
    }


    public function checkOrgUnitRelations()
    {
        $service = $this->dic->fau()->org();
        $service->checkOrgUnitRelations();
    }


}