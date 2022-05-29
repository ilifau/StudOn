<?php
/**
 * fau: fauService - patch to create the tables
 */
class ilFauPatches
{
	public function createFauTables()
	{
        global $DIC;
        $DIC->fau()->campo()->migration()->createCourseOfStudyTable();
        $DIC->fau()->user()->migration()->createTables();
	}

}