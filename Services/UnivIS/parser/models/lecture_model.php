<?php
/*==========================================================================*/
/**  LectureModel PHP class.
 *==========================================================================
 *
 *   LME - Starbugs - Typo 3.0 - Univis Module 
 *
 *   @par Copyright
 *   Copyright (c) FAU Erlangen-Nuernberg 2007 <BR>
 *   All Rights Reserved
 *
 *   @file lectureModel.php	
 *
 *
 *==========================================================================*/


require_once './Services/UnivIS/parser/models/model.php';

class TitleModel extends Model
{
	var $table = 'univis_title';
	var $parentModelClass = 'UnivisModel';
	var $parentModelKey = '';
	var $childModelKey = '';

	var $attributes = array(
			'key' => '',
			'ordernr' => '',
			'parent_title' => '',
			'title' => '',
			'title_en' => '',
			'text' => '',
			'text_en' => ''
			);
}

class LectureModel extends Model
{
	var $table = 'univis_lecture';
	var $parentModelClass = 'UnivisModel';
	var $parentModelKey = '';
	var $childModelKey = '';

	var $attributes = array(
			'key' => '',
			'classification' => '',
			'parent_lv' => '',
			'room' => '',
			'semester' => '',
			'organizational' => '',
			'summary' => '',
			'ects_summary' => '',
			'name' => '',
			'ects_name' => '',
			'beginners' => '',
			'benschein' => '',
			'comment' => '',
			'ects' => '',
			'ects_cred' => '',
			'ects_literature' => '',
			'ects_organizational' => '',
			'evaluation' => '',
			'fachstud' => '',
			'fruehstud' => '',
			'gast' => '',
			'id' => '',
			'internat' => '',
			'keywords' => '',
			'literature' => '',
			'ordernr' => '',
			'regsystem' => '',
			'regqueue' => '',
			'regstart' => '',
			'regstarttime' => '',
			'regend' => '',
			'regendtime' => '',
			'regwlist' => '',
			'schein' => '',
			'short' => '',
			'startdate' => '',
			'sws' => '',
			'time_description' => '',
			'turnout' => '',
			'maxturnout' => '',
			'type' => '',
			'url_description' => '',
			'orgname' => '',
			'bonus' => '',
			'malus' => '',
			'mag' => '',
			'dipl' => '',
			'mast' => '',
			'bac' => '',
			'laew' => '',
			'lafv' => '',
			'lafn' => '',
			'lafb' => '',
			'ladidg' => '',
			'ladidh' => '',
			'ladidf' => '',
			'schluessel' => '',
			'senior' => '',
			'women' => '',
			'allfak' => '',
			'einf' => '',
			'schwerp' => '',
			'medabschn1' => '',
			'medabschn2' => '',
			'praktjahr' => ''
			);

	function LectureModel()
	{
	    if (empty($GLOBALS['semester']))
	    {
		DEBUG("LectureModel(): Semester nicht gesetzt!\n");
		return false;
	    }
	    $this->attributes['semester'] = $GLOBALS['semester'];
	}
}

class CoursesModel extends Model
{
	var $parentModelClass = 'LectureModel';    
	var $parentModelKey = 'key';
	var $childModelKey = 'lecture_key';

	var $attributes = array();

	function CoursesModel()
	{
	    $this->attributes[$this->childModelKey]='';
	}

	function store(){
	    //echo "nothing to store\n";
	}
}

class CourseModel extends Model
{
	var $table = 'univis_lecture_courses';
	var $parentModelClass = 'CoursesModel';    
	var $parentModelKey = 'lecture_key';
	var $childModelKey = 'lecture_key';

	var $attributes = array(
				'course' => '',
				'semester' => '',
				);

	function CourseModel()
	{
	    $this->attributes[$this->childModelKey]='';
	    if (empty($GLOBALS['semester']))
	    {
		DEBUG("LectureModel(): Semester nicht gesetzt!\n");
		return false;
	    }
	    $this->attributes['semester'] = $GLOBALS['semester'];

	}

}

class DozsModel extends Model
{
	var $parentModelClass = 'LectureModel';    
	var $parentModelKey = 'key';
	var $childModelKey = 'lecture_key';

	var $attributes = array();

	function DozsModel()
	{
	    $this->attributes[$this->childModelKey]='';
	}

	function store(){
	    //echo "nothing to store\n";
	}
}

class DozModel extends Model
{
	var $table = 'univis_lecture_dozs';
	var $parentModelClass = 'DozsModel';    
	var $parentModelKey = 'lecture_key';
	var $childModelKey = 'lecture_key';

	var $attributes = array(
				'doz' => '',
				'semester' => '',
				'orderindex' => ''
				);
				
	static $orderindex = 0;			

	function DozModel()
	{
	    $this->attributes[$this->childModelKey]='';
	    if (empty($GLOBALS['semester']))
	    {
		DEBUG("LectureModel(): Semester nicht gesetzt!\n");
		return false;
	    }
	    $this->attributes['semester'] = $GLOBALS['semester'];
	    
	    $this->attributes['orderindex'] = self::$orderindex++;
	}

}

class StudsModel extends Model
{
	var $parentModelClass = 'LectureModel';    
	var $parentModelKey = 'key';
	var $childModelKey = 'lecture_key';

	var $attributes = array();

	function StudsModel()
	{
	    $this->attributes[$this->childModelKey]='';
	}

	function store(){
	    //echo "nothing to store\n";
	}
}

class StudModel extends Model
{
	var $table = 'univis_lecture_stud';
	var $parentModelClass = 'StudsModel';    
	var $parentModelKey = 'lecture_key';
	var $childModelKey = 'lecture_key';

	var $attributes = array(
				'pflicht' => '',
				'richt' => '',
				'sem' => '',
				'semester' => '',
				'credits' => ''
				);

	function StudModel()
	{
	    $this->attributes[$this->childModelKey]='';
	    if (empty($GLOBALS['semester']))
	    {
		DEBUG("LectureModel(): Semester nicht gesetzt!\n");
		return false;
	    }
	    $this->attributes['semester'] = $GLOBALS['semester'];

	}

}

class TermsModel extends Model
{
	var $parentModelClass = 'LectureModel';    
	var $parentModelKey = 'key';
	var $childModelKey = 'lecture_key';

	var $attributes = array();

	function TermsModel()
	{
	    $this->attributes[$this->childModelKey]='';
	}

	function store(){
	    //echo "nothing to store\n";
	}
}

class TermModel extends Model
{
	var $table = 'univis_lecture_term';
	var $parentModelClass = 'TermsModel';    
	var $parentModelKey = 'lecture_key';
	var $childModelKey = 'lecture_key';

	var $attributes = array(
				'room' => '',
				'startdate' => '',
				'starttime' => '',
				'enddate' => '',
				'endtime' => '',
				'exclude' => '',
				'semester' => '',
				'repeat' => ''
				);

	function TermModel()
	{
	    $this->attributes[$this->childModelKey]='';
	    
	    if (empty($GLOBALS['semester']))
	    {
		DEBUG("LectureModel(): Semester nicht gesetzt!\n");
		return false;
	    }
	    $this->attributes['semester'] = $GLOBALS['semester'];

	}

}


?>
