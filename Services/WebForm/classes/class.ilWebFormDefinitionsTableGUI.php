<?php
/* fim: [webform] new class. */

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */


include_once 'Services/Table/classes/class.ilTable2GUI.php';

/**
* Class ilWebFormDefinitionsTableGUI
*
* @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
* @version $Id: $
*
* @package webform
*/

class ilWebFormDefinitionsTableGUI extends ilTable2GUI
{
	var $lm_obj = null;
	
    /**
    * Constructor
    * @param    object  parent gui
    * @param    object  commant of parent gui to show the table
	* @param    object  learning module object related to parent gui
    */
	function __construct($a_parent_obj, $a_parent_cmd, $a_lm_obj)
    {
        global $ilCtrl, $lng;

        parent::__construct($a_parent_obj, $a_parent_cmd);

		$this->lm_obj = $a_lm_obj;

        $this->addColumn($lng->txt("webform_field_title"), "title", "30%");
        $this->addColumn($lng->txt("webform_field_form_name"), "form_name", "20%");
        $this->addColumn($lng->txt("webform_field_dataset_id"), "dataset_id", "20%");
        $this->addColumn($lng->txt("webform_field_path"), "path", "30%");

        $this->setEnableHeader(true);
        $this->setEnableTitle(false);
        $this->setFormAction($ilCtrl->getFormAction($a_parent_obj));
        $this->setRowTemplate("tpl.list_definitions_row.html", "Services/WebForm");
        $this->setDefaultOrderField("title");
        $this->setDefaultOrderDirection("asc");
        $this->setPrefix("webform_definitions");
		$this->readData();
    }

    /**
    * Get the form definitions of the learning module
    */
    private function readData()
    {
		include_once "./Services/WebForm/classes/class.ilWebForm.php";
		
		$data = ilWebForm::_getFormDataOfModule($this->lm_obj->getId());
        $this->setData($data);
	}

    /**
    * Fill a single data row
    */
    protected function fillRow($a_set)
    {
        global $ilCtrl;

		$ilCtrl->setParameter($this->getParentObject(),"form_id",$a_set["form_id"]);
		$this->tpl->setVariable("TITLE", $a_set["title"]);
		$this->tpl->setVariable("LINK_EDIT", $ilCtrl->getLinkTarget($this->getParentObject(),"edit"));
		$this->tpl->setVariable("NAME", $a_set["form_name"]);
		$this->tpl->setVariable("DATASET_ID", $a_set["dataset_id"]);
		$this->tpl->setVariable("PATH", $a_set["path"]);
		$this->tpl->setVariable("LINK_VIEW", $this->lm_obj->getDataDirectory("output")."/".$a_set["path"]);
    }
}
?>
