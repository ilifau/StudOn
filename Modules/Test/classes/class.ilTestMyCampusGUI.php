<?php
// fau: campusGrades - new class ilTestMyCampusGUI.

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once "./Modules/Test/classes/class.ilTestServiceGUI.php";
include_once "./Modules/Test/classes/class.ilTestMyCampusTools.php";

/**
* Class for my campus export of test results.
*
* @ilCtrl_isCalledBy ilTestMyCampusGUI: ilObjTestGUI
* @extends ilTestServiceGUI
*/
class ilTestMyCampusGUI extends ilTestServiceGUI
{
    /**
     * Tools object
     *
     * @var object
     */
    public $tools = null;

    
    /**
    * ilTestMyCampusGUI constructor
    *
    * The constructor takes the test object reference as parameter
    *
    * @param ilObjTest $a_object
    * @access public
    */
    public function __construct(ilObjTest $a_object)
    {
        parent::__construct($a_object);
        $this->tools = new ilTestMyCampusTools($a_object);
    }
    

    /**
    * execute command
    */
    public function &executeCommand()
    {
        global $ilCtrl, $ilAccess, $lng;
            
        // check access rights
        if (!$ilAccess->checkAccess("write", "", $this->object->getRefId())
                and !$ilAccess->checkAccess("write", "", $this->object->getRefId())) {
            ilUtil::sendInfo($lng->txt("cannot_edit_test"), true);
            $ilCtrl->redirectByClass("ilobjtestgui", "infoScreen");
        }
        
        if (!ilCust::extendedUserDataAccess()) {
            ilUtil::sendInfo(sprintf($lng->txt("ass_mycampus_export_forbidden"), "goto.php?target=studon_exportrequest"), true);
            $ilCtrl->redirectByClass('iltestexportgui');
        }
        
        // handle the command
        $cmd = $ilCtrl->getCmd();
        $next_class = $ilCtrl->getNextClass($this);

        if (strlen($cmd) == 0) {
            return $this->showExportForm();
        }
        $cmd = $this->getCommand($cmd);
        switch ($next_class) {
            default:
                $ret = &$this->$cmd();
                break;
        }
        return $ret;
    }
    
    /**
     * initialize the export form
     *
     * @return object	property form
     */
    public function initExportForm()
    {
        global $ilCtrl, $lng;
        
        include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
        $form = new ilPropertyFormGUI();
        $form->setFormAction($ilCtrl->getFormAction($this, "submitExportForm"));
        $form->setTitle($lng->txt("ass_mycampus_export"));

        $group = new ilRadioGroupInputGUI($lng->txt('ass_mycampus_export_scope'), 'export_scope');
        $group->setRequired(true);

        $option = new ilRadioOption($lng->txt('ass_mycampus_export_all'), 'all');
        $option->setInfo($lng->txt('ass_mycampus_export_all_info'));
        // exam number
        $item = new ilNumberInputGUI($lng->txt('ass_mycampus_exam_number'), 'exam_number');
        $item->setInfo($lng->txt('ass_mycampus_exam_number_info'));
        $item->setSize(10);
        $item->setRequired(true);
        $option->addSubItem($item);
        $group->addOption($option);

        $option = new ilRadioOption($lng->txt('ass_mycampus_export_registered'), 'filter');
        $option->setInfo($lng->txt('ass_mycampus_export_registered_info'));
        // registration file
        include_once("./Services/Form/classes/class.ilFileInputGUI.php");
        $item = new ilFileInputGUI($lng->txt("ass_mycampus_registrations_file"), "registrations_file");
        $item->setInfo($lng->txt("ass_mycampus_registrations_file_info"));
        $item->setRequired(true);
        $item->setSuffixes(array("csv"));
        $option->addSubItem($item);
        $group->addOption($option);

        $form->addItem($group);


        // rating type
        $group = new ilRadioGroupInputGUI($lng->txt('ass_mycampus_rating_type'), 'rating_type');
        $group->setRequired(true);
        
        $option = new ilRadioOption($lng->txt('ass_mycampus_not_marked'), 'not_marked');
        $option->setInfo($lng->txt('ass_mycampus_not_marked_info'));
        $group->addOption($option);
        
        $option = new ilRadioOption($lng->txt('ass_mycampus_marked'), 'marked');
        $option->setInfo($lng->txt('ass_mycampus_marked_info'));

        // mark field
        $subgroup = new ilRadioGroupInputGUI($lng->txt('ass_mycampus_mark_field'), 'mark_field');
        $subgroup->setValue('short');
        $suboption = new ilRadioOption($lng->txt('ass_mycampus_mark_field_short'), 'short');
        $subgroup->addOption($suboption);
        $suboption = new ilRadioOption($lng->txt('ass_mycampus_mark_field_official'), 'official');
        $subgroup->addOption($suboption);
        
        $option->addSubItem($subgroup);
        $group->addOption($option);
        $form->addItem($group);

        // other fields
        $group = new ilCheckboxGroupInputGUI($lng->txt('ass_mycampus_other_fields'), 'other_fields');
        $group->setInfo($lng->txt('ass_mycampus_other_fields_info'));
        $option = new ilCheckboxOption($lng->txt('firstname'), 'firstname');
        $group->addOption($option);
        $option = new ilCheckboxOption($lng->txt('lastname'), 'lastname');
        $group->addOption($option);
        $option = new ilCheckboxOption($lng->txt('ass_mycampus_test_date'), 'date');
        $group->addOption($option);
        $option = new ilCheckboxOption($lng->txt('ass_mycampus_test_starttime'), 'starttime');
        $group->addOption($option);
        $form->addItem($group);

        $form->addCommandButton("submitExportForm", $this->lng->txt("create_export_file"));
        $form->addCommandButton("cancel", $this->lng->txt("cancel"));
        
        return $form;
    }
    
    /**
     * show the export form with the last saved options for this test
     */
    public function showExportForm()
    {
        // get the saved export options
        $values = array();
        $values['exam_number'] = $this->tools->getOption('exam_number');
        $values['rating_type'] = $this->tools->getOption('rating_type');
        $values['mark_field'] = $this->tools->getOption('mark_field');
        $values['other_fields'] = explode(',', $this->tools->getOption('other_fields'));

        $form = $this->initExportForm();
        $form->setValuesByArray($values);
                
        $this->tpl->setVariable("ADM_CONTENT", $form->getHTML());
    }
    
    /**
     * submit the export form to create the export file
     */
    public function submitExportForm()
    {
        global $ilCtrl, $lng;
        
        $form = $this->initExportForm();
        if (!$form->checkInput()) {
            $form->setValuesByPost();
            $this->tpl->setVariable("ADM_CONTENT", $form->getHTML());
            return;
        }

        if (!empty($_FILES["registrations_file"]["name"])) {
            $matches = array();
            $result = preg_match('/^prf_([0-9]+)\.csv$/', $_FILES["registrations_file"]["name"], $matches);
            $mess = $result
                    ? $this->tools->checkRegistrationsFile($_FILES["registrations_file"]["tmp_name"])
                    : $lng->txt('ass_mycampus_registrations_file_wrong_named');

            if (!empty($mess)) {
                ilUtil::sendFailure($mess, false);
                $form->setValuesByPost();
                $this->tpl->setVariable("ADM_CONTENT", $form->getHTML());
                return;
            }

            $exam_number = $matches[1];
        } else {
            $exam_number = $form->getInput('exam_number');
        }

        $this->tools->setOption('exam_number', $exam_number);
        $this->tools->setOption('rating_type', $form->getInput('rating_type'));
        $this->tools->setOption('mark_field', $form->getInput('mark_field'));
        $this->tools->setOption('other_fields', implode(',', (array) $form->getInput('other_fields')));
        $this->tools->writeOptions();
        $mess = $this->tools->createExportFiles();
        if ($mess) {
            ilUtil::sendInfo($mess, true);
        }
        ilUtil::sendSuccess($lng->txt("ass_mycampus_file_written"), true);
        $ilCtrl->redirectByClass('iltestexportgui');
    }
    
    /**
     * cancel the export form
     */
    public function cancel()
    {
        global $ilCtrl;
        $ilCtrl->redirectByClass('iltestexportgui');
    }
}
