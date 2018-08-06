<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once ('./Services/UnivIS/classes/class.ilUnivisData.php');

/**
*  fim: [uvivis] class for looking up lectures data
*/
class ilUnivisRoom extends ilUnivisData
{

    /**
	* Read the data (to be overwritten)
	*
	* @param 	string     string representation of the primary key
	*/
	function read($a_primary_key)
	{
	    global $ilDB;

		$query = "SELECT * FROM univis_room "
				." WHERE ". parent::_getLookupCondition()
				." AND ". parent::_getPrimaryCondition('univis_room', $a_primary_key);

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
	    return self::_getPrimaryKey('univis_room', $this->data);
	}

    /**
	* Get a link for looking up the data in univis
	*/
	function getUnivisLink()
	{
	    return parent::_getPrgUrl().'search=rooms&show=long&id='.$this->data['id'];
	}

    /**
	* Get Display (like displayed for a lecture)
	*
	* @return 	string      display text
	*/
	function getDisplayShort($a_linked = false)
	{
	    if ($a_linked)
		{
	        return sprintf('<a href="%s" target="_blank">%s</a>',
							$this->getUnivisLink(),
							$this->data['short']);
	    }
		else
		{
	    	return $this->data['short'];
		}
	}
}
?>
