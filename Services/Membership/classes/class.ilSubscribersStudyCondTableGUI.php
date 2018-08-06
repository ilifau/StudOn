<?php
/* fim: [memcond] new class. */

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */


include_once 'Services/Table/classes/class.ilTable2GUI.php';

/**
* Class ilSubscribersStudyCondTableGUI
*
* @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
* @version $Id: $
*
* @package webform
*/

class ilSubscribersStudyCondTableGUI extends ilTable2GUI
{
	var $obj_id;
	
    /**
    * Constructor
    * @param    object  parent gui
    * @param    string  command of parent gui to show the table
	* @param    int   	course or group object id
    */
	function __construct($a_parent_obj, $a_parent_cmd, $a_obj_id)
    {
        global $ilCtrl, $lng;

        parent::__construct($a_parent_obj, $a_parent_cmd);

		$this->obj_id = $a_obj_id;

        $this->addColumn($lng->txt("studycond_field_subject"), "subject", "20%");
        $this->addColumn($lng->txt("studycond_field_degree"), "degree", "20%");
        $this->addColumn($lng->txt("studycond_field_min_semester"), "min_semester", "15%");
        $this->addColumn($lng->txt("studycond_field_max_semester"), "max_semester", "15%");
        $this->addColumn($lng->txt("studycond_field_ref_semester"), "ref_semester", "15%");
        $this->addColumn($lng->txt("functions"), "", "15%");

        $this->setEnableHeader(true);
        $this->setEnableTitle(false);
        $this->setFormAction($ilCtrl->getFormAction($a_parent_obj));
        $this->setRowTemplate("tpl.list_subscribers_studycond_row.html", "Services/Membership");
        $this->setDefaultOrderField("title");
        $this->setDefaultOrderDirection("asc");
        $this->setPrefix("studycond_conditions");
        $this->addCommandButton("create", $lng->txt("studycond_add_condition"));
		$this->readData();
    }

    /**
    * Get the form definitions of the learning module
    */
    private function readData()
    {
		include_once "./Services/Membership/classes/class.ilSubscribersStudyCond.php";
		include_once "./Services/StudyData/classes/class.ilStudyData.php";

		$data = array();
		foreach (ilSubscribersStudyCond::_getConditionsData($this->obj_id) as $row)
		{
			$row['subject'] = ilStudyData::_lookupSubject($row['subject_id']);
			$row['degree'] = ilStudyData::_lookupDegree($row['degree_id']);
			$data[] = $row;
		}
        $this->setData($data);
	}

    /**
    * Fill a single data row
    */
    protected function fillRow($a_set)
    {
        global $ilCtrl, $lng;
        
		$ilCtrl->setParameter($this->getParentObject(),"cond_id", $a_set["cond_id"]);
		$this->tpl->setVariable("LINK_EDIT", $ilCtrl->getLinkTarget($this->getParentObject(),"edit"));
		$this->tpl->setVariable("LINK_DELETE", $ilCtrl->getLinkTarget($this->getParentObject(),"delete"));
  		$this->tpl->setVariable("SUBJECT", $a_set["subject"]);
		$this->tpl->setVariable("DEGREE", $a_set["degree"]);
		if ($a_set["min_semester"])
		{
			$this->tpl->setVariable("MIN_SEMESTER", $a_set["min_semester"]);
		}
		if ($a_set["max_semester"])
		{
			$this->tpl->setVariable("MAX_SEMESTER", $a_set["max_semester"]);
		}
		if ($a_set["min_semester"] or $a_set["max_semester"])
		{
       		include_once "./Services/StudyData/classes/class.ilStudyData.php";
			$this->tpl->setVariable("REF_SEMESTER", ilStudyData::_getRefSemesterText($a_set["ref_semester"]));
		}
		
		$this->tpl->setVariable("TXT_EDIT", $lng->txt('edit'));
		$this->tpl->setVariable("TXT_DELETE", $lng->txt('delete'));
   }
}
?>
