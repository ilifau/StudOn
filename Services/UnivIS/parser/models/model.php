<?php
/*==========================================================================*/
/**  Model PHP class.
 *==========================================================================
 *
 *   LME - Starbugs - Typo 3.0 - Univis Module 
 *
 *   @par Copyright
 *   Copyright (c) FAU Erlangen-Nuernberg 2007 <BR>
 *   All Rights Reserved
 *
 *   @file model.php	
 *
 *
 *==========================================================================*/

class Model
{
    var $attributes = array();
    var $parentModelClass;
    var $parentModelKey;
    var $childModelKey;
    var $parentToTable = array();
    var $actualParentModel;
    var $department_id;

	// fim: [univis] remember last attribute name for concatenating
	var $last_name;
	// fim.

    function fitsIntoDOM($curModelClass, $parModelClass)
    {
	if (!$this->parentModelClass)
	{
	    DEBUG("It's a TOP-level model -> modelFits = true.\n");
	    return true;
	}
	if (is_string($this->parentModelClass))
	{
	    DEBUG("parentModelClass is a string\n");
	    if ($this->parentModelClass == $curModelClass)
	    {
		DEBUG("We are currently in a model class with the correct modelClass -> modelFits = true.\n");
		return true;
	    }
	    if ($this->parentModelClass == $parModelClass)
	    {
		DEBUG("We are currently in a model class with the correct parent->modelClass -> modelFits = true.\n");
		return true;
	    }
	}
	if (is_array($this->parentModelClass))
	{
	    DEBUG("parentModelClass is an array\n");
	    if (in_array($curModelClass, $this->parentModelClass))
	    {
		DEBUG("We are currently in a model class with the correct modelClass -> modelFits = true.\n");
		$this->actualParentModel = $curModelClass;
		return true;
	    }
	    if (in_array($parModelClass, $this->parentModelClass))
	    {
		DEBUG("We are currently in a model class with the correct parent->modelClass -> modelFits = true.\n");
		$this->actualParentModel = $parModelClass;
		return true;
	    }
	}
	return false;
    }

    function setAttribute($name, $value)
    {
		$name = Model::normalizeAttributeName($name);
		if (false !== $name)
		{
		    // fim: [univis] don't decode, value is already utf8 with entities decoded
			/*
		    if (is_string($value))
		    {
				$value = utf8_decode($value);
		    }
		    $value = mysql_escape_string($value);
			*/
			// fim.

			// fim: [univis] use different recognition for quotes
		    if ($name == $this->last_name)
		    {
				// mind the . (dot)!
				$this->attributes[$name] .= $value;
			}
			 else
			{
				$this->attributes[$name] = $value;
		    }
			$this->last_name = $name;
			//fim.

		    return true;
		}
		return false;
    }

    function getAttribute($name)
    {
		$name = Model::normalizeAttributeName($name);
		if (false !== $name)
		{
		    return $this->attributes[$name];
		}
		return null;
    }

    /* static */ function normalizeAttributeName($name)
    {
		$name = str_replace('-', '_', $name);
		$name = strtolower($name);
		if (isset($this->attributes[$name]))
		{
		    return $name;
		}
		return false;
    }

    function store()
    {
		$table = $this->table; // make a copy so we don't modify the object
		if (!empty($this->parentToTable)
			&& !empty($this->actualParentModel)
			&& !empty($this->parentToTable[$this->actualParentModel]))
		{
		    $table = $this->parentToTable[$this->actualParentModel];
		}
		$this->attributes['department_id'] = $this->department_id;
		$this->attributes['session_id'] = session_id();

		// fim: [univis] chance storing query to requirements of ILIAS
		global $ilDB;

		// use a copy, because quoting is added
		$quoted = array();

		// add session_id to attribute set

		// quote all field names and values
		foreach($this->attributes as $key => $value)
		{
			$quoted[$ilDB->quoteIdentifier($key)] = $ilDB->quote(trim($value),'text');
		}

		// use DELETE and INSERT instead of REPLACE
		//	- REPLACE requires a unique key
		//  - MySQL keys can be maximum 1000 bytes
		//  - ILIAS tables are utf8
		//  - utf8 fields have 3 bytes per character

		require_once ('./Services/UnivIS/classes/class.ilUnivisData.php');
		$primary_key = ilUnivisData::_getPrimaryKey($table, $this->attributes);
		$delete_cond = ilUnivisData::_getPrimaryCondition($table, $primary_key);
		if ($delete_cond)
		{
			$query = 'DELETE FROM '.$ilDB->quoteIdentifier($table). ' WHERE '. $delete_cond;
			$ilDB->manipulate($query);
	    }

		$names =  implode(', ', array_keys($quoted));
		$values = implode(', ', array_values($quoted));

		$query= 'INSERT INTO '.$ilDB->quoteIdentifier($table).' ('.$names.') VALUES ('.$values.')';
		//print $query."\n";

		$ilDB->manipulate($query);
		// fim.
    }
}
?>
