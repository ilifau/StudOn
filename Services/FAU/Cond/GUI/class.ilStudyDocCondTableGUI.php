<?php

use FAU\Study\Data\DocProgramme;

class ilStudyDocCondTableGUI extends ilTable2GUI
{
    public $obj_id;
    
    /**
    * Constructor
    * @param    object  parent gui
    * @param    string  command of parent gui to show the table
    * @param    int   	course or group object id
    */
    public function __construct($a_parent_obj, $a_parent_cmd, $a_obj_id)
    {
        $this->setId('ilStudyDocCondTableGUI');
        parent::__construct($a_parent_obj, $a_parent_cmd);

        $this->obj_id = $a_obj_id;

        $this->addColumn($this->lng->txt("studycond_field_doc_program"), "program", "45%");
        $this->addColumn($this->lng->txt("studycond_field_min_approval_date"), "min_approval_date", "20%");
        $this->addColumn($this->lng->txt("studycond_field_max_approval_date"), "max_approval_date", "20%");
        $this->addColumn($this->lng->txt("functions"), "", "15%");

        $this->setEnableHeader(true);
        $this->setEnableTitle(false);
        $this->setEnableNumInfo(false);
        $this->setExternalSegmentation(true);
        $this->setFormAction($this->ctrl->getFormAction($a_parent_obj));
        $this->setRowTemplate("tpl.study_doc_cond_row.html", "Services/FAU/Cond/GUI");
        $this->setDefaultOrderField("program");
        $this->setDefaultOrderDirection("asc");
        $this->setPrefix("study_doc_cond");
        $this->readData();
    }

    /**
    * Get the form definitions of the learning module
    */
    private function readData()
    {
        global $DIC;

        $data = array();
        foreach ($DIC->fau()->cond()->repo()->getDocConditionsForObject($this->obj_id) as $condition) {

            $program = $DIC->fau()->study()->repo()->getDocProgramme($condition->getProgCode(), DocProgramme::model());

            $row = [];
            $row['cond_id'] = $condition->getId();
            $row['program'] = $program ? $program->getProgText() . ' [' . $program->getProgCode() . ']' : '';
            $row['min_approval_date'] = empty($condition->getMinApprovalDate()) ? null : new ilDate($condition->getMinApprovalDate(), IL_CAL_DATE);
            $row['max_approval_date'] = empty($condition->getMaxApprovalDate()) ? null : new ilDate($condition->getMaxApprovalDate(), IL_CAL_DATE);
            $data[] = $row;
        }
        $this->setData($data);
    }

    /**
     * Fill a single data row
     * @param array $a_set
     */
    protected function fillRow(array $a_set): void
    {
        $this->ctrl->setParameter($this->getParentObject(), "cond_id", $a_set["cond_id"]);
        $this->tpl->setVariable("LINK_EDIT", $this->ctrl->getLinkTarget($this->getParentObject(), "editDocCond"));
        $this->tpl->setVariable("LINK_DELETE", $this->ctrl->getLinkTarget($this->getParentObject(), "deleteDocCond"));
        $this->tpl->setVariable("PROGRAM", $a_set["program"]);
        if ($a_set["min_approval_date"] instanceof ilDate) {
            $this->tpl->setVariable("MIN_APPROVAL_DATE", ilDatePresentation::formatDate($a_set["min_approval_date"]));
        }
        if ($a_set["max_approval_date"] instanceof ilDate) {
            $this->tpl->setVariable("MAX_APPROVAL_DATE", ilDatePresentation::formatDate($a_set["max_approval_date"]));
        }

        $this->tpl->setVariable("TXT_EDIT", $this->lng->txt('edit'));
        $this->tpl->setVariable("TXT_DELETE", $this->lng->txt('delete'));
    }
}
