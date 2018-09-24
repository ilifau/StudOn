<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once ('./Services/UnivIS/classes/class.ilUnivisData.php');

/**
*  fim: [uvivis] class for looking up personal data
*/
class ilUnivisDepartment extends ilUnivisData
{
	

	/**
	* Get array with select options for lecture search
	*
	* @return 	array       orgnr => name
	*/
	static function _getOptionsForLectureSearch($a_add_default = false)
	{
	    global $lng, $ilDB;

		$options = array();
		if ($a_add_default)
		{
	    	$options[0] =  $lng->txt('please_select');
		}
		$query = "SELECT * FROM univis_org "
				." WHERE ". parent::_getLookupCondition()
				." ORDER BY ". $ilDB->quoteIdentifier('name');

		$result = $ilDB->query($query);

		while ($row = $ilDB->fetchAssoc($result))
		{
			$key = $row['orgnr'];
			$value = $row['name'];
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
}
?>
