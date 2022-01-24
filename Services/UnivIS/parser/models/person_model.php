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
    public $table = 'univis_person';
    public $parentModelClass = 'UnivisModel';
    public $parentModelKey = '';
    public $childModelKey = '';

    public $attributes = array(
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
            // fau: univisImport - added fields
            'orgname' => ''
            // fau.
            );
}

class LocationsModel extends Model
{
    public $parentModelClass = 'PersonModel';
    public $parentModelKey = 'key';
    public $childModelKey = 'person_key';

    public $attributes = array();
    public function LocationsModel()
    {
        $this->attributes[$this->childModelKey] = '';
    }

    public function store()
    {
        //echo "nothing to store\n";
    }
}

class LocationModel extends Model
{
    public $table = 'univis_person_location';
    public $parentModelClass = 'LocationsModel';
    public $parentModelKey = 'person_key';
    public $childModelKey = 'person_key';

    public $attributes = array(
            'email' => '',
            'fax' => '',
            'mobile' => '',
            'office' => '',
            'ort' => '',
            'street' => '',
            'tel' => '',
            'url' => '',
            );

    public function LocationModel()
    {
        $this->attributes[$this->childModelKey] = '';
    }
}

class OfficehoursModel extends Model
{
    public $parentModelClass = 'PersonModel';
    public $parentModelKey = 'key';
    public $childModelKey = 'person_key';

    public $attributes = array();
    public function OfficehoursModel()
    {
        $this->attributes[$this->childModelKey] = '';
    }

    public function store()
    {
        //echo "nothing to store\n";
    }
}

class OfficehourModel extends Model
{
    public $table = 'univis_person_officehour';
    public $parentModelClass = 'OfficehoursModel';
    public $parentModelKey = 'person_key';
    public $childModelKey = 'person_key';

    public $attributes = array(
                'endtime' => '',
                'office' => '',
                'repeat' => '',
                'starttime' => '',
                'comment' => ''
                );

    public function OfficehourModel()
    {
        $this->attributes[$this->childModelKey] = '';
    }
}
