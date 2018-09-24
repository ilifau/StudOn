<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once ('./Services/UnivIS/classes/class.ilUnivisData.php');

/**
*  fim: [uvivis] class for looking up location data
*/
class ilUnivisOfficehour extends ilUnivisData
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

		$query = "SELECT * FROM univis_officehour "
				." WHERE ". parent::_getLookupCondition()
				." AND ". parent::_getPrimaryCondition('univis_officehour', $a_primary_key);

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
	    return parent::_getPrimaryKey('univis_officehour', $this->data);
	}

    /**
	* Get the officehour disploay
	*/
	function getDisplay()
	{
		global $lng;

	    $rep = explode(' ', $this->data['repeat']);
		$days = explode(',', $rep[1]);

		// repeating
		switch ($rep[0])
		{
	        case 'd1':
				$info = $lng->txt('univis_term_d1') . ' ';
				$show_days = false;
				break;

			case 'w1':
				$show_days = true;
				break;

			case 'w2':
				$info = $lng->txt('univis_term_w2') . ' ';
				$show_days = true;
				break;
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
		if ($this->data['office'])
		{
			$info.= ', '. $this->data['office'];
	    }

		return $info;
	}


    /**
	* Get all officehours of a person
	*
	* @param 	string      person key
	* @return   array       list of officehour objects
	*/
	static function _getOfficehoursOfPerson($a_person_key)
	{
	    global $ilDB;

		$locations = array();

		$query = "SELECT * FROM univis_person_officehour "
				." WHERE ". parent::_getLookupCondition()
				." AND person_key = " . $ilDB->quote($a_person_key, 'text')
				." ORDER BY orderindex";

		$result = $ilDB->query($query);
		while ($row = $ilDB->fetchAssoc($result))
		{
	        $term = new ilUnivisOfficehour();
			$term->setData($row);
			$terms[] = $term;
		}

	    return $terms;
	}
}
?>
