<?php
/* fau: studyCond - new table class. */

require_once 'Services/Table/classes/class.ilTable2GUI.php';
require_once "Services/StudyData/classes/class.ilStudyCourseData.php";
require_once "Services/StudyData/classes/class.ilStudyCourseCond.php";
require_once "Services/StudyData/classes/class.ilStudyOptionSubject.php";
require_once "Services/StudyData/classes/class.ilStudyOptionDegree.php";


class ilStudyCourseCondTableGUI extends ilTable2GUI
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
        $this->setId('ilStudyCorseCondTableGUI');
        parent::__construct($a_parent_obj, $a_parent_cmd);

        $this->obj_id = $a_obj_id;

        $this->addColumn($this->lng->txt("studycond_field_subject"), "subject", "20%");
        $this->addColumn($this->lng->txt("studycond_field_degree"), "degree", "20%");
        $this->addColumn($this->lng->txt("studydata_type"), "study_type", "10%");
        $this->addColumn($this->lng->txt("studycond_field_min_semester"), "min_semester", "10%");
        $this->addColumn($this->lng->txt("studycond_field_max_semester"), "max_semester", "10%");
        $this->addColumn($this->lng->txt("studycond_field_ref_semester"), "ref_semester", "10%");
        $this->addColumn($this->lng->txt("functions"), "", "15%");

        $this->setEnableHeader(true);
        $this->setEnableTitle(false);
        $this->setEnableNumInfo(false);
        $this->setExternalSegmentation(true);
        $this->setFormAction($this->ctrl->getFormAction($a_parent_obj));
        $this->setRowTemplate("tpl.study_course_cond_row.html", "Services/StudyData");
        $this->setDefaultOrderField("subject");
        $this->setDefaultOrderDirection("asc");
        $this->setPrefix("study_course_cond");
        $this->readData();
    }

    /**
    * Get the form definitions of the learning module
    */
    private function readData()
    {
        $data = array();

        foreach (ilStudyCourseCond::_get($this->obj_id) as $cond) {
            $row = [];
            $row['cond_id'] = $cond->cond_id;
            $row['subject'] = ilStudyOptionSubject::_lookupText($cond->subject_id);
            $row['degree'] = ilStudyOptionDegree::_lookupText($cond->degree_id);
            $row['min_semester'] = $cond->min_semester;
            $row['max_semester'] = $cond->max_semester;
            $row['ref_semester'] = $cond->ref_semester;
            $row['study_type'] = $cond->study_type;
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
        $this->ctrl->setParameter($this->getParentObject(), "cond_id", $a_set["cond_id"]);
        $this->tpl->setVariable("LINK_EDIT", $this->ctrl->getLinkTarget($this->getParentObject(), "editCourseCond"));
        $this->tpl->setVariable("LINK_DELETE", $this->ctrl->getLinkTarget($this->getParentObject(), "deleteCourseCond"));
        $this->tpl->setVariable("SUBJECT", $a_set["subject"]);
        $this->tpl->setVariable("DEGREE", $a_set["degree"]);
        if ($a_set["min_semester"]) {
            $this->tpl->setVariable("MIN_SEMESTER", $a_set["min_semester"]);
        }
        if ($a_set["max_semester"]) {
            $this->tpl->setVariable("MAX_SEMESTER", $a_set["max_semester"]);
        }
        if ($a_set["ref_semester"]) {
            $this->tpl->setVariable("REF_SEMESTER", ilStudyCourseData::_getRefSemesterText($a_set["ref_semester"]));
        }
        if ($a_set["study_type"]) {
            $this->tpl->setVariable("STUDY_TYPE", ilStudyCourseData::_getStudyTypeText($a_set["study_type"]));
        }
        
        $this->tpl->setVariable("TXT_EDIT", $this->lng->txt('edit'));
        $this->tpl->setVariable("TXT_DELETE", $this->lng->txt('delete'));
    }
}
