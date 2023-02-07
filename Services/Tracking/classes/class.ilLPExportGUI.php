<?php
// fau: LPExport
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once './Services/Tracking/classes/class.ilLearningProgressBaseGUI.php';
include_once "./Services/Tracking/classes/class.ilLPExportTools.php";

/**
 * Class ilLPExportGUI
 *
 * @author Christina Fuchs <christina.fuchs@ili.fau.de>
 *
 * @version $Id$
 *
 */
class ilLPExportGUI extends ilLearningProgressBaseGUI
{
    private $form;
    private $tools = null;

    public function __construct($a_mode, $a_ref_id)
    {
        parent::__construct($a_mode, $a_ref_id);
        $this->tools = new ilLPExportTools($this->obj_id);
    }

    /**
    * execute command
    */
    public function executeCommand()
    {
        switch ($this->ctrl->getNextClass()) {
            default:
                $cmd = $this->__getDefaultCommand();
                $this->$cmd();

        }
        return true;
    }

    /**
     * Show export form
     */
    protected function show()
    {
        global $DIC;

        $ilHelp = $DIC['ilHelp'];

        $ilHelp->setSubScreenId("trac_export");
        $form = $this->initFormSettings();
        $this->tpl->setContent(
            $form->getHTML() 
        );
        $this->form = $form;
    }


    /**
     * Init property form
     *
     * @return ilPropertyFormGUI $form
     */
    protected function initFormSettings()
    {
        global $ilCtrl, $lng;
        
        include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
        $form = new ilPropertyFormGUI();
        $form->setPreventDoubleSubmission(false);

        $form->setFormAction($ilCtrl->getFormAction($this, "submitExportForm"));
        $form->setTitle($lng->txt("trac_export_title"));

        $matr = new ilTextAreaInputGUI($lng->txt("trac_lp_matriculation_numbers"), 'matriculations');
        $matr->setInfo($lng->txt('trac_lp_matriculation_numbers_info'));
        $form->addItem($matr);

        $form->addCommandButton("submitExportForm", $this->lng->txt("trac_create_export_file"));
        
        $this->form = $form;  
        return $this->form;      
    }

    /**
     * submit the export form to create the export file
     */
    private function submitExportForm()
    {
        global $ilCtrl, $lng;
        
        $form = $this->initFormSettings();
        if (!$form->checkInput()) {
            $form->setValuesByPost();
            $this->tpl->setVariable("ADM_CONTENT", $form->getHTML());
            return;
        }

        $success = $this->tools->createExportFile($this->form->getInput('matriculations'));
    }
}
// fau.