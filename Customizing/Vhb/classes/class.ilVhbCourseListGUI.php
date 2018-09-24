<?php

/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once "Services/Object/classes/class.ilObjectListGUI.php";

/**
 * Class ilVhbCourseListGUI
 *
 * @author Fred Neumann <fred.neumann@fau.de>
 * $Id: class.ilVhbCourseListGUI.php 60770 2015-09-18 21:20:46Z fneumann $
 */
class ilVhbCourseListGUI extends ilObjectListGUI
{
	private $vhbsession;

	/**
	* constructor
	*/
	function __construct($a_context = self::CONTEXT_REPOSITORY)
	{
		parent::__construct($a_context);
	}

	/**
	* initialisation
	*/
	function init()
	{
		$this->type = "crs";
		$this->gui_class_name = "ilobjcoursegui";
		$this->icons_enabled = true;
		$this->commands_enabled = false;
		$this->restrict_to_goto = true;
	}

	protected function buildGotoLink()
	{
		$link = "vhblogin.php?course_ref_id=".$this->ref_id."&amp;client_id=".CLIENT_ID;
		if (!empty($this->vhbsession))
		{
			$link .= "&vhbsession=" . $this->vhbsession;
		}
		return $link;
	}

	public function checkCommandAccess($a_permission, $a_cmd, $a_ref_id, $a_type, $a_obj_id = "")
	{
		return true;
	}

	function insertProperties($a_item = '')
	{
		return;
	}

	public function setVhbSession($a_session)
	{
		$this->vhbsession = $a_session;
	}
}
?>
