<?php
/** UnivIS update script
 * Fetches content from UnivIS via PRG interface in XML and stores 
 * it into a mysql database
 */

require_once './Services/UnivIS/parser/modules/generic_module.php';

// Note: It's sometimes hard to distinguish between 
// - the running semester.
//   This term shall refer to the semester which is currently running at the university.
//   E.g. in Mai 2009 it will consist of 2009 and 's'
//   E.g. in December 2009 it will consist of 2009 and 'w'
// - the current semester.
//   This term shall refer to the current state of this module. It's the semester which just got fetched or will be fetched next.
class LecturesModule extends GenericModule
{
	/**
	* @var array  ('year' => integer, 'sem' => string)
	*/
	var $current_semester = null;

	/**
	* @var array  ('year' => integer, 'sem' => string)
	*/
	var $last_semester = null;


	/**
	* constructor
	*
	* @param    string  module name
	* @param    array   first semester ('year' => (integer), 'sem' => (string))
	* @param    array   last semester ('year' => (integer), 'sem' => (string))
	*/
    function __construct($module, $a_first_semester = null, $a_last_semester = null)
    {
		parent::__construct($module);

		// starting semester for fetching univis data
		if (is_array($a_first_semester))
		{
	        $this->current_semester = $a_first_semester;
		}
		else
		{
	        $this->current_semester = $this->getRunningSemester();
	    }

		// last semester for fetching univis data
		if (is_array($a_last_semester))
		{
	        $this->last_semester = $a_last_semester;
		}
		else
		{
	        $this->last_semester = $this->getNextSemester($this->current_semester);
	    }
    }


    /**
	* Get the running semester in real world
	*/
    function getRunningSemester()
	{
		$m = (int) date('m');
		$y = date('Y');
		$term = 's';

		if ($m <= 3)
		{
		    // January till March -> still winter term of the previous year
		    --$y;
		    $term = 'w';
		}
		else if ($m >= 10)
		{
		    // September till Dezember -> winter term of the current year
		    $term = 'w';
		}
		// else -> summer term of the current year

		return array('year' => $y, 'sem' => $term);
    }

    /**
	* 	getNextSemester computes the semester following a given one.
    *	If no reference semester is given it returns the semester following the running one.
	*/
    function getNextSemester($semester = null)
	{
		if (is_null($semester))
		{
		    $semester = LecturesModule::getRunningSemester();
		}

		if ('w' == $semester['sem'])
		{
		    ++$semester['year'];
		    $semester['sem'] = 's';
		}
		else
		{
		    $semester['sem'] = 'w';
		}

		return $semester;
    }


	/**
	* get the url for fetching univis data
	*/
    function getUrl($uConf)
    {
		$semester = $this->getSemesterString();

		return $uConf['prg_url']
			. 'search='.$this->module
			. '&show=xml'
			. '&sem='.$semester
			. ($uConf['id'] ? '&id='.urlencode(utf8_decode($uConf['id'])) : '')
			. ($uConf['name'] ? '&name='.urlencode(utf8_decode($uConf['name'])) : '')
			. ($uConf['lecturer'] ? '&lecturer='.urlencode(utf8_decode($uConf['lecturer'])) : '')
			. ($uConf['department'] ? '&department='.urlencode($uConf['department']) : '')
            . ($uConf['noimports'] ? '&noimports='.urlencode($uConf['noimports']) : '');
    }


	/**
	* get the sting representation of the semester used for the url
	*/
	function getSemesterString()
	{
		return $this->current_semester['year'].$this->current_semester['sem'];
	}


    // hasMoreUrls fulfills two functions.
    // 1) It checks if we want to fetch more XML data (for another semester)
    //    We want to fetch data for previous semesters, the running semester and the one after it.
    // 2) If we want to fetch more, it updates the state of the object.
    function hasMoreUrls()
    {
		// the next semester to fetch
		$next_semester = $this->getNextSemester($this->current_semester);

		// check if nextSemester is later than lastSemester
		if ($next_semester['year'] > $this->last_semester['year']
		    or ($next_semester['year'] == $this->last_semester['year']
				and $next_semester['sem'] == 'w' and $this->last_semester['sem'] == 's'))
		{
		    return false;
		}
		else
		{
			$this->current_semester = $next_semester;
			return true;
		}
	}
}
?>
