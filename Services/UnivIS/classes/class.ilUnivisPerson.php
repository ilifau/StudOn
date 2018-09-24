<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once ('./Services/UnivIS/classes/class.ilUnivisData.php');

/**
*  fim: [uvivis] class for looking up personal data
*/
class ilUnivisPerson extends ilUnivisData
{
    /**
	* Read the data (to be overwritten)
	*
	* @param 	string     string representation of the primary key
	*/
	function read($a_primary_key)
	{
	    global $ilDB;

		$query = "SELECT * FROM univis_person "
				." WHERE ". parent::_getLookupCondition()
				." AND ". parent::_getPrimaryCondition('univis_person', $a_primary_key);

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
	    return parent::_getPrimaryKey('univis_person', $this->data);
	}

    /**
	* Get a link for looking up the data in univis
	*/
	function getUnivisLink()
	{
	    return parent::_getPrgUrl().'search=persons&show=info&id='.$this->data['id'];
	}

    /**
	* Get the locations
	*/
	function getLocations()
	{
	    $locations = ilUnivisLocation::_getLocationsOfPerson($this->data['key']);
		return $locations;
	}

    /**
	* Get the officehours
	*/
	function getOfficehours()
	{
	    $locations = ilUnivisOfficehour::_getOfficehoursOfPerson($this->data['key']);
		return $locations;
	}

    /**
	* Get display (like displayed in lectures)
	*
	* @return 	string      display text
	*/
	function getDisplay($a_linked = false)
	{
		$parts = array();
		$parts[] = trim($this->data['title']);
		$parts[] = trim($this->data['firstname']);
		$parts[] = trim($this->data['lastname']);
		$parts[] = trim($this->data['atitle']);
		$name = implode(' ',$parts);


	    if ($a_linked)
		{
	        return sprintf('<a href="%s" target="_blank">%s</a>',
							$this->getUnivisLink(),
							$name);
	    }
		else
		{
	    	return $name;
		}
	}


    /**
	* Get short display (like displayed in lectures list)
	*
	* @return 	string      display text
	*/
	function getDisplayShort($a_linked = false)
	{
	    $name = $this->data['lastname'];

		$firstnames = explode(" ", $this->data['firstname']);
		foreach ($firstnames as $firstname)
		{
			$name .= ' '. substr($firstname,0,1) . '.';
	    }


	    if ($a_linked)
		{
	        return sprintf('<a href="%s" target="_blank">%s</a>',
							$this->getUnivisLink(),
							$name);
	    }
		else
		{
	    	return $name;
		}
	}


	/**
	* Get array with select options for lecture search
	*
	* @param    boolean     add default entry for selection
	* @return 	array       lecturer search pattern => name
	*/
	static function _getOptionsForLectureSearch($a_add_default = false)
	{
	    global $lng, $ilDB;

		$options = array();
		if ($a_add_default)
		{
	    	$options[''] =  $lng->txt('please_select');
		}

		$query = "SELECT * FROM univis_person "
				." WHERE ". parent::_getLookupCondition()
				." ORDER BY ". $ilDB->quoteIdentifier('lastname')
				." , ". $ilDB->quoteIdentifier('firstname');
		$result = $ilDB->query($query);

		// first collect all data to count multiple names
		$data = array();
		$names = array();
		while ($row = $ilDB->fetchAssoc($result))
		{
			$row['searchname'] = $row['lastname']. ', ' . $row['firstname'];
	        $data[] = $row;
			$names[$row['searchname']]++;
		}

		// then build the search options
		foreach ($data as $row)
		{
	        // the key is the serch pattern for lecturer
            // use orgname only if name is not unique (some orgnames don't work)
			if ($names[$row['searchname']] > 1)
			{
				$key = $row['orgname'] .'/'. $row['searchname'];
	        }
			else
			{
				$key = $row['searchname'];
	        }

	        // the value is shown in the selection list
  			$value = $row['lastname']. ', ' . $row['firstname'] .'; '. $row['orgname'];
			if (strlen($value) > 80)
			{
	            $value = substr($value,0, 77).'...';
	        }

	        $options[$key] = $value;
			$found = true;
	    }

		if ($found)
		{
	        return $options;
		}
		else
		{
	        return array();
	    }
	}

   	/**
	* Get all lecturers of a lecture
	* @return 	array      person objects
	*/
	static function _getLecturersOfLecture($a_lecture_key, $a_semester)
	{
	    global $ilDB;

		$query = "SELECT * FROM univis_lecture_dozs "
				." WHERE ". parent::_getLookupCondition()
				." AND lecture_key=". $ilDB->quote($a_lecture_key, 'text')
				." AND semester=". $ilDB->quote($a_semester, 'text')
				." ORDER BY orderindex";
		$result = $ilDB->query($query);

		$lecturers = array();
		while ($row = $ilDB->fetchAssoc($result))
		{
			$lecturers[] = new ilUnivisPerson($row['doz']);
		}
		return $lecturers;
	}
}
?>
