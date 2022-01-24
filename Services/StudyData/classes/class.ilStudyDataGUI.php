<?php
/* fau: studyData - new class. */

/**
 * Class ilStudyDataGUI
 *
 * @ilCtrl_Calls ilStudyDataGUI:
 *
 */
require_once "Services/StudyData/classes/class.ilStudyCourseData.php";
require_once "Services/StudyData/classes/class.ilStudyDocData.php";
require_once("Services/StudyData/classes/class.ilStudyOptionDegree.php");
require_once("Services/StudyData/classes/class.ilStudyOptionSchool.php");
require_once("Services/StudyData/classes/class.ilStudyOptionSubject.php");
require_once "Services/StudyData/classes/class.ilStudyOptionDocProgram.php";

class ilStudyDataGUI
{
    
    /* @var	ilObjUser */
    public $user = null;
    
    /* @var ilPropertyFormGUI */
    public $form = null;

    /** @var ilTemplate */
    public $tpl;

    /** @var ilCtrl */
    public $ctrl;

    /** @var ilLanguage  */
    public $lng;

    /**
     * Constructor
     * @param ilObjUser $a_user
     */
    public function __construct($a_user)
    {
        global $DIC;
        
        $this->tpl = $DIC['tpl'];
        $this->ctrl = $DIC->ctrl();
        $this->lng = $DIC->language();
        $this->lng->loadLanguageModule('registration');
        
        $this->user = $a_user;
    }
    

    /**
     * Command execution
     */
    public function executeCommand()
    {
        global $DIC;
        /** @var ilErrorHandling $ilErr */
        $ilErr = $DIC['ilErr'];

        if (!$DIC->rbac()->system()->checkAccess("write", USER_FOLDER_ID)) {
            $ilErr->raiseError("You are not entitled to access this page!");
        }

        $cmd = $this->ctrl->getCmd("edit");
        switch ($cmd) {
            case 'edit':
            case 'update':
                $cmd .= 'Object';
                return $this->$cmd();
        
            default:
                return false;
        }
    }

    /**
     * Show the edit screen
     */
    protected function editObject()
    {
        $this->initForm($this->getValues());
        $this->tpl->setContent($this->form->getHtml());
    }
    
    /**
     * Save the form data
     */
    protected function updateObject()
    {
        $this->initForm();

        if ($this->form->checkInput() and $this->checkAndSetValues()) {
            ilUtil::sendSuccess($this->lng->txt("msg_obj_modified"), true);
            $this->ctrl->redirect($this, "edit");
        } else {
            $this->form->setValuesByPost();
            $this->tpl->setContent($this->form->getHtml());
        }
    }
    
    
    /**
     * Init the data form
     * @param array $values - value to be set in the form
     */
    private function initForm($values = [])
    {
        $this->form = new ilPropertyFormGUI();
        $this->form->setTitle($this->lng->txt("studydata_edit"));
        
        // matriculation
        $item = new ilTextInputGUI($this->lng->txt("matriculation"), "matriculation");
        $item->setValue($values['matriculation']);
        $this->form->addItem($item);
        
        // three studies
        for ($study_no = 1; $study_no <= 3; $study_no++) {
            // title
            $item = new ilFormSectionHeaderGUI;
            $item->setTitle(sprintf($this->lng->txt("studydata_study_no"), $study_no));
            $this->form->addItem($item);
            
            // ref semester
            $item = new ilSelectInputGUI($this->lng->txt('studydata_ref_semester'), 'study' . $study_no . '_ref_semester');
            $item->setOptions(ilStudyCourseData::_getSemesterSelectOptions());
            $item->setValue($values['study' . $study_no . '_ref_semester']);
            $this->form->addItem($item);
            
            // degree
            $item = new ilSelectInputGUI($this->lng->txt('studydata_degree'), 'study' . $study_no . '_degree_id');
            $item->setOptions(ilStudyOptionDegree::_getSelectOptions(-1, $values['study' . $study_no . '_degree_id']));
            $item->setValue($values['study' . $study_no . '_degree_id']);
            $this->form->addItem($item);
            
            // school
            $item = new ilSelectInputGUI($this->lng->txt('studydata_school'), 'study' . $study_no . '_school_id');
            $item->setOptions(ilStudyOptionSchool::_getSelectOptions(-1, $values['study' . $study_no . '_school_id']));
            $item->setValue($values['study' . $study_no . '_school_id']);
            $this->form->addItem($item);

            // type
            $item = new ilSelectInputGUI($this->lng->txt('studydata_type'), 'study' . $study_no . '_study_type');
            $item->setOptions(ilStudyCourseData::_getStudyTypeSelectOptions());
            $item->setValue($values['study' . $study_no . '_study_type']);
            $this->form->addItem($item);

            for ($subject_no = 1; $subject_no <= 3; $subject_no++) {
                // subject
                $item = new ilSelectInputGUI(
                    sprintf($this->lng->txt('studydata_subject_no'), $subject_no),
                    'study' . $study_no . '_subject' . $subject_no . '_subject_id'
                );
                $item->setOptions(ilStudyOptionSubject::_getSelectOptions(-1, $values['study' . $study_no . '_subject' . $subject_no . '_subject_id']));
                $item->setValue($values['study' . $study_no . '_subject' . $subject_no . '_subject_id']);
                $this->form->addItem($item);
                
                // semester
                $item = new ilNumberInputGUI(
                    sprintf($this->lng->txt('studydata_semester_subject_no'), $subject_no),
                    'study' . $study_no . '_subject' . $subject_no . '_semester'
                );
                $item->setDecimals(0);
                $item->setMinValue(1);
                $item->setSize(2);
                $item->setValue($values['study' . $study_no . '_subject' . $subject_no . '_semester']);
                $this->form->addItem($item);
            }
        }

        $item = new ilFormSectionHeaderGUI();
        $item->setTitle($this->lng->txt('studydata_promotion'));
        $this->form->addItem($item);

        // doc programme
        $item = new ilSelectInputGUI($this->lng->txt('studydata_promotion_program'), 'study_doc_prog');
        $item->setOptions(ilStudyOptionDocProgram::_getSelectOptions(-1, $values['study_doc_prog']));
        $item->setValue($values['study_doc_prog']);
        $this->form->addItem($item);

        // doc approval date
        $item = new ilDateTimeInputGUI($this->lng->txt('studydata_promotion_approval_date'), 'study_doc_approval_date');
        $item->setShowTime(false);
        $item->setDate($values['study_doc_approval_date']);
        $this->form->addItem($item);

        $this->form->addCommandButton("update", $this->lng->txt("update"));
        $this->form->setFormAction($this->ctrl->getFormAction($this));
    }
    
    
    /**
     * get the stored study data
     */
    private function getValues()
    {
        $studydata = ilStudyCourseData::_get($this->user->getId());
        $values = array();
        $values['matriculation'] = $this->user->getMatriculation();
        $study_no = 1;
        foreach ($studydata as $study) {
            $values['study' . $study_no . '_ref_semester'] = $study->ref_semester;
            $values['study' . $study_no . '_degree_id'] = $study->degree_id;
            $values['study' . $study_no . '_school_id'] = $study->school_id;
            $values['study' . $study_no . '_study_type'] = $study->study_type;
            
            $subject_no = 1;
            foreach ($study->subjects as $subject) {
                $values['study' . $study_no . '_subject' . $subject_no . '_subject_id'] = $subject->subject_id;
                $values['study' . $study_no . '_subject' . $subject_no . '_semester'] = $subject->semester;
                $subject_no++;
            }
            
            $study_no++;
        }

        $docdata = ilStudyDocData::_get($this->user->getId());
        foreach ($docdata as $doc) {
            $values['study_doc_prog'] = $doc->prog_id;
            $values['study_doc_approval_date'] = $doc->prog_approval;
        }

        return $values;
    }
    
    
    /**
     * set the study data of the user
     */
    private function checkAndSetValues()
    {
        $studydata = array();
        for ($study_no = 1; $study_no <= 3; $study_no++) {
            $study = new ilStudyCourseData;
            $study->user_id = $this->user->getId();
            $study->study_no = $study_no;
            $ref_semester = $this->form->getInput('study' . $study_no . '_ref_semester');
            $study->ref_semester = (empty($ref_semester) ? null : $ref_semester);
            $degree_id = $this->form->getInput('study' . $study_no . '_degree_id');
            $study->degree_id = ($degree_id < 0 ? null : $degree_id);
            $school_id = $this->form->getInput('study' . $study_no . '_school_id');
            $study->school_id = ($school_id < 0 ? null : $school_id);
            $study->study_type = $this->form->getInput('study' . $study_no . '_study_type');
            $study->subjects = [];

            for ($subject_no = 1; $subject_no <= 3; $subject_no++) {
                $subject = new ilStudyCourseSubject;
                $subject->study_no = $study_no;
                $subject->user_id = $this->user->getId();
                $subject_id = $this->form->getInput('study' . $study_no . '_subject' . $subject_no . '_subject_id');
                $subject->subject_id = ($subject_id < 0 ? null : $subject_id);
                $semester = $this->form->getInput('study' . $study_no . '_subject' . $subject_no . '_semester');
                $subject->semester = (empty($semester) ? null : $semester);
                
                if (isset($subject->subject_id) and isset($subject->semester)) {
                    // all set: add
                    $study->subjects[] = $subject;
                } elseif (isset($subject->subject_id) or isset($subject->semester)) {
                    // one not set: reject
                    ilUtil::sendFailure($this->lng->txt("studydata_msg_incomplete"), false);
                    return false;
                }
            }
            
            if (isset($study->ref_semester) and isset($study->degree_id) and isset($study->school_id) and !empty($study->subjects)) {
                // all set: add
                $studydata[] = $study;
            } elseif (isset($study->ref_semester) or isset($study->degree_id) or isset($study->school_id) or !empty($study->subjects)) {
                // one not set: reject
                ilUtil::sendFailure($this->lng->txt("studydata_msg_incomplete"), false);
                return false;
            }
        }

        /** @var ilSelectInputGUI $item */
        $this->form->setValuesByPost();
        $item = $this->form->getItemByPostVar('study_doc_prog');
        $prog_id = $item->getValue();

        /** @var ilDateTimeInputGUI $item */
        $item = $this->form->getItemByPostVar('study_doc_approval_date');
        $prog_approval = $item->getDate();

        if ($prog_id >= 0 or isset($prog_approval)) {
            $doc = new ilStudyDocData();
            $doc->user_id = $this->user->getId();
            $doc->prog_id = ($prog_id < 0 ? null : $prog_id);
            $doc->prog_approval = $prog_approval;
        }

        ilStudyCourseData::_delete($this->user->getId());
        ilStudyDocData::_delete(($this->user->getId()));

        foreach ($studydata as $study) {
            $study->write();
        }
        if (isset($doc)) {
            $doc->write();
        }

        $this->user->setMatriculation($this->form->getInput("matriculation"));
        $this->user->update();
        return true;
    }
}
