<?php
/*==========================================================================*/
/**  UnivisModel PHP class.
 *==========================================================================
 *
 *   LME - Starbugs - Typo 3.0 - Univis Module 
 *
 *   @par Copyright
 *   Copyright (c) FAU Erlangen-Nuernberg 2007 <BR>
 *   All Rights Reserved
 *
 *   @file UnivisModel.php	
 *
 *
 *==========================================================================*/


require_once './Services/UnivIS/parser/models/model.php';

class UnivisModel extends Model
{
	var $attributes = array();
	var $parentModelClass = '';
	function store()
	{
	    //echo "nothing to store\n";
	}
}
?>
