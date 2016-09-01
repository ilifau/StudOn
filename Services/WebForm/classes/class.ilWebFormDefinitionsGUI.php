<?php
/* fim: [webform] new class. */

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once "./Services/WebForm/classes/class.ilWebForm.php";

/**
* Class ilWebFormDefinitionsGUI
*
* @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
* @version $Id: $
*
* @ilCtrl_Calls ilWebFormDefinitionsGUI: 
*
* @package webform
*/

class ilWebFormDefinitionsGUI
{
	/**
	* Constructor
	* @access public
	*/
	function ilWebFormDefinitionsGUI(&$a_parent_gui)
	{
		global $ilCtrl, $tpl, $lng;

		$this->ctrl =& $ilCtrl;
		$this->tpl =& $tpl;
		$this->lng =& $lng;
		$this->lng->loadLanguageModule("webform");
		$this->parent_gui =& $a_parent_gui;
		$this->parent_obj_id = $this->parent_gui->object->getId();
		$this->parent_ref_id = $this->parent_gui->object->getRefId();
	}

	/**
	* Execute a command (main entry point)
	* @access public
	*/
	public function &executeCommand()
	{
		global $ilAccess, $ilErr;

		// access to all functions in this class are only allowed if edit_permission is granted
		if (!$ilAccess->checkAccess("write", "edit", $this->parent_ref_id, "", $this->parent_obj_id))
		{
			$ilErr->raiseError($this->lng->txt("permission_denied"),$ilErr->MESSAGE);
		}

		// NOT NICE
		$this->ctrl->saveParameter($this, "webform_definitions_table_nav");
		$this->ctrl->saveParameter($this, "form_id");

		$cmd = $this->ctrl->getCmd("listForms");
		$this->$cmd();

		return true;
	}

	/**
	* List the form definitions
	* @access private
	*/
	private function listForms()
	{
		// build the table of form definitions
		include_once 'Services/WebForm/classes/class.ilWebFormDefinitionsTableGUI.php';
		$table_gui = new ilWebFormDefinitionsTableGUI($this, "listForms", $this->parent_gui->object);

		// build the page
		$tpl = new ilTemplate("tpl.list_definitions.html", true, true, "Services/WebForm");
		$tpl->setVariable("ACTION_READ_DEFINITIONS", $this->ctrl->getFormAction($this));
		$tpl->setVariable("CMD_READ_DEFINITIONS", "readForms");
		$tpl->setVariable("TXT_READ_DEFINITIONS", $this->lng->txt("webform_read_definitions"));
		$tpl->setVariable("CMD_CREATE_DEFINITION", "create");
		$tpl->setVariable("TXT_CREATE_DEFINITION", $this->lng->txt("webform_create_definition"));
  		$tpl->setVariable("DEFINITIONS_TABLE", $table_gui->getHTML());
  		$tpl->parse();

		$this->tpl->setContent($tpl->get());
	}


	/**
	* Show an empty form to create a new definition
	*/
	private function create()
	{
		$this->initForm("create");
		$this->tpl->setContent($this->form_gui->getHtml());
	}

	
	/**
	* Show the form to edit an existing definition
	*/
	private function edit()
	{
		$webform = new ilWebForm((int) $_GET["form_id"]);
		$webform->read();

		$this->initForm("edit");
		$this->getValues($webform);
		$this->tpl->setContent($this->form_gui->getHtml());
	}
	
	
	/**
	* Save a newly entered definition
	*/
    private function save()
    {
        $this->initForm("create");
        if ($this->form_gui->checkInput())
        {
			$webform = new ilWebForm();
        	$webform->setLmObjId($this->parent_obj_id);
        	$this->setValues($webform);
        	$webform->create();
        	
            ilUtil::sendInfo($this->lng->txt("webform_definition_saved"),true);
            $this->ctrl->redirect($this);
            
        }
        else
        {
            $this->form_gui->setValuesByPost();
            $this->tpl->setContent($this->form_gui->getHtml());
        }
    }

	/**
	* Update a changed definition
	*/
    private function update()
    {
		$this->ctrl->saveParameter($this,"form_id");
		$this->initForm("edit");
		
        if ($this->form_gui->checkInput())
        {
            $webform = new ilWebForm((int) $_GET["form_id"]);
			$webform->read();
			$this->setValues($webform);
			$webform->update();
			
			ilUtil::sendInfo($this->lng->txt("webform_definition_updated"),true);
            $this->ctrl->redirect($this);
        }
        else
        {
            $this->form_gui->setValuesByPost();
        	$this->tpl->setContent($this->form_gui->getHtml());
    	}
    }
    
    /**
	* Delete a form definition
	*/
    private function delete()
    {
    	$webform = new ilWebForm((int) $_GET["form_id"]);
    	
    	if ($webform->delete())
    	{
    		ilUtil::sendInfo($this->lng->txt("webform_definition_deleted"),true);
    	}
    	else
    	{
    		ilUtil::sendInfo($this->lng->txt("webform_definition_delete_failed"),true);
    	}
    	
           $this->ctrl->redirect($this);
    }
    
	/**
	* Get the values of a web form into property gui
	* @param    object  webform object to read the values from
	*/
	private function getValues($a_webform)
	{
		$form_gui = $this->form_gui;
		
		$form_gui->getItemByPostVar("title")->setValue($a_webform->getTitle());
		$form_gui->getItemByPostVar("form_name")->setValue($a_webform->getFormName());
		$form_gui->getItemByPostVar("dataset_id")->setValue($a_webform->getDatasetId());
		$form_gui->getItemByPostVar("path")->setValue($a_webform->getPath());
		
		$item = $form_gui->getItemByPostVar("send_maxdate");
		if ($send_maxdate = $a_webform->getSendMaxdate())
		{
			$item->setDate(new ilDateTime($send_maxdate, IL_CAL_DATETIME));
			$item->enableDateActivation($this->lng->txt("active"),"send_maxdate_active", true);
		}
		$form_gui->getItemByPostVar("solution_ref")->setValue($a_webform->getSolutionRef());
		$form_gui->getItemByPostVar("solution_mode")->setValue($a_webform->getSolutionMode());

		if ($solution_date = $a_webform->getSolutionDate())
		{
			$item = $form_gui->getItemByPostVar("solution_date");
			$item->setDate(new ilDateTime($solution_date, IL_CAL_DATETIME));
		}
		$form_gui->getItemByPostVar("forum")->setValue($a_webform->getForum());
		$form_gui->getItemByPostVar("forum_parent")->setValue($a_webform->getForumParent());
		$form_gui->getItemByPostVar("forum_subject")->setValue($a_webform->getForumSubject());
	}

	/**
	* Set the values of the property gui into a webform
	* @param    object  webform object to store the values
	*/
	private function setValues($a_webform)
	{
		$form_gui = $this->form_gui;

		$a_webform->setTitle($form_gui->getInput("title"));
		$a_webform->setFormName($form_gui->getInput("form_name"));
		$a_webform->setDatasetId($form_gui->getInput("dataset_id"));
		$a_webform->setPath($form_gui->getInput("path"));
		if ($form_gui->getInput("send_maxdate_active"))
		{
			$date = $form_gui->getItemByPostVar("send_maxdate")->getDate();
			$a_webform->setSendMaxdate($date->get(IL_CAL_DATETIME));
		}
		else
		{
			$a_webform->setSendMaxdate(null);
		}
		$a_webform->setSolutionRef($form_gui->getInput("solution_ref"));
		$a_webform->setSolutionMode($form_gui->getInput("solution_mode"));
		if ($a_webform->getSolutionMode() == "date" )
		{
			$date = $form_gui->getItemByPostVar("solution_date")->getDate();
			$a_webform->setSolutionDate($date->get(IL_CAL_DATETIME));
		}
		else
		{
			$a_webform->setSendMaxdate(null);
		}
		$a_webform->setForum($form_gui->getInput("forum"));
		$a_webform->setForumParent($form_gui->getInput("forum_parent"));
		$a_webform->setForumSubject($form_gui->getInput("forum_subject"));
	}

	/**
	* Initialize the form GUI
	* @param    int     form mode ("create" or "edit")
	*/
	private function initForm($a_mode)
	{
		include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$this->form_gui = new ilPropertyFormGUI();
		$this->form_gui->setFormAction($this->ctrl->getFormAction($this));
		$this->form_gui->setTitle($this->lng->txt("webform_definition"));

		// title
		$item = new ilTextInputGUI($this->lng->txt("webform_field_title"), "title");
		$item->setInfo($this->lng->txt("webform_field_title_info"));
		$item->setMaxLength(255);
		$item->setRequired(true);
		$this->form_gui->addItem($item);
		
		// form name
		$item = new ilTextInputGUI($this->lng->txt("webform_field_form_name"), "form_name");
		$item->setInfo($this->lng->txt("webform_field_form_name_info"));
		$item->setRequired(true);
		$this->form_gui->addItem($item);

		// dataset id
		$item = new ilTextInputGUI($this->lng->txt("webform_field_dataset_id"), "dataset_id");
		$item->setInfo($this->lng->txt("webform_field_dataset_id_info"));
		$item->setMaxLength(255);
		$this->form_gui->addItem($item);

		// path
		$item = new ilTextInputGUI($this->lng->txt("webform_field_path"), "path");
		$item->setInfo($this->lng->txt("webform_field_path_info"));
		$item->setMaxLength(255);
		$item->setRequired(true);
		$this->form_gui->addItem($item);

		// send_maxdate
		$item = new ilDateTimeInputGUI($this->lng->txt("webform_field_send_maxdate"), "send_maxdate");
		$item->setInfo($this->lng->txt("webform_field_send_maxdate_info"));
		$item->setShowTime(true);
		$item->enableDateActivation($this->lng->txt("active"),"send_maxdate_active", false);
		$this->form_gui->addItem($item);

		// solution ref
		$item = new ilTextInputGUI($this->lng->txt("webform_field_solution_ref"), "solution_ref");
		$item->setInfo($this->lng->txt("webform_field_solution_ref_info"));
		$item->setMaxLength(255);
		$this->form_gui->addItem($item);

		// solution mode
		$item = new ilSelectInputGUI($this->lng->txt("webform_field_solution_mode"), "solution_mode");
		$item->setInfo($this->lng->txt("webform_field_solution_mode_info"));
		$item->setOptions(array(	"send" => $this->lng->txt("webform_solution_mode_send"),
		                            "checked" => $this->lng->txt("webform_solution_mode_checked"),
		                            "date" => $this->lng->txt("webform_solution_mode_date")));
		$this->form_gui->addItem($item);

		// solution date
		$item = new ilDateTimeInputGUI($this->lng->txt("webform_field_solution_date"), "solution_date");
		$item->setInfo($this->lng->txt("webform_field_solution_date_info"));
		$item->setShowTime(true);
		$this->form_gui->addItem($item);

		// forum
		$item = new ilTextInputGUI($this->lng->txt("webform_field_forum"), "forum");
		$item->setInfo($this->lng->txt("webform_field_forum_info"));
		$item->setMaxLength(255);
		$this->form_gui->addItem($item);

		// forum parent
		$item = new ilTextInputGUI($this->lng->txt("webform_field_forum_parent"), "forum_parent");
		$item->setInfo($this->lng->txt("webform_field_forum_parent_info"));
		$item->setMaxLength(255);
		$this->form_gui->addItem($item);

		// forum subject
		$item = new ilTextInputGUI($this->lng->txt("webform_field_forum_subject"), "forum_subject");
		$item->setInfo($this->lng->txt("webform_field_forum_subject_info"));
		$item->setMaxLength(255);
		$this->form_gui->addItem($item);

        // save and cancel commands
        if ($a_mode == "create")
        {
            $this->form_gui->addCommandButton("save", $this->lng->txt("save"));
            $this->form_gui->addCommandButton("listForms", $this->lng->txt("cancel"));
        }
        else
        {
            $this->form_gui->addCommandButton("update", $this->lng->txt("save"));
            $this->form_gui->addCommandButton("listForms", $this->lng->txt("cancel"));
            $this->form_gui->addCommandButton("delete", $this->lng->txt("delete"));
        }
	}
	
	
	/**
	* Read the form definitions from a definitions file
	* @access public
	*/
	private function readForms()
	{
		require_once("./Services/WebForm/classes/class.ilWebFormAccess.php");
		$file = ilWebFormAccess::_findXMLImportFile($this->parent_gui->object);
		if (is_file($file))
        {
        	$names = implode("<br />", ilWebForm::_importXML($this->parent_obj_id, $file));
            ilUtil::sendInfo(sprintf($this->lng->txt("webform_definitions_read_finished"), $names),true);
            $this->ctrl->redirect($this);
        }
        else
        {
        	ilUtil::sendInfo(sprintf($this->lng->txt("webform_definitions_not_found"), $local_path),true);
        	$this->ctrl->redirect($this);
        }
	}
}
?>
