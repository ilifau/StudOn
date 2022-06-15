<?php
/**
 * fau: fauService - patch to create the tables
 */
class ilFauPatches
{
	public function createFauTables()
	{
        global $DIC;
        $DIC->fau()->cond()->migration()->createTables(true);
        $DIC->fau()->org()->migration()->createTables(true);
        $DIC->fau()->study()->migration()->createTables(true);
        $DIC->fau()->user()->migration()->createTables(true);
	}


    public function createOrgTable()
    {
        global $DIC;
        $DIC->fau()->org()->migration()->createTables(true);
    }


    public function syncOrgTable()
    {
        global $DIC;
        $service = $DIC->fau()->sync()->org();
        $service->synchronize();
    }


    public function checkOrgUnitRelations()
    {
        global $DIC;
        $service = $DIC->fau()->org();
        $service->checkOrgUnitRelations();
    }


}