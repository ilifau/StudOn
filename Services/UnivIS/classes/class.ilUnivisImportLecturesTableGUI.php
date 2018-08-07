<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once("Services/Table/classes/class.ilTable2GUI.php");
include_once 'Services/Tree/classes/class.ilPathGUI.php';

/**
* fim: [univis] table to show lectures for creating courses
*
* @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
* @version $Id: $
*/
class ilUnivisImportLecturesTableGUI extends ilTable2GUI
{
	/**
	* List of selected lecture_ids
	*/
	var $selected_ids = array();

	/**
	* Wizard mode
	*/
	var $wizard_mode = '';


	/**
	* Selection mode
	*/
	var $select_mode = 'multiple';

    /**
    * Constructor
    * @param    object  parent gui
    * @param    string  command of parent gui to show the table
	* @param    int   	course or group object id
    */
	function __construct($a_parent_obj, $a_parent_cmd)
    {
        global $ilCtrl, $lng;

        parent::__construct($a_parent_obj, $a_parent_cmd);

		$this->pathGUI = new ilPathGUI();
		$this->pathGUI->setUseImages(false);
		$this->pathGUI->enableTextOnly(false);
        $this->pathGUI->enableHideLeaf(false);

        $this->setPrefix("univis_lectures");
    	$this->setRowTemplate("tpl.univis_lectures_row.html", "Services/UnivIS");

	 	$this->addColumn($this->lng->txt('select'),'checked_ids','1', true);
	 	$this->addColumn($this->lng->txt('title'),'name','75%');
	 	$this->addColumn($this->lng->txt('univis_lecturer'),'','15%');
	 	$this->addColumn($this->lng->txt('univis_semester'),'','10%');

       	$this->setDefaultOrderField('name');
        $this->setDefaultOrderDirection('asc');
        $this->setData(ilUnivisLecture::_getLecturesData());
    }

   	/**
    * Set the parent object id
	*
	* @param    integer     id
    */
	function setParentObjId($a_obj_id)
	{
	    $this->parent_obj_id = $a_obj_id;
	}

   	/**
    * Set the wizard mode
	*
	* @param    string  mode
    */
	function setWizardMode($a_mode)
	{
	    $this->wizard_mode = $a_mode;
	}


    /**
    * Set the select mode
	*
	* @param    string  mode ('checkbox' or 'radio')
    */
	function setSelectMode($a_mode)
	{
		$this->select_mode = $a_mode;

		if ($a_mode == 'multiple')
		{
			$this->setSelectAllCheckbox('checked_ids');
	    }
	}


    /**
    * Set the information about selected ids
    */
	function setSelectedIds($a_ids = array())
	{
		$this->selected_ids = $a_ids;
	}

    /**
    * Get a link to send a mail to the owner
    */
	function getOwnerLink($a_obj_id)
	{
		if ($owner = ilObject::_lookupOwner($a_obj_id))
		{
	        $link = '<a target="_blank" href="ilias.php?baseClass=ilMailGUI&type=new&rcp_to='
				.urlencode(ilObjUser::_lookupLogin($owner)).'">'. ilObjUser::_lookupFullname($owner) .'</a>';
	    }
		return $link;
	}

    /**
    * Get a link to send a mail to the owner
    */
	function getPath($a_ref_id)
	{


		$start_id = ilCust::get('ilias_repository_cat_id');
		$start_id = $start_id ? $start_id : ROOT_FOLDER_ID;

		return $this->pathGUI->getPath($start_id, $a_ref_id);
	}


    /**
    * Fill a single data row
    */
    protected function fillRow($a_set)
    {
        global $ilCtrl, $ilAccess, $lng;

		$lecture = new ilUnivisLecture();
		$lecture->setData($a_set);
		$lecture_id = $lecture->getPrimaryKey();
		$import_id = $lecture->getIliasImportId();

		// init additional settings
        $show_select = false;
		$show_path = false;
		$message = '';

		// check for existing course (should only have one ref_id)
		if (!$obj_id = ilObject::_lookupObjIdByImportId($import_id))
		{
			$show_select = true;
			$show_path = false;
			$message = '';
		}
	    elseif (!ilObject::_hasUntrashedReference($obj_id))
		{
			$show_select = true;
			$show_path = false;
			$message = '';
		}
		else
		{
	        // course should only have one ref_id
			$ref_id = current(ilObject::_getAllReferences($obj_id));

			if ($obj_id == $this->parent_obj_id)
			{
	            // course is current course
				switch ($this->wizard_mode)
				{
 					case 'course_data_import':
		              	$show_select = true;
						$show_path = false;
						$message = $lng->txt('univis_lecture_message_course_parent');
						break;
				}
			}
	        elseif ($ilAccess->checkAccess('write', '', $ref_id, 'crs', $obj_id))
			{
	            // course can be changed
				switch ($this->wizard_mode)
				{
                    case 'category_course_import':
					case 'course_data_import':
		                $show_select = false;
						$show_path = true;
						$message = sprintf($lng->txt('univis_lecture_message_course_exists'), $this->getOwnerLink($obj_id));
						break;
					case 'category_course_update':
		                $show_select = true;
						$show_path = true;
						$message = '';
						break;
				}
           	}
			elseif ($ilAccess->checkAccess('visible', '', $ref_id, 'crs', $obj_id))
			{
	            // course is visible but can't be changes
				switch ($this->wizard_mode)
				{
                    case 'category_course_import':
					case 'course_data_import':
		                $show_select = false;
						$show_path = true;
						$message = sprintf($lng->txt('univis_lecture_message_course_exists'), $this->getOwnerLink($obj_id));
						break;
					case 'category_course_update':
		                $show_select = false;
						$show_path = true;
						$message = sprintf($lng->txt('univis_lecture_message_course_blocked'), $this->getOwnerLink($obj_id));
						break;
				}
			}
			else
			{
	            // course is not visible
				switch ($this->wizard_mode)
				{
                    case 'category_course_import':
					case 'category_course_update':
					case 'course_data_import':
		                $show_select = false;
						$show_path = false;
						$message = sprintf($lng->txt('univis_lecture_message_course_hidden'), $this->getOwnerLink($obj_id));
						break;
				}
            }
		}

		// show checkbox or radio button
		if ($show_select and in_array($this->select_mode, array('multiple','single')))
		{
			$this->tpl->setCurrentBlock('select_'. $this->select_mode);
	  		$this->tpl->setVariable("POSTNAME_CHECKED", 'checked_ids');
	  		$this->tpl->setVariable("POSTNAME_HIDDEN", 'hidden_ids');
			$this->tpl->setVariable("LECTURE_ID", $lecture_id);
			if ($this->selected_ids[$lecture_id])
			{
				$this->tpl->setVariable("CHECKED", 'checked="checked"');
		    }
			$this->tpl->parseCurrentBlock();
		}

		// Basic lecture data
  		$this->tpl->setVariable("TXT_TITLE", $lecture->getDisplayTitle(true, true));
		if ($txt = $lecture->getDisplayInfoShort(true))
		{
	        $this->tpl->setCurrentBlock("info");
			$this->tpl->setVariable("TXT_INFO",$txt);
			$this->tpl->parseCurrentBlock();
		}
		if ($txt = $lecture->getDisplayTerms(true))
		{
	        $this->tpl->setCurrentBlock("terms");
			$this->tpl->setVariable("TXT_TERMS",$txt);
			$this->tpl->parseCurrentBlock();
		}

		// info about the ilias course
		if ($show_path)
		{
	        $this->tpl->setCurrentBlock("path");
			$this->tpl->setVariable("TXT_PATH", $lng->txt('univis_course_path'));
			$this->tpl->setVariable("PATH", $this->getPath($ref_id));
			$this->tpl->parseCurrentBlock();
		}
		if ($message)
		{
	        $this->tpl->setCurrentBlock("message");
			$this->tpl->setVariable("TXT_MESSAGE",$message);
			$this->tpl->parseCurrentBlock();
		}
		
		// lecture id
	    $this->tpl->setCurrentBlock("univis_id");
		$this->tpl->setVariable("TXT_UNIVIS_ID",$lng->txt('univis_id'));
		$this->tpl->setVariable("VAL_UNIVIS_ID",$import_id);
		$this->tpl->parseCurrentBlock();
		
		// other columns
		$this->tpl->setVariable("LECTURERS", $lecture->getDisplayLecturersShort(true));
		$this->tpl->setVariable("SEMESTER", $lecture->getDisplaySemester());
   }
}
?>
