<?php

use ILIAS\DI\Container;
use FAU\Cond\Data\CosCondition;
use FAU\Cond\Data\DocCondition;
use FAU\Study\Data\Term;

/**
* Class ilStudyCondGUI
 *
* @ilCtrl_Calls ilStudyCondGUI:
*/
class ilStudyCondGUI
{
    protected Container $dic;

    /** @var  string  */
    protected $headline;
    /** @var  string  */
    protected $info;
    /** @var  bool  */
    protected $with_backlink;
    /** @var ilCtrl */
    protected $ctrl;
    /** @var ilGlobalTemplate */
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
        $this->dic = $DIC;
        $this->ctrl = $DIC->ctrl();
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->lng = $DIC->language();
        $this->err = $DIC['ilErr'];

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
        // access to all functions in this class are only allowed if edit_permission is granted
        if (!$this->dic->access()->checkAccess("write", "edit", $this->parent_ref_id, "", $this->parent_obj_id)) {
            $this->err->raiseError($this->lng->txt("permission_denied"), $this->err->MESSAGE);
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
        if ($this->isWithBacklink()) {
            $back = ilLinkButton::getInstance();
            $back->setUrl($this->ctrl->getLinkTarget($this, 'back'));
            $back->setCaption('back');
            $this->dic->toolbar()->addButtonInstance($back);
        }

        $tpl = new ilTemplate("tpl.list_study_cond.html", true, true, "Services/FAU/Cond/GUI");
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
        $but1 = ilLinkButton::getInstance();
        $but1->setUrl($this->ctrl->getLinkTarget($this, 'createCourseCond'));
        $but1->setCaption('studycond_add_course_condition');
        $this->dic->toolbar()->addButtonInstance($but1);

        $but2 = ilLinkButton::getInstance();
        $but2->setUrl($this->ctrl->getLinkTarget($this, 'createDocCond'));
        $but2->setCaption('studycond_add_doc_condition');
        $this->dic->toolbar()->addButtonInstance($but2);

        $table1 = new ilStudyCosCondTableGUI($this, "listConditions", $this->parent_obj_id);
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
        $this->initCourseForm("create", CosCondition::model());
        $this->show($this->form_gui->getHtml());
    }

    /**
     * Show an empty form to create a new condition
     * @throws ilTemplateException
     */
    protected function createDocCond()
    {
        $this->initDocForm("create", DocCondition::model());
        $this->show($this->form_gui->getHtml());
    }


    /**
     * Show the form to edit an existing condition
     * @throws ilTemplateException
     */
    protected function editCourseCond()
    {
        $condition = $this->dic->fau()->cond()->repo()->getCosCondition((int) $_GET["cond_id"], CosCondition::model());
        $this->initCourseForm("edit", $condition);
        $this->show($this->form_gui->getHtml());
    }

    /**
     * Show the form to edit an existing condition
     * @throws ilTemplateException
     */
    protected function editDocCond()
    {
        $condition = $this->dic->fau()->cond()->repo()->getDocCondition((int) $_GET["cond_id"], DocCondition::model());
        $this->initDocForm("edit", $condition);
        $this->show($this->form_gui->getHtml());
    }


    /**
     * Save a newly entered condition
     * @throws ilTemplateException
     */
    protected function saveCourseCond()
    {
        $this->initCourseForm("create", CosCondition::model());
        if ($this->form_gui->checkInput()) {
            $condition = $this->getCosConditionFromForm(0, $this->parent_obj_id, $this->form_gui);
            $this->dic->fau()->cond()->repo()->save($condition);
            
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
        $this->initDocForm("create", DocCondition::model());
        if ($this->form_gui->checkInput()) {
            $condition = $this->getDocConditionFromForm(0, $this->parent_obj_id, $this->form_gui);
            $this->dic->fau()->cond()->repo()->save($condition);

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
        $this->initCourseForm("edit", CosCondition::model());
        
        if ($this->form_gui->checkInput()) {
            $condition = $this->getCosConditionFromForm($_GET["cond_id"], $this->parent_obj_id, $this->form_gui);
            $this->dic->fau()->cond()->repo()->save($condition);
            
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
        $this->initDocForm("edit", DocCondition::model());

        if ($this->form_gui->checkInput()) {
            $condition = $this->getDocConditionFromForm($_GET["cond_id"], $this->parent_obj_id, $this->form_gui);
            $this->dic->fau()->cond()->repo()->save($condition);

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
        if (!empty($cond = $this->dic->fau()->cond()->repo()->getCosCondition($_GET["cond_id"]))) {
            if ($cond->getIliasObjId() == $this->parent_obj_id) {
                $this->dic->fau()->cond()->repo()->delete($cond);
                ilUtil::sendInfo($this->lng->txt("studycond_condition_deleted"), true);
            }
        }
        $this->ctrl->redirect($this, 'listConditions');
    }

    /**
     * Delete a condition
     */
    protected function deleteDocCond()
    {
        if (!empty($cond = $this->dic->fau()->cond()->repo()->getDocCondition($_GET["cond_id"]))) {
            if ($cond->getIliasObjId() == $this->parent_obj_id) {
                $this->dic->fau()->cond()->repo()->delete($cond);
                ilUtil::sendInfo($this->lng->txt("studycond_condition_deleted"), true);
            }
        }
        $this->ctrl->redirect($this, 'listConditions');
    }

    /**
     * Get a course of study condition from form inputs
     */
    private function getCosConditionFromForm(int $id, int $obj_id, ilPropertyFormGUI $form_gui) : CosCondition
    {
        $subject_id = $form_gui->getInput("subject_id");
        $degree_id = $form_gui->getInput("degree_id");
        $min_semester = $form_gui->getInput("min_semester");
        $max_semester = $form_gui->getInput("max_semester");
        $ref_semester = $form_gui->getInput("ref_semester");
        $study_enrolment = $form_gui->getInput("study_enrolment");

        return new CosCondition(
            $id,
            $obj_id,
            empty($subject_id) ? null : $subject_id,
            empty($degree_id) ? null : $degree_id,
            null,
            empty($study_enrolment) ? null : $study_enrolment,
            empty($min_semester) ? null : $min_semester,
            empty($max_semester) ? null : $max_semester,
            empty($ref_semester) ? null : Term::fromString($ref_semester)->getYear(),
            empty($ref_semester) ? null : Term::fromString($ref_semester)->getTypeId()
        );
    }

    /**
     * Get a doc program condition from form inputs
     */
    private function getDocConditionFromForm(int $id, int $obj_id, ilPropertyFormGUI $form_gui) : DocCondition
    {
        $prog_code = $form_gui->getInput("prog_code");

        /** @var ilDateTimeInputGUI $item */
        /** @var ilDate $min_approval_date */
        $item = $form_gui->getItemByPostVar('min_approval_date');
        $min_approval_date = $item->getDate();

        /** @var ilDateTimeInputGUI $item */
        /** @var ilDate $max_approval_date */
        $item = $form_gui->getItemByPostVar('max_approval_date');
        $max_approval_date = $item->getDate();

        return new DocCondition(
            $id,
            $obj_id,
            empty($prog_code) ? null : $prog_code,
            empty($min_approval_date) ? null : $min_approval_date->get(IL_CAL_DATE),
            empty($max_approval_date) ? null : $max_approval_date->get(IL_CAL_DATE),
        );
    }


    /**
     * Initialize the form GUI
     */
    private function initCourseForm(string $a_mode, CosCondition $condition)
    {
        $this->form_gui = new ilPropertyFormGUI();
        $this->form_gui->setFormAction($this->ctrl->getFormAction($this));

        // subject
        $item = new ilSelectInputGUI($this->lng->txt("studycond_field_subject"), "subject_id");
        $item->setInfo($this->lng->txt("studycond_field_subject_info"));
        $item->setOptions($this->dic->fau()->study()->getSubjectSelectOptions(0, $condition->getSubjectHisId()));
        $item->setValue($condition->getSubjectHisId());
        $this->form_gui->addItem($item);

        // degree
        $item = new ilSelectInputGUI($this->lng->txt("studycond_field_degree"), "degree_id");
        $item->setInfo($this->lng->txt("studycond_field_degree_info"));
        $item->setOptions($this->dic->fau()->study()->getDegreeSelectOptions(0, $condition->getSchoolHisId()));
        $item->setValue($condition->getDegreeHisId());
        $this->form_gui->addItem($item);

        // enrolment
        $item = new ilSelectInputGUI($this->lng->txt("studycond_field_enrolment"), "study_enrolment");
        $item->setOptions($this->dic->fau()->study()->getEnrolmentSelectOptions(0, $condition->getEnrolmentId()));
        $item->setInfo($this->lng->txt("studycond_field_enrolment_info"));
        $item->setValue($condition->getEnrolmentId());
        $this->form_gui->addItem($item);

        // min semester
        $item = new ilNumberInputGUI($this->lng->txt("studycond_field_min_semester"), "min_semester");
        $item->setInfo($this->lng->txt("studycond_field_min_semester_info"));
        $item->setSize(2);
        $item->setValue($condition->getMinSemester());
        $this->form_gui->addItem($item);

        // max semester
        $item = new ilNumberInputGUI($this->lng->txt("studycond_field_max_semester"), "max_semester");
        $item->setInfo($this->lng->txt("studycond_field_max_semester_info"));
        $item->setSize(2);
        $item->setValue($condition->getMaxSemester());
        $this->form_gui->addItem($item);

        // ref semester
        $ref_semester = empty($condition->getRefTerm()) ? '' : $condition->getRefTerm()->toString();
        $item = new ilSelectInputGUI($this->lng->txt("studycond_field_ref_semester"), "ref_semester");
        $item->setInfo($this->lng->txt("studycond_field_ref_semester_info"));
        $item->setOptions($this->dic->fau()->study()->getTermSelectOptions('',$ref_semester));
        $item->setValue($ref_semester);
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
     */
    private function initDocForm(string $a_mode, DocCondition $condition)
    {
        $this->form_gui = new ilPropertyFormGUI();
        $this->form_gui->setFormAction($this->ctrl->getFormAction($this));

        // Prog code
        $item = new ilSelectInputGUI($this->lng->txt("studycond_field_doc_program"), "prog_code");
        $item->setOptions($this->dic->fau()->study()->getDocProgSelectOptions('', $condition->getProgCode()));
        $item->setValue($condition->getProgCode());
        $this->form_gui->addItem($item);

        // min approval date
        $item = new ilDateTimeInputGUI($this->lng->txt('studycond_field_min_approval_date'), 'min_approval_date');
        $item->setShowTime(false);
        $item->setDate(empty($condition->getMinApprovalDate()) ? null : new ilDate($condition->getMinApprovalDate(), IL_CAL_DATE));
        $this->form_gui->addItem($item);

        // max approval date
        $item = new ilDateTimeInputGUI($this->lng->txt('studycond_field_max_approval_date'), 'max_approval_date');
        $item->setShowTime(false);
        $item->setDate(empty($condition->getMaxApprovalDate()) ? null : new ilDate($condition->getMaxApprovalDate(), IL_CAL_DATE));
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
