<?php
/* fim: [memcond] new class. */

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once "./Services/Membership/classes/class.ilSubscribersStudyCond.php";

/**
* Class ilSubscribersStudyCondGUI
*
* @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
* @version $Id: $
*
* @ilCtrl_Calls ilSubscribersStudyCondGUI:
*
* @ingroup ServicesMembership
*/

class ilSubscribersStudyCondGUI
{
	/** @var  string page peadline */
	protected $headline;
	/** @var  string page info */
	protected $info;
	/** @var  bool provide a bacl link */
	protected $with_backlink;

	/**
	* Constructor
	* @access public
	*/
	function __construct(&$a_parent_gui)
	{
		global $ilCtrl, $tpl, $lng;

		$this->ctrl = $ilCtrl;
		$this->tpl = $tpl;
		$this->lng = $lng;
		$this->parent_gui = $a_parent_gui;
		$this->parent_obj_id = $this->parent_gui->object->getId();
		$this->parent_ref_id = $this->parent_gui->object->getRefId();

		$this->headline = $this->lng->txt("studycond_condition_headline");
		$this->info = $this->lng->txt("studycond_condition_combi_info");
		$this->with_backlink = true;
	}


	/**
	* Execute a command (main entry point)
	* @access public
	*/
	public function executeCommand()
	{
		global $ilAccess, $ilErr;

		// access to all functions in this class are only allowed if edit_permission is granted
		if (!$ilAccess->checkAccess("write", "edit", $this->parent_ref_id, "", $this->parent_obj_id))
		{
			$ilErr->raiseError($this->lng->txt("permission_denied"),$ilErr->MESSAGE);
		}

		// NOT NICE
		$this->ctrl->saveParameter($this, "studycond_conditions_table_nav");
		$this->ctrl->saveParameter($this, "cond_id");

		$cmd = $this->ctrl->getCmd("listConditions");
		$this->$cmd();

		return true;
	}

	/**
	 * @return string
	 */
	public function getHeadline()
	{
		return $this->headline;
	}

	/**
	 * @param string $headline
	 */
	public function setHeadline($headline)
	{
		$this->headline = $headline;
	}

	/**
	 * @return string
	 */
	public function getInfo()
	{
		return $this->info;
	}

	/**
	 * @param string $info
	 */
	public function setInfo($info)
	{
		$this->info = $info;
	}

	/**
	 * @return bool
	 */
	public function isWithBacklink()
	{
		return $this->with_backlink;
	}

	/**
	 * @param bool $with_backlink
	 */
	public function setWithBacklink($with_backlink)
	{
		$this->with_backlink = $with_backlink;
	}


	private function show($a_html)
	{
		if ($this->isWithBacklink())
		{
			/** @var ilToolBarGUI $ilToolbar */
			global $ilToolbar;
			$back = ilLinkButton::getInstance();
			$back->setUrl($this->ctrl->getLinkTarget($this, 'back'));
			$back->setCaption('back');
			$ilToolbar->addButtonInstance($back);
		}

		$tpl = new ilTemplate("tpl.list_subscribers_studycond.html", true, true, "Services/Membership");
 		$tpl->setVariable("CONDITIONS_HEADLINE", $this->getHeadline());
 		$tpl->setVariable("CONDITIONS_COMBI_INFO",$this->getInfo());
		$tpl->setVariable("CONDITIONS_CONTENT", $a_html);
		$tpl->parse();
		$this->tpl->setContent($tpl->get());
	}


	/**
	* List the form definitions
	* @access private
	*/
	private function listConditions()
	{
		// build the table of form definitions
		include_once 'Services/Membership/classes/class.ilSubscribersStudyCondTableGUI.php';
		$table_gui = new ilSubscribersStudyCondTableGUI($this, "listConditions", $this->parent_obj_id);
		$this->show($table_gui->getHTML());
	}

	/**
	* Return to the parent GUI
	*/
	private function back()
	{
	  $this->ctrl->returnToParent($this,'studycond');
	}


	/**
	* Show an empty form to create a new condition
	*/
	private function create()
	{
		$this->initForm("create");
		$this->show($this->form_gui->getHtml());
	}

	
	/**
	* Show the form to edit an existing condition
	*/
	private function edit()
	{
		$condition = new ilSubscribersStudyCond((int) $_GET["cond_id"]);
		$condition->read();

		$this->initForm("edit");
		$this->getValues($condition);
		$this->show($this->form_gui->getHtml());
	}
	
	
	/**
	* Save a newly entered condition
	*/
    private function saveCond()
    {
        $this->initForm("create");
        if ($this->form_gui->checkInput())
        {
			$condition = new ilSubscribersStudyCond();
        	$condition->setObjId($this->parent_obj_id);
        	$this->setValues($condition);
        	$condition->create();
        	
            ilUtil::sendInfo($this->lng->txt("studycond_condition_saved"),true);
        	$this->ctrl->redirect($this, 'listConditions');
        }
        else
        {
            $this->form_gui->setValuesByPost();
            $this->show($this->form_gui->getHtml());
        }
    }


	/**
	* Update a changed condition
	*/
    private function updateCond()
    {
		$this->ctrl->saveParameter($this,"cond_id");
		$this->initForm("edit");
		
        if ($this->form_gui->checkInput())
        {
            $condition = new ilSubscribersStudyCond((int) $_GET["cond_id"]);
			$condition->read();
			$this->setValues($condition);
			$condition->update();
			
			ilUtil::sendInfo($this->lng->txt("studycond_condition_updated"),true);
        	$this->ctrl->redirect($this, 'listConditions');
		}
        else
        {
            $this->form_gui->setValuesByPost();
        	$this->show($this->form_gui->getHtml());
    	}
    }
    
    
    /**
	* Delete a condition
	*/
    private function delete()
    {
    	$condition = new ilSubscribersStudyCond((int) $_GET["cond_id"]);
    	
    	if ($condition->delete())
    	{
    		ilUtil::sendInfo($this->lng->txt("studycond_condition_deleted"),true);
    	}
    	
        $this->ctrl->redirect($this, 'listConditions');
   	}
    
    
	/**
	* Get the values of a web form into property gui
	* @param    object  studycond object to read the values from
	*/
	private function getValues($a_condition)
	{
		$form_gui = $this->form_gui;
		
		$form_gui->getItemByPostVar("subject_id")->setValue($a_condition->getSubjectId());
		$form_gui->getItemByPostVar("degree_id")->setValue($a_condition->getDegreeId());
		$form_gui->getItemByPostVar("min_semester")->setValue($a_condition->getMinSemester());
		$form_gui->getItemByPostVar("max_semester")->setValue($a_condition->getMaxSemester());
		$form_gui->getItemByPostVar("ref_semester")->setValue($a_condition->getRefSemester());
	}


	/**
	* Set the values of the property gui into a webform
	* @param    object  studycond object to store the values
	*/
	private function setValues($a_condition)
	{
		$form_gui = $this->form_gui;

		$a_condition->setSubjectId($form_gui->getInput("subject_id"));
		$a_condition->setDegreeId($form_gui->getInput("degree_id"));
		$a_condition->setMinSemester($form_gui->getInput("min_semester"));
		$a_condition->setMaxSemester($form_gui->getInput("max_semester"));
		$a_condition->setRefSemester($form_gui->getInput("ref_semester"));
	}


	/**
	* Initialize the form GUI
	* @param    int     form mode ("create" or "edit")
	*/
	private function initForm($a_mode)
	{
		require_once("Services/StudyData/classes/class.ilStudyData.php");
		require_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$this->form_gui = new ilPropertyFormGUI();
		$this->form_gui->setFormAction($this->ctrl->getFormAction($this));

		// subject
		$item = new ilSelectInputGUI($this->lng->txt("studycond_field_subject"), "subject_id");
		$item->setInfo($this->lng->txt("studycond_field_subject_info"));
		$item->setOptions(ilStudyData::_getSubjectSelectOptions());
		$this->form_gui->addItem($item);

		// degree
		$item = new ilSelectInputGUI($this->lng->txt("studycond_field_degree"), "degree_id");
		$item->setInfo($this->lng->txt("studycond_field_degree_info"));
		$item->setOptions(ilStudyData::_getDegreeSelectOptions());
		$this->form_gui->addItem($item);

		// min semester
		$item = new ilNumberInputGUI($this->lng->txt("studycond_field_min_semester"), "min_semester");
		$item->setInfo($this->lng->txt("studycond_field_min_semester_info"));
		$item->setSize(2);
		$this->form_gui->addItem($item);

		// max semester
		$item = new ilNumberInputGUI($this->lng->txt("studycond_field_max_semester"), "max_semester");
		$item->setInfo($this->lng->txt("studycond_field_max_semester_info"));
		$item->setSize(2);
		$this->form_gui->addItem($item);

		// ref semester
		$item = new ilSelectInputGUI($this->lng->txt("studycond_field_ref_semester"), "ref_semester");
		$item->setInfo($this->lng->txt("studycond_field_ref_semester_info"));
		$item->setOptions(ilStudyData::_getSemesterSelectOptions());
		$this->form_gui->addItem($item);
		

        // save and cancel commands
        if ($a_mode == "create")
        {
 			$this->form_gui->setTitle($this->lng->txt("studycond_add_condition"));
           	$this->form_gui->addCommandButton("saveCond", $this->lng->txt("save"));
            $this->form_gui->addCommandButton("listConditions", $this->lng->txt("cancel"));
        }
        else
        {
  			$this->form_gui->setTitle($this->lng->txt("studycond_edit_condition"));
           $this->form_gui->addCommandButton("updateCond", $this->lng->txt("save"));
            $this->form_gui->addCommandButton("listConditions", $this->lng->txt("cancel"));
        }
	}
}
?>
