<?php

use FAU\Study\Data\Term;
use FAU\Study\Data\StudySubject;
use FAU\Study\Data\StudyDegree;
use FAU\Study\Data\StudyEnrolment;

class ilStudyCosCondTableGUI extends ilTable2GUI
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
        $this->setId('ilStudyCosCondTableGUI');
        parent::__construct($a_parent_obj, $a_parent_cmd);

        $this->obj_id = $a_obj_id;

        $this->addColumn($this->lng->txt("studycond_field_subject"), "subject", "20%");
        $this->addColumn($this->lng->txt("studycond_field_degree"), "degree", "20%");
        $this->addColumn($this->lng->txt("studycond_field_enrolment"), "enrolment", "10%");
        $this->addColumn($this->lng->txt("studycond_field_min_semester"), "min_semester", "10%");
        $this->addColumn($this->lng->txt("studycond_field_max_semester"), "max_semester", "10%");
        $this->addColumn($this->lng->txt("studycond_field_ref_semester"), "ref_semester", "10%");
        $this->addColumn($this->lng->txt("functions"), "", "15%");

        $this->setEnableHeader(true);
        $this->setEnableTitle(false);
        $this->setEnableNumInfo(false);
        $this->setExternalSegmentation(true);
        $this->setFormAction($this->ctrl->getFormAction($a_parent_obj));
        $this->setRowTemplate("tpl.study_cos_cond_row.html", "Services/StudyData");
        $this->setDefaultOrderField("subject");
        $this->setDefaultOrderDirection("asc");
        $this->setPrefix("study_cos_cond");
        $this->readData();
    }

    /**
    * Get the form definitions of the learning module
    */
    private function readData()
    {
        global $DIC;
        $data = array();

        foreach ($DIC->fau()->cond()->repo()->getCosConditionsForObject($this->obj_id) as $condition) {

            $subject = $DIC->fau()->study()->repo()->getStudySubject($condition->getSubjectHisId(), StudySubject::model());
            $degree = $DIC->fau()->study()->repo()->getStudyDegree($condition->getDegreeHisId(), StudyDegree::model());
            $enrolment = $DIC->fau()->study()->repo()->getStudyEnrolment($condition->getEnrolmentId(), StudyEnrolment::model());

            $row = [];
            $row['cond_id'] = $condition->getId();
            $row['subject'] = $subject->getSubjectTitle($DIC->language()->getLangKey());
            $row['degree'] = $degree->getDegreeTitle($DIC->language()->getLangKey());
            $row['enrolment'] = $enrolment->getEnrolmentTitle($DIC->language()->getLangKey());
            $row['min_semester'] = $condition->getMinSemester();
            $row['max_semester'] = $condition->getMaxSemester();
            $row['ref_semester'] = (new Term($condition->getRefTermYear(), $condition->getRefTermTypeId()))->toString();
            $data[] = $row;
        }
        $this->setData($data);
    }

    /**
     * Fill a single data row
     * @param array $a_set
     */
    protected function fillRow($a_set)
    {
        global $DIC;

        $this->ctrl->setParameter($this->getParentObject(), "cond_id", $a_set["cond_id"]);
        $this->tpl->setVariable("LINK_EDIT", $this->ctrl->getLinkTarget($this->getParentObject(), "editCourseCond"));
        $this->tpl->setVariable("LINK_DELETE", $this->ctrl->getLinkTarget($this->getParentObject(), "deleteCourseCond"));
        $this->tpl->setVariable("SUBJECT", $a_set["subject"]);
        $this->tpl->setVariable("DEGREE", $a_set["degree"]);
        $this->tpl->setVariable("ENROLMENT",  $a_set["enrolment"]);
        if ($a_set["min_semester"]) {
            $this->tpl->setVariable("MIN_SEMESTER", $a_set["min_semester"]);
        }
        if ($a_set["max_semester"]) {
            $this->tpl->setVariable("MAX_SEMESTER", $a_set["max_semester"]);
        }
        if ($a_set["ref_semester"]) {
            $this->tpl->setVariable("REF_SEMESTER", $DIC->fau()->study()->getReferenceTermText(Term::fromString($a_set['ref_semester'])));
        }

        $this->tpl->setVariable("TXT_EDIT", $this->lng->txt('edit'));
        $this->tpl->setVariable("TXT_DELETE", $this->lng->txt('delete'));
    }
}
