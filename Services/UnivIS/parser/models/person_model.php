<?php
/*==========================================================================*/
/**  PersonModel PHP class.
 *==========================================================================
 *
 *   LME - Starbugs - Typo 3.0 - Univis Module 
 *
 *   @par Copyright
 *   Copyright (c) FAU Erlangen-Nuernberg 2007 <BR>
 *   All Rights Reserved
 *
 *   @file personModel.php	
 *
 *
 *==========================================================================*/


require_once './Services/UnivIS/parser/models/model.php';

class PersonModel extends Model
{
	var $table = 'univis_person';
	var $parentModelClass = 'UnivisModel';
	var $parentModelKey = '';
	var $childModelKey = '';

	var $attributes = array(
			'key' => '',
			'type' => '',
			'group' => '',
			'atitle' => '',
			'firstname' => '',
			'from' => '',
			'id' => '',
			'lastname' => '',
			'lehr' => '',
			'lehraufg' => '',
			'lehrtyp' => '',
			'pgroup' => '',
			'shortname' => '',
			'title' => '',
			'univis_key' => '',
			'until' => '',
			'visible' => '',
			'work' => '',
			'zweitmgl' => '',
			'alumni' => '',
			'chef' => '',
			'founder' => '',
			'name' => '',
			'current' => '',
			'gender' => '',
			// fim: [univis] added fields
			'orgname' => ''
			// fim.
			);
}

class LocationsModel extends Model
{
	var $parentModelClass = 'PersonModel';
	var $parentModelKey = 'key';
	var $childModelKey = 'person_key';

	var $attributes = array();
	function LocationsModel()
	{
	    $this->attributes[$this->childModelKey]='';
	}

	function store(){
	    //echo "nothing to store\n";
	}
}

class LocationModel extends Model
{
	var $table = 'univis_person_location';
	var $parentModelClass = 'LocationsModel';
	var $parentModelKey = 'person_key';
	var $childModelKey = 'person_key';

	var $attributes = array(
			'email' => '',
			'fax' => '',
			'mobile' => '',
			'office' => '',
			'ort' => '',
			'street' => '',
			'tel' => '',
			'url' => '',
			);

	function LocationModel()
	{
	    $this->attributes[$this->childModelKey]='';
	}

}

class OfficehoursModel extends Model
{
	var $parentModelClass = 'PersonModel';
	var $parentModelKey = 'key';
	var $childModelKey = 'person_key';

	var $attributes = array();
	function OfficehoursModel()
	{
	    $this->attributes[$this->childModelKey]='';
	}

	function store(){
	    //echo "nothing to store\n";
	}
}

class OfficehourModel extends Model
{
	var $table = 'univis_person_officehour';
	var $parentModelClass = 'OfficehoursModel';
	var $parentModelKey = 'person_key';
	var $childModelKey = 'person_key';

	var $attributes = array(
				'endtime' => '',
				'office' => '',
				'repeat' => '',
				'starttime' => '',
				'comment' => ''
				);

	function OfficehourModel()
	{
	    $this->attributes[$this->childModelKey]='';
	}

}

?>
