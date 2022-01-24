<?php
/* fau: studyCond - new class for handling conditions. */

include_once "Services/StudyData/classes/class.ilStudyCourseCond.php";
include_once "Services/StudyData/classes/class.ilStudyDocCond.php";

/**
* Class ilStudyCondGUI
 *
* @ilCtrl_Calls ilStudyCondGUI:
*/
class ilStudyCondGUI
{
    /** @var  string  */
    protected $headline;
    /** @var  string  */
    protected $info;
    /** @var  bool  */
    protected $with_backlink;
    /** @var ilCtrl */
    protected $ctrl;
    /** @var ilTemplate */
    protected $tpl;
    /** @var ilLanguage */
    protected $lng;
    /** @var ilPropertyFormGUI */
    protected $form_gui;

    protected $parent_gui;
    protected $parent_obj_id;
    protected $parent_ref_id;

    /**
     * Constructor
     * @access public
     * @param $a_parent_gui
     */
    public function __construct($a_parent_gui)
    {
        global $DIC;

        $this->ctrl = $DIC->ctrl();
        $this->tpl = $DIC['tpl'];
        $this->lng = $DIC->language();
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
        global $DIC;

        $ilErr = $DIC['ilErr'];

        // access to all functions in this class are only allowed if edit_permission is granted
        if (!$DIC->access()->checkAccess("write", "edit", $this->parent_ref_id, "", $this->parent_obj_id)) {
            $ilErr->raiseError($this->lng->txt("permission_denied"), $ilErr->MESSAGE);
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

    /**
     * @param $a_html
     * @throws ilTemplateException
     */
    private function show($a_html)
    {
        global $DIC;
        $ilToolbar = $DIC->toolbar();

        if ($this->isWithBacklink()) {
            $back = ilLinkButton::getInstance();
            $back->setUrl($this->ctrl->getLinkTarget($this, 'back'));
            $back->setCaption('back');
            $ilToolbar->addButtonInstance($back);
        }

        $tpl = new ilTemplate("tpl.list_study_cond.html", true, true, "Services/StudyData");
        $tpl->setVariable("CONDITIONS_HEADLINE", $this->getHeadline());
        $tpl->setVariable("CONDITIONS_COMBI_INFO", $this->getInfo());
        $tpl->setVariable("CONDITIONS_CONTENT", $a_html);
        $tpl->parse();
        $this->tpl->setContent($tpl->get());
    }


    /**
     * List the form definitions
     * @throws ilTemplateException
     */
    protected function listConditions()
    {
        global $DIC;
        $ilToolbar = $DIC->toolbar();

        $but1 = ilLinkButton::getInstance();
        $but1->setUrl($this->ctrl->getLinkTarget($this, 'createCourseCond'));
        $but1->setCaption('studycond_add_course_condition');
        $ilToolbar->addButtonInstance($but1);

        $but2 = ilLinkButton::getInstance();
        $but2->setUrl($this->ctrl->getLinkTarget($this, 'createDocCond'));
        $but2->setCaption('studycond_add_doc_condition');
        $ilToolbar->addButtonInstance($but2);


        require_once 'Services/StudyData/classes/class.ilStudyCourseCondTableGUI.php';
        $table1 = new ilStudyCourseCondTableGUI($this, "listConditions", $this->parent_obj_id);

        require_once 'Services/StudyData/classes/class.ilStudyDocCondTableGUI.php';
        $table2 = new ilStudyDocCondTableGUI($this, "listConditions", $this->parent_obj_id);

        $this->show($table1->getHTML() . $table2->getHTML());
    }

    /**
    * Return to the parent GUI
    */
    protected function back()
    {
        $this->ctrl->returnToParent($this, 'studycond');
    }


    /**
     * Show an empty form to create a new condition
     * @throws ilTemplateException
     */
    protected function createCourseCond()
    {
        $this->initCourseForm("create");
        $this->show($this->form_gui->getHtml());
    }

    /**
     * Show an empty form to create a new condition
     * @throws ilTemplateException
     */
    protected function createDocCond()
    {
        $this->initDocForm("create");
        $this->show($this->form_gui->getHtml());
    }


    /**
     * Show the form to edit an existing condition
     * @throws ilTemplateException
     */
    protected function editCourseCond()
    {
        $condition = new ilStudyCourseCond((int) $_GET["cond_id"]);

        $this->initCourseForm("edit", $this->getCourseValues($condition));
        $this->show($this->form_gui->getHtml());
    }

    /**
     * Show the form to edit an existing condition
     * @throws ilTemplateException
     */
    protected function editDocCond()
    {
        $condition = new ilStudyDocCond((int) $_GET["cond_id"]);
        $this->initDocForm("edit", $this->getDocValues($condition));
        $this->show($this->form_gui->getHtml());
    }


    /**
     * Save a newly entered condition
     * @throws ilTemplateException
     */
    protected function saveCourseCond()
    {
        $this->initCourseForm("create");
        if ($this->form_gui->checkInput()) {
            $condition = new ilStudyCourseCond;
            $condition->obj_id = $this->parent_obj_id;
            $this->setCourseValues($condition);
            $condition->write();
            
            ilUtil::sendInfo($this->lng->txt("studycond_condition_saved"), true);
            $this->ctrl->redirect($this, 'listConditions');
        } else {
            $this->form_gui->setValuesByPost();
            $this->show($this->form_gui->getHtml());
        }
    }

    /**
     * Save a newly entered condition
     * @throws ilTemplateException
     */
    protected function saveDocCond()
    {
        $this->initDocForm("create");
        if ($this->form_gui->checkInput()) {
            $condition = new ilStudyDocCond;
            $condition->obj_id = $this->parent_obj_id;
            $this->setDocValues($condition);
            $condition->write();

            ilUtil::sendInfo($this->lng->txt("studycond_condition_saved"), true);
            $this->ctrl->redirect($this, 'listConditions');
        } else {
            $this->form_gui->setValuesByPost();
            $this->show($this->form_gui->getHtml());
        }
    }


    /**
     * Update a changed condition
     * @throws ilTemplateException
     */
    protected function updateCourseCond()
    {
        $this->ctrl->saveParameter($this, "cond_id");
        $this->initCourseForm("edit");
        
        if ($this->form_gui->checkInput()) {
            $condition = new ilStudyCourseCond((int) $_GET["cond_id"]);
            $this->setCourseValues($condition);
            $condition->write();
            
            ilUtil::sendInfo($this->lng->txt("studycond_condition_updated"), true);
            $this->ctrl->redirect($this, 'listConditions');
        } else {
            $this->form_gui->setValuesByPost();
            $this->show($this->form_gui->getHtml());
        }
    }

    /**
     * Update a changed condition
     * @throws ilTemplateException
     */
    protected function updateDocCond()
    {
        $this->ctrl->saveParameter($this, "cond_id");
        $this->initDocForm("edit");

        if ($this->form_gui->checkInput()) {
            $condition = new ilStudyDocCond((int) $_GET["cond_id"]);
            $this->setDocValues($condition);
            $condition->write();

            ilUtil::sendInfo($this->lng->txt("studycond_condition_updated"), true);
            $this->ctrl->redirect($this, 'listConditions');
        } else {
            $this->form_gui->setValuesByPost();
            $this->show($this->form_gui->getHtml());
        }
    }


    /**
    * Delete a condition
    */
    protected function deleteCourseCond()
    {
        $cond = new ilStudyCourseCond($_GET["cond_id"]);
        if ($cond->obj_id == $this->parent_obj_id) {
            $cond->delete();
            ilUtil::sendInfo($this->lng->txt("studycond_condition_deleted"), true);
        }
        $this->ctrl->redirect($this, 'listConditions');
    }

    /**
     * Delete a condition
     */
    protected function deleteDocCond()
    {
        $cond = new ilStudyDocCond($_GET["cond_id"]);
        if ($cond->obj_id == $this->parent_obj_id) {
            $cond->delete();
            ilUtil::sendInfo($this->lng->txt("studycond_condition_deleted"), true);
        }
        $this->ctrl->redirect($this, 'listConditions');
    }


    /**
     * Get the values of a web form into property gui
     * @param    ilStudyCourseCond  $a_condition
     * @return array;
     */
    private function getCourseValues($a_condition)
    {
        $values = [];
        $values["subject_id"] = $a_condition->subject_id;
        $values["degree_id"] = $a_condition->degree_id;
        $values["min_semester"] = $a_condition->min_semester;
        $values["max_semester"] = $a_condition->max_semester;
        $values["ref_semester"] = $a_condition->ref_semester;
        $values["study_type"] = $a_condition->study_type;

        return $values;
    }

    /**
     * Get the values of a web form into property gui
     * @param    ilStudyDocCond  $a_condition
     * @return  array
     */
    private function getDocValues($a_condition)
    {
        $values = [];
        $values['prog_id'] = $a_condition->prog_id;
        $values['min_approval_date'] = $a_condition->min_approval_date;
        $values['max_approval_date'] = $a_condition->max_approval_date;

        return $values;
    }



    /**
    * Set the values of the property gui into a webform
    * @param    ilStudyCourseCond  $a_condition
    */
    private function setCourseValues($a_condition)
    {
        $form_gui = $this->form_gui;

        $subject_id = $form_gui->getInput("subject_id");
        $a_condition->subject_id = ($subject_id < 0 ? null : $subject_id);

        $degree_id = $form_gui->getInput("degree_id");
        $a_condition->degree_id = ($degree_id < 0 ? null : $degree_id);

        $min_semester = $form_gui->getInput("min_semester");
        $a_condition->min_semester = (empty($min_semester) ? null : $min_semester);

        $max_semester = $form_gui->getInput("max_semester");
        $a_condition->max_semester = (empty($max_semester) ? null : $max_semester);

        $ref_semester = $form_gui->getInput("ref_semester");
        $a_condition->ref_semester = (empty($ref_semester) ? null : $ref_semester);

        $study_type = $form_gui->getInput("study_type");
        $a_condition->study_type = (empty($study_type) ? null : $study_type);
    }

    /**
     * Set the values of the property gui into a webform
     * @param    ilStudyDocCond  $a_condition
     */
    private function setDocValues($a_condition)
    {
        $this->form_gui->setValuesByPost();

        $prog_id = $this->form_gui->getInput("prog_id");
        $a_condition->prog_id = ($prog_id < 0  ? null : $prog_id);


        /** @var ilDateTimeInputGUI $item */
        $item = $this->form_gui->getItemByPostVar('min_approval_date');
        $a_condition->min_approval_date = $item->getDate();

        /** @var ilDateTimeInputGUI $item */
        $item = $this->form_gui->getItemByPostVar('max_approval_date');
        $a_condition->max_approval_date = $item->getDate();
    }


    /**
     * Initialize the form GUI
     * @param    int     $a_mode form mode ("create" or "edit")
     * @param   array   $a_values
     */
    private function initCourseForm($a_mode, $a_values = [])
    {
        require_once("Services/StudyData/classes/class.ilStudyCourseData.php");
        require_once("Services/StudyData/classes/class.ilStudyOptionSubject.php");
        require_once("Services/StudyData/classes/class.ilStudyOptionDegree.php");

        $this->form_gui = new ilPropertyFormGUI();
        $this->form_gui->setFormAction($this->ctrl->getFormAction($this));

        // subject
        $item = new ilSelectInputGUI($this->lng->txt("studycond_field_subject"), "subject_id");
        $item->setInfo($this->lng->txt("studycond_field_subject_info"));
        $item->setOptions(ilStudyOptionSubject::_getSelectOptions(-1, $a_values['subject_id']));
        $item->setValue($a_values['subject_id']);
        $this->form_gui->addItem($item);

        // degree
        $item = new ilSelectInputGUI($this->lng->txt("studycond_field_degree"), "degree_id");
        $item->setInfo($this->lng->txt("studycond_field_degree_info"));
        $item->setOptions(ilStudyOptionDegree::_getSelectOptions(-1, $a_values['degree_id']));
        $item->setValue($a_values['degree_id']);
        $this->form_gui->addItem($item);

        // study_type
        $item = new ilSelectInputGUI($this->lng->txt("studydata_type"), "study_type");
        $item->setOptions(ilStudyCourseData::_getStudyTypeSelectOptions());
        $item->setInfo($this->lng->txt("studycond_field_studytype_info"));
        $item->setValue($a_values['study_type']);
        $this->form_gui->addItem($item);

        // min semester
        $item = new ilNumberInputGUI($this->lng->txt("studycond_field_min_semester"), "min_semester");
        $item->setInfo($this->lng->txt("studycond_field_min_semester_info"));
        $item->setSize(2);
        $item->setValue($a_values['min_semester']);
        $this->form_gui->addItem($item);

        // max semester
        $item = new ilNumberInputGUI($this->lng->txt("studycond_field_max_semester"), "max_semester");
        $item->setInfo($this->lng->txt("studycond_field_max_semester_info"));
        $item->setSize(2);
        $item->setValue($a_values['max_semester']);
        $this->form_gui->addItem($item);

        // ref semester
        $item = new ilSelectInputGUI($this->lng->txt("studycond_field_ref_semester"), "ref_semester");
        $item->setInfo($this->lng->txt("studycond_field_ref_semester_info"));
        $item->setOptions(ilStudyCourseData::_getSemesterSelectOptions());
        $item->setValue($a_values['ref_semester']);
        $this->form_gui->addItem($item);

        // save and cancel commands
        if ($a_mode == "create") {
            $this->form_gui->setTitle($this->lng->txt("studycond_add_condition"));
            $this->form_gui->addCommandButton("saveCourseCond", $this->lng->txt("save"));
            $this->form_gui->addCommandButton("listConditions", $this->lng->txt("cancel"));
        } else {
            $this->form_gui->setTitle($this->lng->txt("studycond_edit_condition"));
            $this->form_gui->addCommandButton("updateCourseCond", $this->lng->txt("save"));
            $this->form_gui->addCommandButton("listConditions", $this->lng->txt("cancel"));
        }
    }

    /**
     * Initialize the form GUI
     * @param    int        $a_mode form mode ("create" or "edit")
     * @param    array      $a_values
     */
    private function initDocForm($a_mode, $a_values = [])
    {
        require_once("Services/StudyData/classes/class.ilStudyOptionDocProgram.php");

        $this->form_gui = new ilPropertyFormGUI();
        $this->form_gui->setFormAction($this->ctrl->getFormAction($this));

        // subject
        $item = new ilSelectInputGUI($this->lng->txt("studycond_field_doc_program"), "prog_id");
        $item->setOptions(ilStudyOptionDocProgram::_getSelectOptions(-1, $a_values['prog_id']));
        $item->setValue($a_values['prog_id']);
        $this->form_gui->addItem($item);

        // min approval date
        $item = new ilDateTimeInputGUI($this->lng->txt('studycond_field_min_approval_date'), 'min_approval_date');
        $item->setShowTime(false);
        $item->setDate($a_values['min_approval_date']);
        $this->form_gui->addItem($item);

        // max approval date
        $item = new ilDateTimeInputGUI($this->lng->txt('studycond_field_max_approval_date'), 'max_approval_date');
        $item->setShowTime(false);
        $item->setDate($a_values['max_approval_date']);
        $this->form_gui->addItem($item);


        // save and cancel commands
        if ($a_mode == "create") {
            $this->form_gui->setTitle($this->lng->txt("studycond_add_condition"));
            $this->form_gui->addCommandButton("saveDocCond", $this->lng->txt("save"));
            $this->form_gui->addCommandButton("listConditions", $this->lng->txt("cancel"));
        } else {
            $this->form_gui->setTitle($this->lng->txt("studycond_edit_condition"));
            $this->form_gui->addCommandButton("updateDocCond", $this->lng->txt("save"));
            $this->form_gui->addCommandButton("listConditions", $this->lng->txt("cancel"));
        }
    }
}
