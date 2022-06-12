<?php
/**
 * fau: fauService - patch to create the tables
 */
class ilFauPatches
{
	public function createFauTables()
	{
        global $DIC;
        $DIC->fau()->org()->migration()->createTables(true);
        $DIC->fau()->study()->migration()->createTables(true);
        $DIC->fau()->user()->migration()->createTables(true);
	}
}