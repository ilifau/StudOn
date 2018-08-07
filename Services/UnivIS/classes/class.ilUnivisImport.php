<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* Add here all classes for data lookup
* an inclusion of ilUnivisImport will make them available
*/
require_once ('./Services/UnivIS/classes/class.ilUnivisData.php');

require_once ('./Services/UnivIS/classes/class.ilUnivisLecture.php');
require_once ('./Services/UnivIS/classes/class.ilUnivisPerson.php');
require_once ('./Services/UnivIS/classes/class.ilUnivisDepartment.php');
require_once ('./Services/UnivIS/classes/class.ilUnivisTerm.php');
require_once ('./Services/UnivIS/classes/class.ilUnivisRoom.php');
require_once ('./Services/UnivIS/classes/class.ilUnivisStudy.php');
require_once ('./Services/UnivIS/classes/class.ilUnivisLocation.php');
require_once ('./Services/UnivIS/classes/class.ilUnivisOfficehour.php');

/**
* fim: [univis] base class for univis import
*
* This is the main class to start an import of univis data for ILIAS.
* It calls univis2mysql from the univis partser to perform the import.
*/
class ilUnivisImport
{
   	var $conf = array();

	/**
	* Constructor
	*/
	function __construct()
	{


	    // initialize the interface
		require_once ('./Services/UnivIS/parser/class.univis2mysql.php');
		$this->univis2mysql = new univis2mysql;
		$this->initConf();
	}


	/**
	* Initialize the configuration
	*/
	function initConf()
	{


	    $this->conf = array();
		$this->conf['univis']['server'] = ilCust::get('univis_server');
		$this->conf['univis']['port'] = ilCust::get('univis_port');
		$this->conf['univis']['prg_url'] = ilCust::get('univis_prg_url');
		$this->conf['univis']['tempdir'] = ilCust::get('univis_tempdir');
		$this->conf['univis']['noimports'] = ilCust::get('univis_noimports');

		// first and last semester for query (if null: running semester and following)
		// array('year' => (integer), 'sem' => (string))
		if ($semester = ilCust::get('univis_semester'))
		{
			$this->conf['univis']['first_semester'] = ilUnivisLecture::_getSemesterFromString($semester);
			$this->conf['univis']['last_semester'] = ilUnivisLecture::_getSemesterFromString($semester);
		}
		else
		{
			$this->conf['univis']['first_semester'] = null;
			$this->conf['univis']['last_semester'] = null;		
		}

		// what data to import from UnivIS
		// the module names as used as search parameters
		// initially all nothing is imported
		$this->conf['univis']['modules'] = array(
				'lectures' => false,
				'persons' => false,
				'departments' => false,
				'rooms' => false,
				'thesis' => false,
				'publications' => false,
				'projects' => false
			);
	}


	/**
	* get an error message for the last import
	*
	* @return   string error message
	*/
	function getErrorMessage()
	{
	    global $lng;

		switch ($this->univis2mysql->getError())
		{
			case univis2mysql::ERROR_NONE:
				return $lng->txt('univis_error_none');

			case univis2mysql::ERROR_CONNECT:
				return $lng->txt('univis_error_connect');

			case univis2mysql::ERROR_TRANSFER:
				return $lng->txt('univis_error_transfer');

			case univis2mysql::ERROR_VALIDATE:
				return $lng->txt('univis_error_validate');

			default:
				return $lng->txt('univis_error_unknown');
		}
	}

	/**
	* Cleanup previously imported lecture data
	*/
	function cleanupLectures()
	{
		self::_deleteBySession('univis_lecture');
		self::_deleteBySession('univis_lecture_courses');
		self::_deleteBySession('univis_lecture_stud');
		self::_deleteBySession('univis_lecture_term');
		self::_deleteBySession('univis_lecture_dozs');
	}


	/**
	* Count the imported lectures
	*/
	function countLectures()
	{
		return self::_countSessionImport('univis_lecture');
	}


	/**
	* Cleanup previously imported department data
	*/
	function cleanupDepartments()
	{
		self::_deleteBySession('univis_org');
	}


	/**
	* Cleanup previously imported personal data
	*/
	function cleanupPersons()
	{
		self::_deleteBySession('univis_org');
		self::_deleteBySession('univis_person');
		self::_deleteBySession('univis_person_location');
		self::_deleteBySession('univis_person_officehour');
		self::_deleteBySession('univis_person_jobs');
	}


	/**
	* Import univs data of lectures
	*
	* @param    string     	search pattern for the lecture title
	* @param    string     	search pattern for the lecturer
	* @param    integer    	id of the department
	* @return   integer     number of imported lectures or false
	*/
	function importLectures($a_name = '', $a_lecturer = '', $a_department = 0)
	{
		// firs cleanup lectures data
		$this->cleanupLectures();

		// then import the new data
		$this->initConf();
		$this->conf['univis']['name'] = $a_name;
		$this->conf['univis']['lecturer'] = $a_lecturer;
		$this->conf['univis']['department'] = $a_department;
		$this->conf['univis']['modules'] = array('lectures' => true);

		return $this->univis2mysql->updateFromUnivis($this->conf) ?
                self::_countSessionImport('univis_lecture') : false;
	}


	/**
	* Import  a single lecture idenfied by an import id
	*
	* @param    string     	ilias import id
	* @return   boolean     import successful (true/false)
	*/
	function importLecture($a_ilias_import_id)
	{
		$parts = ilUnivisLecture::_splitIliasImportId($a_ilias_import_id);

		// then import the new data
		$this->initConf();
		$this->conf['univis']['id'] = $parts['id'];
		$this->conf['univis']['first_semester'] = $parts['semester'];
		$this->conf['univis']['last_semester'] = $parts['semester'];
		$this->conf['univis']['modules'] = array('lectures' => true);

		return $this->univis2mysql->updateFromUnivis($this->conf);
	}


	/**
	* Import the basic data of departments
	*
	* @param    string     search pattern for department name
	* @param    integer    id of the department
	*/
	function importDepartments($a_name = '', $a_department = 0)
	{
	    // first delete all current data
		$this->cleanupDepartments();

		// then import the new data
		$this->initConf();
		$this->conf['univis']['name'] = $a_name;
		$this->conf['univis']['department'] = $a_department;
		$this->conf['univis']['modules'] = array('departments' => true);

		return $this->univis2mysql->updateFromUnivis($this->conf) ?
                self::_countSessionImport('univis_org') : false;
	}


	/**
	* Import the basic data of a department
	*
	* @param    string     search pattern for fullname
	* @param    integer    id of the department
	*/
	function importPersons($a_fullname = '', $a_department = 0)
	{
	    // first delete all current data
		$this->cleanupPersons();

		// then import the new data
		$this->initConf();
		$this->conf['univis']['fullname'] = $a_fullname;
		$this->conf['univis']['department'] = $a_department;
		$this->conf['univis']['modules'] = array('persons' => true);

		return $this->univis2mysql->updateFromUnivis($this->conf) ?
                self::_countSessionImport('univis_person') : false;
	}


	/**
	* Import all univs data of a department
	*
	* @param    integer     ID of the department
	* @return   boolean     import successful (true/false)
	*/
	function importAllByDepartment($a_department_id)
	{
	    // first deleta all department related data
		self::_deleteByDepartment($a_department_id);

		// then import the new data for the department
		$this->initConf();
		$this->conf['univis']['department'] = $a_department_id;

		return $this->univis2mysql->updateFromUnivis($this->conf);
	}


	/**
	* Count all lectures that are imported in the current session
	*/
	static function _countSessionImport($a_table)
	{
		global $ilDB;
        $query = "SELECT COUNT(*) count_imported FROM " . $ilDB->quoteIdentifier($a_table)
				." WHERE session_id = " . $ilDB->quote(session_id(),'text');

		$result = $ilDB->query($query);

		if ($row = $ilDB->fetchAssoc($result))
		{
	        return $row['count_imported'];
	    }
		else
		{
	        return 0;
	    }
	}


	/**
	* Delete all univis data for a specific department
	*
	* @param    integer     ID of the department
	*/
	static function _deleteByDepartment($a_department_id)
	{
		global $ilDB;

	    foreach (ilUnivisData::_getTableNames() as $table)
		{
	        $query = "DELETE FROM " . $ilDB->quoteIdentifier($table)
					." WHERE department_id = ". $ilDB->quote($a_department_id, 'integer');

			$ilDB->manipulate($query);
		}
	}


	/**
	* Delete all univis data from current session or inactive sessions
	*
	* (univis tables are only used as staging tables within a session)
	*
	*/
	static function _deleteBySession($a_table = '')
	{
		global $ilDB;

		$tables = $a_table ? array($a_table) : ilUnivisData::_getTableNames();

	    foreach ($tables as $table)
		{
	        $query = "DELETE FROM " . $ilDB->quoteIdentifier($table)
					." WHERE session_id = " . $ilDB->quote(session_id(),'text')
					." OR session_id NOT IN (SELECT session_id FROM usr_session)";

			$ilDB->manipulate($query);
		}
	}


	/**
	* Delete all univis data from a specific user session
	*
	* @param    string     ILIAS session id
	*/
	static function _deleteAll()
	{
		global $ilDB;

	    foreach (ilUnivisData::_getTableNames() as $table)
		{
	        $query = "DELETE FROM " . $ilDB->quoteIdentifier($table);

			$ilDB->manipulate($query);
		}
	}
}
?>
