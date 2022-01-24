<?php
/*==========================================================================*/
/**  RoomModel PHP class.
 *==========================================================================
 *
 *   LME - Starbugs - Typo 3.0 - Univis Module 
 *
 *   @par Copyright
 *   Copyright (c) FAU Erlangen-Nuernberg 2007 <BR>
 *   All Rights Reserved
 *
 *   @file roomModel.php	
 *
 *
 *==========================================================================*/


require_once './Services/UnivIS/parser/models/model.php';

class RoomModel extends Model
{
	var $table = 'univis_room';
	var $parentModelClass = 'UnivisModel';
	var $parentModelKey = '';
	var $childModelKey = '';

	var $attributes = array(
				'key' => '',
				'address' => '',
				'description' => '',
				'anst' => '',
				'audio' => '',
				'beam' => '',
				'buildno' => '',
				'dark' => '',
				'dia' => '',
				'fest' => '',
				'id' => '',
				'inet' => '',
				'lose' => '',
				'name' => '',
				'ohead' => '',
				'roomno' => '',
				'short' => '',
				'size' => '',
				'tafel' => '',
				'tel' => '',
				'url' => '',
				'vcr' => ''
			);
}

class ContactsModel extends Model
{
	var $parentModelClass = 'RoomModel';    
	var $parentModelKey = 'key';
	var $childModelKey = 'room_key';

	var $attributes = array();

	function ContactsModel()
	{
	    $this->attributes[$this->childModelKey]='';
	}

	function store(){
	    //echo "nothing to store\n";
	}
}



class ContactModel extends Model
{
	var $table = 'univis_room_contacts';
	var $parentModelClass = 'ContactsModel';    
	var $parentModelKey = 'room_key';
	var $childModelKey = 'room_key';

	var $attributes = array(
				'contact' => '',
				);

	function ContactModel()
	{
	    $this->attributes[$this->childModelKey]='';
	}

}

?>
