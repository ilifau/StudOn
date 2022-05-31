<?php
/**
 * fau: fauService - patch to create the tables
 */
class ilFauPatches
{
	public function createFauTables()
	{
        global $DIC;
        $DIC->fau()->study()->migration()->createTables();
        $DIC->fau()->user()->migration()->createTables();
	}

}