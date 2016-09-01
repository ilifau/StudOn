<?php
/**
 * fim: [cust] local patch functions for univis interface.
 */
class ilUnivisPatches
{
	public function createUnivisTables()
	{
		include_once("./Services/UnivIS/parser/sql/class.univistablemanager.php");
		univistablemanager::create_univis_tables();
	}

	public function dropUnivisTables()
	{
		include_once("./Services/UnivIS/parser/sql/class.univistablemanager.php");
		univistablemanager::drop_univis_tables();
	}

	public function testUnivisImport()
	{
		include_once("./Services/UnivIS/classes/class.ilUnivisImport.php");
		$Univis = new ilUnivisImport();

		// Deleta all existing import data
		// $Univis->_deleteAll();

		// Import whole Department (FIM)
		// $Univis->importAllByDepartment(110060);

		// Import Department (FIM)
		// $Univis->importDepartments('FIM');

		// Import Person (Paul Held)
		// $Univis->importPersons('Held');

		// Import Lectures
		// $Univis->importLectures('Lernprozesse');

		// Import Lectures of Paul Held
		// $Univis->importLectures('','110060/Held, Paul');

		// Import Lectures of ILI
		// $Univis->importLectures('','',110060);
	}
}