<?php
/*==========================================================================*/
/**  ThesisModel PHP class.
 *==========================================================================
 *
 *   LME - Starbugs - Typo 3.0 - Univis Module 
 *
 *   @par Copyright
 *   Copyright (c) FAU Erlangen-Nuernberg 2007 <BR>
 *   All Rights Reserved
 *
 *   @file thesisModel.php	
 *
 *
 *==========================================================================*/


require_once './Services/UnivIS/parser/models/model.php';

class ThesisModel extends Model
{
	var $table = 'univis_thesis';
	var $parentModelClass = 'UnivisModel';
	var $parentModelKey = '';
	var $childModelKey = '';

	var $attributes = array(
			'key' => '',
			'title' => '',
			'notice' => '',
			'topic' => '',
			'finishdate' => '',
			'finishyear' => '',
			'firstname' => '',
			'keywords' => '',
			'lastname' => '',
			'prerequisit' => '',
			'public' => '',
			'registerdate' => '',
			'reservedate' => '',
			'short' => '',
			'status' => '',
			'type' => '',
			'url' => '',
			'visible' => '',
			);
}

class AdvisorsModel extends Model
{
	var $parentModelClass = 'ThesisModel';    
	var $parentModelKey = 'key';
	var $childModelKey = 'thesis_key';

	var $attributes = array();

	function AdvisorsModel()
	{
	    $this->attributes[$this->childModelKey]='';
	}

	function store(){
	    //echo "nothing to store\n";
	}
}

class AdvisorModel extends Model
{
	var $table = 'univis_thesis_advisors';
	var $parentModelClass = 'AdvisorsModel';    
	var $parentModelKey = 'thesis_key';
	var $childModelKey = 'thesis_key';

	var $attributes = array(
				'advisor' => '',
				);

	function AdvisorModel()
	{
	    $this->attributes[$this->childModelKey]='';
	}

}

?>
