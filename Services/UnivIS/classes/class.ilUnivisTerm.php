<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once ('./Services/UnivIS/classes/class.ilUnivisData.php');

/**
*  fim: [uvivis] class for looking up lectures data
*/
class ilUnivisTerm extends ilUnivisData
{

	static $dayvar_short = array(
			1 => 'Mo_short',
			2 => 'Tu_short',
			3 => 'We_short',
			4 => 'Th_short',
			5 => 'Fr_short',
			6 => 'Sa_short',
			0 => 'Su_short');

	static $dayvar_long = array(
			1 => 'Mo_long',
			2 => 'Tu_long',
			3 => 'We_long',
			4 => 'Th_long',
			5 => 'Fr_long',
			6 => 'Sa_long',
			0 => 'Su_long');


    /**
	* Read the data (to be overwritten)
	*
	* @param 	string     string representation of the primary key
	*/
	function read($a_primary_key)
	{
	    global $ilDB;

		$query = "SELECT * FROM univis_lecture_term "
				." WHERE ". parent::_getLookupCondition()
				." AND ". parent::_getPrimaryCondition('univis_lecture_term', $a_primary_key);

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
	    return parent::_getPrimaryKey('univis_lecture_term', $this->data);
	}


    /**
	* Get Display (like displayed for a lecture)
	*
	* @return 	string      display text
	*/
	function getDisplay($a_linked = false)
	{
	    global $lng;

	    $rep = explode(' ', $this->data['repeat']);
		$days = explode(',', $rep[1]);

		// repeating
		switch ($rep[0])
		{
	        case 'bd':
				$info = $lng->txt('univis_term_bd') . ' ';
				$show_enddate = true;
				$show_days = true;
				break;

	        case 'd1':
				$info = $lng->txt('univis_term_d1') . ' ';
				$show_enddate = true;
				$show_days = true;
				break;

			case 's1':
				$info = $lng->txt('univis_term_s1'). ' ';
				$show_enddate = false;
				$show_days = false;
				break;

			case 'w1':
				$show_enddate = true;
				$show_days = true;
				break;

			case 'w2':
				$info = $lng->txt('univis_term_w2') . ' ';
				$show_enddate = true;
				$show_days = true;
				break;
		}

		// dates
		if ($this->data['startdate'] and $this->data['enddate']
		and $this->data['startdate'] != $this->data['enddate']
		and $show_enddate)
		{
			$info.= date('d.m.Y',strtotime($this->data['startdate'])).'-'
				  . date('d.m.Y',strtotime($this->data['enddate'])).' ';
		}
		elseif ($this->data['startdate'])
		{
			$info.= date('d.m.Y',strtotime($this->data['startdate'])).' ';
		}

		// weekdays
		if ($show_days and count($days))
		{
	        foreach ($days as $day)
			{
	            $info .= $lng->txt(self::$dayvar_short[$day]). ', ';
	        }
		}

		// times
		if ($this->data['starttime'] and $this->data['endtime'])
		{
	        $info .= $this->data['starttime']. '-' . $this->data['endtime'] . ' ';
	    }

		// room
		if ($this->data['room'])
		{
	        $room = new ilUnivisRoom($this->data['room']);
			$info.= ', '. $room->getDisplayShort($a_linked);
	    }

		return $info;
	}


    /**
	* Get all terms of a lecture
	*
	* @param 	string      lecture key
	* @param 	string      semester
	* @return   array       list of term objects
	*/
	function _getTermsOfLecture($a_lecture_key, $a_semester)
	{
	    global $ilDB;

		$terms = array();

		$query = "SELECT * FROM univis_lecture_term "
				." WHERE ". parent::_getLookupCondition()
				." AND lecture_key = " . $ilDB->quote($a_lecture_key, 'text')
				." AND semester = " . $ilDB->quote($a_semester, 'text')
				." ORDER BY orderindex";

		$result = $ilDB->query($query);
		while ($row = $ilDB->fetchAssoc($result))
		{
	        $term = new ilUnivisTerm();
			$term->setData($row);
			$terms[] = $term;
		}

	    return $terms;
	}
}
?>
