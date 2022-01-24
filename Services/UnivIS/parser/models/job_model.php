<?php
/*==========================================================================*/
/**  JobModel PHP class.
 *==========================================================================
 *
 *   LME - Starbugs - Typo 3.0 - Univis Module 
 *
 *   @par Copyright
 *   Copyright (c) FAU Erlangen-Nuernberg 2007 <BR>
 *   All Rights Reserved
 *
 *   @file jobModel.php	
 *
 *
 *==========================================================================*/


require_once './Services/UnivIS/parser/models/model.php';

class JobsModel extends Model
{
	var $parentModelClass = 'OrgModel';    
	var $parentModelKey = 'key';
	var $childModelKey = 'parent_key';

	var $attributes = array();

	function JobsModel()
	{
	    $this->attributes[$this->childModelKey]='';
	}

	function store(){
	    //echo "nothing to store\n";
	}
}



class JobModel extends Model
{
	var $table = 'univis_job';
	var $parentModelClass = 'JobsModel';    
	var $parentModelKey = 'parent_key';
	var $childModelKey = 'parent_key';

	var $attributes = array(
				'description' => '',
				'description_en' => '', 
				'flags' => ''
				);

	function JobModel()
	{
	    $this->attributes[$this->childModelKey]='';
	}
}

class PersModel extends Model
{
	var $parentModelClass = 'JobModel';    
	var $parentModelKey = 'description';
	var $childModelKey = 'job_key';

	var $attributes = array();

	function PersModel()
	{
	    $this->attributes[$this->childModelKey]='';
	}

	function store(){
	    //echo "nothing to store\n";
	}
}



class PerModel extends Model
{
	var $table = 'univis_person_jobs';
	var $parentModelClass = 'PersModel';    
	var $parentModelKey = 'job_key';
	var $childModelKey = 'job_key';

	var $attributes = array(
				'per' => '',
				);

	function PerModel()
	{
	    $this->attributes[$this->childModelKey]='';
	}

}

?>
