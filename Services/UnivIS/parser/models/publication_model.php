<?php
/*==========================================================================*/
/**  PublicationsModel PHP class.
 *==========================================================================
 *
 *   LME - Starbugs - Typo 3.0 - Univis Module 
 *
 *   @par Copyright
 *   Copyright (c) FAU Erlangen-Nuernberg 2007 <BR>
 *   All Rights Reserved
 *
 *   @file PublicationsModel.php	
 *
 *
 *==========================================================================*/


require_once './Services/UnivIS/parser/models/model.php';

class PubModel extends Model
{
	var $table = 'univis_pub';
	var $parentModelClass = 'UnivisModel';
	var $parentModelKey = '';
	var $childModelKey = '';

	var $attributes = array(
				'key' => '',
				'pubtitle' => '',
				'address' => '',
				'booktitle' => '',
				'conf_url' => '',
				'conference' => '',
				'edition' => '',
				'hstype' => '',
				'hsyear' => '',
				'id' => '',
				'isbn' => '',
				'issn' => '',
				'journal' => '',
				'keywords' => '',
				'number' => '',
				'pages' => '',
				'plocation' => '',
				'publisher' => '',
				'puburl' => '',
				'school' => '',
				'series' => '',
				'servolume' => '',
				'type' => '',
				'volume' => '',
				'year' => ''
				);
}

class AuthorsModel extends Model
{
	var $parentModelClass = 'PubModel';    
	var $parentModelKey = 'key';
	var $childModelKey = 'pub_key';

	var $attributes = array();

	function AuthorsModel()
	{
	    $this->attributes[$this->childModelKey]='';
	    $GLOBALS['pkeyOrderIdx'] = 0;
	}

	function store(){
	    //echo "nothing to store\n";
	}
}

class AuthorModel extends Model
{
	var $parentModelClass = 'AuthorsModel';    
	var $parentModelKey = 'pub_key';
	var $childModelKey = 'pub_key';

	var $attributes = array();

	function AuthorModel()
	{
	    $this->attributes[$this->childModelKey]='';
	}

	function store(){
	    //echo "nothing to store\n";
	}
}

class PkeyModel extends Model
{
	var $table = 'univis_pub_authors';
	var $parentModelClass = array('AuthorModel', 'EditorModel');
	var $parentToTable = array(
		'AuthorModel' => 'univis_pub_authors',
		'EditorModel' => 'univis_pub_editors',
		);
	var $parentModelKey = 'pub_key';
	var $childModelKey = 'pub_key';

	var $attributes = array(
				'pkey' => '',
				'orderindex' => 0,
				);

	function PkeyModel()
	{
	    $this->attributes[$this->childModelKey]='';
	    $this->attributes['orderindex'] = $GLOBALS['pkeyOrderIdx']++;
	}

}

class EditorsModel extends Model
{
	var $parentModelClass = 'PubModel';    
	var $parentModelKey = 'key';
	var $childModelKey = 'pub_key';

	var $attributes = array();

	function EditorsModel()
	{
	    $this->attributes[$this->childModelKey]='';
	    $GLOBALS['pkeyOrderIdx'] = 0;
	}

	function store(){
	    //echo "nothing to store\n";
	}
}

class EditorModel extends Model
{
	var $parentModelClass = 'EditorsModel';    
	var $parentModelKey = 'pub_key';
	var $childModelKey = 'pub_key';

	var $attributes = array();

	function EditorModel()
	{
	    $this->attributes[$this->childModelKey]='';
	}

	function store(){
	    //echo "nothing to store\n";
	}
}


?>
