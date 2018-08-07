<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
*  fim: [univis] base class for looking up univis data
*
*  Notes: 
*  The univis tables should only be read.
*  The lookup conditions are statically set and affect all child classes
*/
class ilUnivisData
{
	/**
	*  Lookup only data imported for a certain department
	*/
	static $lookup_department = 0;

	/**
	*  Lookup only data imported in the current user's session
	*/
	static $lookup_by_session = true;

	/**
	* delimiter used for string representation of primary key fields
	*/
	static $primary_key_delimiter = '#:#';

	/**
	* primary keys defined for the univis tables
	*/
	static $primary_keys = array
	(
		'univis_person' => 					array('key'),
		'univis_person_location' =>     	array('person_key', 'ort', 'street', 'office'),
		'univis_person_officehour' =>   	array('person_key', 'starttime', 'repeat'),
		'univis_research' =>    			array('key'),
		'univis_research_promoters' =>		array('project_key', 'name'),
		'univis_research_externals' =>		array('project_key', 'name'),
		'univis_research_publics' =>		array('project_key', 'public'),
		'univis_research_directors' =>		array('project_key', 'director'),
		'univis_research_coworkers' =>		array('project_key', 'coworker'),
		'univis_title' =>					array('key'),
		'univis_room' =>     				array('key'),
		'univis_room_contacts' =>     		array('contact', 'room_key'),
		'univis_lecture' =>     			array('key', 'semester'),
		'univis_lecture_courses' =>     	array('lecture_key', 'semester', 'course'),
		'univis_lecture_stud' =>     		array('lecture_key', 'semester', 'richt', 'pflicht', 'sem'),
		'univis_lecture_term' =>     		array('lecture_key', 'semester', 'repeat', 'startdate', 'starttime', 'room'),
		'univis_lecture_dozs' =>     		array('lecture_key', 'doz', 'semester'),
		'univis_thesis' =>     				array('key'),
		'univis_thesis_advisors' =>     	array('thesis_key', 'advisor'),
		'univis_pub' =>     				array('key'),
		'univis_pub_editors' =>     		array('pub_key', 'pkey'),
		'univis_pub_authors' =>     		array('pub_key', 'pkey'),
		'univis_job' =>     				array('description'),
		'univis_person_jobs' =>     		array('per', 'job_key'),
		'univis_org' =>     				array('key')
 	);


	/**
	*  data array
	*/
	protected $data = array();


    /**
	* Constructor
	*
	* @param 	string     string representation of the primary key
	*/
	function __construct($a_primary_key = '')
	{
		if ($a_primary_key)
		{
			$this->read($a_primary_key);
	    }
	}

    /**
	* Set the data array
	*/
	function setData($a_row)
	{
	    $this->data = $a_row;
	}

	/**
	 * Get the data array
	 */
	function getData()
	{
		return $this->data;
	}

	//////////////////////////////////////
	// Mimimum functions to be implemented
	//////////////////////////////////////


    /**
	* Read the data (to be overwritten)
	*
	* @param 	string     string representation of the primary key
	*/
	function read($a_primary_key)
	{
	}

    /**
	* Get the primary key (to be overwritten)
	*
	* The primary key should be extracted from the data
	*
	* @return 	string     string representation of the primary key
	*/
	function getPrimaryKey()
	{
	}


	///////////////////////////////////
	// Static functions for data lookup
	///////////////////////////////////


	/**
	* Set if lookups should be done for a specific department id
	*
	* @param    integer     department_id
	*/
	static function _setLookupDepartment($a_department)
	{
	    self::$lookup_department = $a_department;
	}


	/**
	* Set if lookups should be done for the current import session
	*
	* @param    boolean    lookup by session
	*/
	static function _setLookupBySession($a_lookup)
	{
	    self::$lookup_by_session = $a_lookup;
	}


	/**
	* Get the additional lookup condition for univis tables
	*
	* @return    string    SQL condition
	*/
	static function _getLookupCondition()
	{
	    global $ilDB;

		$cond = array();

		if (self::$lookup_department)
		{
	        $cond[] = 'department_id = '. $ilDB->quote(self::$lookup_department, 'integer');
	    }

		if (self::$lookup_by_session)
		{
	        $cond[] = 'session_id = ' . $ilDB->quote(session_id(),'text');
	    }

		if (count($cond))
		{
			return ' (' . implode(' AND ', $cond) . ') ';
	    }
		else
		{
	        return ' TRUE ';
	    }
	}


	/**
	* Get the names of all univis import tables
	*
	* @return   array     all names of univis tables
	*/
	static function _getTableNames()
	{
	    return array_keys(self::$primary_keys);
	}


	/**
	* Get the primary key of a data row as a string representation
	*
	* @param    string   	table name
	* @param    array   	data row
	* @return   string  	primary key
	*/
	static function _getPrimaryKey($a_table, $a_row)
	{
		$fields = self::$primary_keys[$a_table];
		if (!is_array($fields))
		{
	        return '';
	    }
		else
		{
			$key = array();
			foreach ($fields as $fieldname)
			{
		        $key[] = $a_row[$fieldname];
		    }
			return implode(self::$primary_key_delimiter, $key);
		}
	}

	/**
	* Build the sql condition based on a primary key
	*
	* @param    string   	primary key
	* @return   string  	sql condition
	*/
	static function _getPrimaryCondition($a_table, $a_primary_key)
	{
	    global $ilDB;

	    $key = explode(self::$primary_key_delimiter, $a_primary_key);
		$fields = self::$primary_keys[$a_table];
		if (!is_array($fields))
		{
	        return '';
	    }
		elseif (count($fields) != count($key))
		{
	        return '';
	    }
		else
		{
	        $cond = array();
			$i = 0;
			foreach ($fields as $fieldname)
			{
		        $cond[] = $ilDB->quoteIdentifier($fieldname) 
						. "=" . $ilDB->quote($key[$i++],'text');
		    }
		}

		return '('. implode(' AND ', $cond) . ')';
	}


	/**
	* get the url of the univis prg interface
	*
	* @return   string  	url with trailing "/?"
	*/
	static function _getPrgUrl()
	{

		return ilCust::get('univis_prg_url');
	}


	/**
	 * Convert the UnivIS text formats to HTML
	 * 
	 * @param 	string	Text with formats
	 * @return 	strung	HTML text
	 */
	static function _convertText($text)
	{
		// PERL regular expressions
		// http://www.php.net/manual/en/function.preg-replace.php
		// http://de.selfhtml.org/perl/sprache/regexpr.htm

		// replace two or more empty lines by double break
		$text = preg_replace('/\n\n[\n]+/',"\n<br /><br />\n", $text);
		
		// replace one empty line by single break
		$text = preg_replace('/\n[\n]+/',"\n<br />\n", $text);
		
		// replace listings 
		$text = preg_replace('/^\s*-\s+(.*)$/m', '<li>$1</li>', $text);		// line with "- " at the beginning
		$text = str_replace("</li>\n<li>", "---next-item---", $text);		// mask sequence
		$text = str_replace("<li>", "<ul>\n<li>", $text);					// <ul> before first item in sequence
		$text = str_replace("</li>", "</li>\n</ul>", $text);				// </ul> after last item in sequence
		$text = str_replace("---next-item---", "</li>\n<li>", $text);		// unmask sequence
		
		// mask style characters
		$text = str_replace('**','---masked-asterisk---', $text);
		$text = str_replace('||','---masked-line---', $text);
		$text = str_replace('^^','---masked-up---', $text);
		$text = str_replace('__','---masked-under---', $text);

		// apply specific styles
		$text = preg_replace('/\*(.*)\*/', '<b>$1</b>', $text);
		$text = preg_replace('/\|(.*)\|/', '<i>$1</i>', $text);
		$text = preg_replace('/\^(.*)\^/', '<sup>$1</sup>', $text);
		$text = preg_replace('/\_(.*)\_/', '<sub>$1</sub>', $text);
		
		// unmask style characters
		$text = str_replace('---masked-asterisk---', '*', $text);
		$text = str_replace('---masked-line---', '|', $text);
		$text = str_replace('---masked-up---', '^', $text);
		$text = str_replace('---masked-under---', '_', $text);
		
		// replace links
		$text = preg_replace('/\[(.*)\]\s*(http|https|ftp|mailto):(.*)\s*/','<a href="$2:$3" target="_blank">$1</a>',$text);
		
		return $text;
	}
}
?>
