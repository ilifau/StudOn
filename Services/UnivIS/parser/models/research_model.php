<?php
/*==========================================================================*/
/**  ResearchModel PHP class.
 *==========================================================================
 *
 *   LME - Starbugs - Typo 3.0 - Univis Module 
 *
 *   @par Copyright
 *   Copyright (c) FAU Erlangen-Nuernberg 2007 <BR>
 *   All Rights Reserved
 *
 *   @file researchModel.php	
 *
 *
 *==========================================================================*/


require_once './Services/UnivIS/parser/models/model.php';

class ResearchModel extends Model
{
	var $table = 'univis_research';
	var $parentModelClass = 'UnivisModel';	
	var $parentModelKey = '';
	var $childModelKey = '';

	var $attributes = array(
				'key' => '',
				'contact' => '', 
				'description' => '',
				'description_en' => '',
				'title' => '',
				'title_en' => '',
				'enddate' => '',
				'keywords' => '',
				'keywords_en' => '',
				'startdate' => '',
				'url' => '',
				'url_en' => '',
				'orgname' => '',
				);
}


class CoworkersModel extends Model
{
	var $parentModelClass = 'ResearchModel';	
	var $parentModelKey = 'key';
	var $childModelKey = 'project_key';

	var $attributes = array();

	function CoworkersModel()
	{
	    $this->attributes[$this->childModelKey]='';
	}

	function store(){
	    //echo "nothing to store\n";
	}

}

class CoworkerModel extends Model
{
	var $table = 'univis_research_coworkers';
	var $parentModelClass = 'CoworkersModel';	
	var $parentModelKey = 'project_key';
	var $childModelKey = 'project_key';

	var $attributes = array(
				'coworker' => '',
				);

	function CoworkerModel()
	{
	    $this->attributes[$this->childModelKey]='';
	}


}

class DirectorsModel extends Model
{
	var $parentModelClass = 'ResearchModel';	
	var $parentModelKey = 'key';
	var $childModelKey = 'project_key';

	var $attributes = array();

	function DirectorsModel()
	{
	    $this->attributes[$this->childModelKey]='';
	}

	function store(){
	    //echo "nothing to store\n";
	}

}

class DirectorModel extends Model
{
	var $table = 'univis_research_directors';
	var $parentModelClass = 'DirectorsModel';	
	var $parentModelKey = 'project_key';
	var $childModelKey = 'project_key';

	var $attributes = array(
				'director' => '',
				);

	function DirectorModel()
	{
	    $this->attributes[$this->childModelKey]='';
	}

}

class ExternalsModel extends Model
{
	var $parentModelClass = 'ResearchModel';	
	var $parentModelKey = 'key';
	var $childModelKey = 'project_key';

	var $attributes = array();

	function ExternalsModel()
	{
	    $this->attributes[$this->childModelKey]='';
	}

	function store(){
	    //echo "nothing to store\n";
	}

}

class ExternalModel extends Model
{
	var $table = 'univis_research_externals';
	var $parentModelClass = 'ExternalsModel';	
	var $parentModelKey = 'project_key';
	var $childModelKey = 'project_key';

	var $attributes = array(
				'name' => '',
				'url' => ''
				);

	function ExternalModel()
	{
	    $this->attributes[$this->childModelKey]='';
	}

}

class PromotersModel extends Model
{
	var $parentModelClass = 'ResearchModel';	
	var $parentModelKey = 'key';
	var $childModelKey = 'project_key';

	var $attributes = array();

	function PromotersModel()
	{
	    $this->attributes[$this->childModelKey]='';
	}

	function store(){
	    //echo "nothing to store\n";
	}

}

class PromoterModel extends Model
{
	var $table = 'univis_research_promoters';
	var $parentModelClass = 'PromotersModel';	
	var $parentModelKey = 'project_key';
	var $childModelKey = 'project_key';

	var $attributes = array(
				'name' => '',
				'url' => ''
				);

	function PromoterModel()
	{
	    $this->attributes[$this->childModelKey]='';
	}

}

class PublicsModel extends Model
{
	var $parentModelClass = 'ResearchModel';	
	var $parentModelKey = 'key';
	var $childModelKey = 'project_key';

	var $attributes = array();

	function PublicsModel()
	{
	    $this->attributes[$this->childModelKey]='';
	}

	function store(){
	    //echo "nothing to store\n";
	}

}

class PublicModel extends Model
{
	var $table = 'univis_research_publics';
	var $parentModelClass = 'PublicsModel';	
	var $parentModelKey = 'project_key';
	var $childModelKey = 'project_key';

	var $attributes = array(
				'public' => '',
				);

	function PublicModel()
	{
	    $this->attributes[$this->childModelKey]='';
	}

}

?>
