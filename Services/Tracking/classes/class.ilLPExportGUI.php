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
 * @ilCtrl_Calls ilLPExportGUI:
 *
 * @ingroup ServicesTracking
 *
 */
class ilLPExportGUI extends ilLearningProgressBaseGUI
{
    private $form;
    public $tools = null;
    private $export_subdir;

    public function __construct($a_mode, $a_ref_id)
    {
        parent::__construct($a_mode, $a_ref_id);
             
        include_once './Services/Object/classes/class.ilObjectLP.php';
        $this->export_subdir = $this->getObjId();
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
        /*
        $info = $this->obj_lp->getSettingsInfo();
        if ($info) {
            ilUtil::sendInfo($info);
        }*/
        
        // get the saved export options
        $values = array();
        $values['matriculations'] = $this->tools->getOption('matriculations');
        $form = $this->initFormSettings();
        $form->setValuesByArray($values);
        $this->tpl->setContent(
          /*  $this->handleLPUsageInfo() .*/
            $form->getHTML() /*.
            $this->getTableByMode()*/
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
        $form->setFormAction($ilCtrl->getFormAction($this, "submitExportForm"));
        $form->setTitle($lng->txt("track_export"));

        $matr = new ilTextAreaInputGUI($lng->txt("track_lp_matriculation_numbers"), 'matriculations');
        $matr->setInfo($lng->txt('track_lp_matriculation_numbers_info'));
        $form->addItem($matr);

        $form->addCommandButton("submitExportForm", $this->lng->txt("track_create_export_file"));
        $form->addCommandButton("cancel", $this->lng->txt("cancel"));
        
        $this->form = $form;  
        return $this->form;      
    }

    /**
     * submit the export form to create the export file
     */
    public function submitExportForm()
    {
        global $ilCtrl, $lng;
        
        $form = $this->initFormSettings();
        if (!$form->checkInput()) {
            $form->setValuesByPost();
            $this->tpl->setVariable("ADM_CONTENT", $form->getHTML());
            return;
        }
        
        $mess = $this->tools->createExportFile($this->form->getInput('matriculations'), $this->export_subdir);
        if ($mess) {
            ilUtil::sendInfo($mess, true);
        }
        ilUtil::sendSuccess($lng->txt("ass_lp_export_file_written"), true);
        $ilCtrl->redirectByClass('illearningprogressgui');
    }
    
    /**
     * cancel the export form
     */
    public function cancel()
    {
        global $ilCtrl;
        $ilCtrl->redirectByClass('illearningprogressgui');
    }
}
// fau.