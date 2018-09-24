<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once ('./Services/UnivIS/classes/class.ilUnivisData.php');

/**
*  fim: [uvivis] class for looking up lectures data
*/
class ilUnivisStudy extends ilUnivisData
{

    /**
	* Read the data (to be overwritten)
	*
	* @param 	string     string representation of the primary key
	*/
	function read($a_primary_key)
	{
	    global $ilDB;

		$query = "SELECT * FROM univis_lecture_stud "
				." WHERE ". parent::_getLookupCondition()
				." AND ". parent::_getPrimaryCondition('univis_lecture_stud', $a_primary_key);

		$result = $ilDB->query($query);
		if ($row = $ilDB->fetchAssoc($result))
		{
	        $this->data = $row;
		}
		else
		{
	        $this->data = array();
		}
	}


    /**
	* Get the primary key
	* The primary key should be extracted from the data
	*
	* @return 	string     string representation of the primary key
	*/
	function getPrimaryKey()
	{
	    return parent::_getPrimaryKey('univis_lecture_stud', $this->data);
	}


    /**
	* Get Display (like displayed for a lecture)
	*
	* @return 	string      display text
	*/
	function getDisplay()
	{
	    global $lng;

	    $info[] = $this->data['pflicht'];
		$info[] = $this->data['richt'];

		$sem = $this->data['sem'];
		if (!is_numeric($sem))
		{
			$info[] = $lng->txt('univis_semester_from') . ' ' . substr($sem,0,1);
	    }
		else
		{
	        $info[] = implode(', ', str_split($sem));
	    }

		if ($this->data['credits'])
		{
	        $info[] = '(' . $lng->txt('univis_ects_credits') . ' '. $this->data['credits'] .')';
	    }

		return implode(' ', $info);
	}


    /**
	* Get all studuesof a lecture
	*
	* @param 	string      lecture key
	* @param 	string      semester
	* @return   array       list of study objects
	*/
	static function _getStudiesOfLecture($a_lecture_key, $a_semester)
	{
	    global $ilDB;

		$terms = array();

		$query = "SELECT * FROM univis_lecture_stud "
				." WHERE ". parent::_getLookupCondition()
				." AND lecture_key = " . $ilDB->quote($a_lecture_key, 'text')
				." AND semester = " . $ilDB->quote($a_semester, 'text')
				." ORDER BY orderindex";

		$result = $ilDB->query($query);
		while ($row = $ilDB->fetchAssoc($result))
		{
	        $term = new ilUnivisStudy();
			$term->setData($row);
			$terms[] = $term;
		}

	    return $terms;
	}
}
?>
