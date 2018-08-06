<?php
/*==========================================================================*/
/**  OrgModel PHP class.
 *==========================================================================
 *
 *   LME - Starbugs - Typo 3.0 - Univis Module 
 *
 *   @par Copyright
 *   Copyright (c) FAU Erlangen-Nuernberg 2007 <BR>
 *   All Rights Reserved
 *
 *   @file orgModel.php	
 *
 *
 *==========================================================================*/


require_once './Services/UnivIS/parser/models/model.php';

class OrgModel extends Model
{
	var $table = 'univis_org';
	var $parentModelClass = 'UnivisModel';
	var $parentModelKey = '';
	var $childModelKey = '';

	var $attributes = array(
				'key' => '',
				'b_lehre' => '',
				'b_orgdesc' => '', 
				'b_rescoop' => '',
				'b_resequip' => '',
				'b_resfoc' => '',
				'resconf' => '',
				'resconf_en' => '',
				'rescoop' => '',
				'rescoop_en' => '',
				'resequip' => '',
				'resequip_en' => '',
				'resfoc' => '',
				'resfoc_en' => '',
				'street' => '',
				'ort' => '',
				'tel' => '',
				'fax' => '',
				'email' => '',
				'url' => '',
				'id' => '',
				'orgnr' => '',
				'ordernr' => '',
				'name' => '',
				'name_en' => '',
				'orgdesc' => '',
				'orgdesc_en' => '',
				'pubser' => '',
				'pubser_en' => '',
				);
}
?>
