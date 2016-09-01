<?php
/* fim: [studydata] new class. */

/* Copyright (c) 1998-2014 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* Class ilStudyDataGUI
*
* @author Fred Neumann <fred.neumann@fim.uni-erlangen.de> 
*
* @ilCtrl_Calls ilStudyDataGUI:
* 
*/
require_once "Services/StudyData/classes/class.ilStudyData.php";

class ilStudyDataGUI
{
	
	/**
	 * @var	object	User object
	 */
	var $user = null;
	
	/**
	 * @var object	Form object
	 */
	var $form = null;

	
	/**
	 * Constructor
	 */
	public function __construct($a_user)
	{
		global $ilCtrl,$tpl,$lng;
		
		$this->tpl = $tpl;
		$this->ctrl = $ilCtrl;
		$this->lng = $lng;
		$this->lng->loadLanguageModule('registration');
		
		$this->user = $a_user;
	}
	

	/**
	 * Command execution
	 */
	public function executeCommand()
	{
		global $ilias, $rbacsystem;
		if(!$rbacsystem->checkAccess("write", USER_FOLDER_ID))
		{
			$ilias->raiseError("You are not entitled to access this page!",$ilias->error_obj->WARNING);
		}

		$cmd = $this->ctrl->getCmd("edit");
		switch ($cmd)
		{
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
	private function editObject()
	{
		$this->initForm();
		$this->getValues();
		$this->tpl->setContent($this->form->getHtml());
	}
	
	/**
	 * Save the form data
	 */
	private function updateObject()
	{
		$this->initForm();

		if ($this->form->checkInput() and $this->checkAndSetValues())
		{
			ilUtil::sendSuccess($this->lng->txt("msg_obj_modified"), true);
			$this->ctrl->redirect($this, "edit");
		}
		else
		{
			$this->form->setValuesByPost();
			$this->tpl->setContent($this->form->getHtml());
		}	
	}
	
	
	/**
	 * Init the data form
	 */
	private function initForm()
	{
		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$this->form = new ilPropertyFormGUI();
		$this->form->setTitle($this->lng->txt("studydata_edit"));
		
		// matriculation
		$item = new ilTextInputGUI($this->lng->txt("matriculation"), "matriculation");
		$item->setRequired(true);
		$this->form->addItem($item);
		
		// three studies
		for ($study_no = 1; $study_no <= 3; $study_no++)
		{
			// title
			$item = new ilFormSectionHeaderGUI;
			$item->setTitle(sprintf($this->lng->txt("studydata_study_no"),$study_no));
			$this->form->addItem($item);
			
			// ref semester
			$item = new ilSelectInputGUI($this->lng->txt('studydata_ref_semester'), 'study'.$study_no.'_ref_semester');
			$item->setOptions(ilStudyData::_getSemesterSelectOptions());
			$this->form->addItem($item);
			
			// degree
			$item = new ilSelectInputGUI($this->lng->txt('studydata_degree'), 'study'.$study_no.'_degree_id');
			$item->setOptions(ilStudyData::_getDegreeSelectOptions());
			$this->form->addItem($item);
			
			// school
			$item = new ilSelectInputGUI($this->lng->txt('studydata_school'), 'study'.$study_no.'_school_id');
			$item->setOptions(ilStudyData::_getSchoolSelectOptions());
			$this->form->addItem($item);
			
			for ($subject_no = 1; $subject_no <= 3; $subject_no++)
			{
				// subject
				$item = new ilSelectInputGUI(sprintf($this->lng->txt('studydata_subject_no'),$subject_no), 
											'study'.$study_no.'_subject'.$subject_no.'_subject_id');
				$item->setOptions(ilStudyData::_getsubjectSelectOptions());
				$this->form->addItem($item);
				
				// semester
				$item = new ilNumberInputGUI(sprintf($this->lng->txt('studydata_semester_subject_no'),$subject_no), 
											'study'.$study_no.'_subject'.$subject_no.'_semester');
				$item->setDecimals(0);
				$item->setMinValue(1);
				$item->setSize(2);
				$this->form->addItem($item);
			}
		}

		$this->form->addCommandButton("update", $this->lng->txt("update"));
		$this->form->setFormAction($this->ctrl->getFormAction($this));
	}
	
	
	/**
	 * get the stored study data
	 */
	private function getValues()
	{
		$data = ilStudyData::_readStudyData($this->user->getId());
		$values = array();
		$values['matriculation'] = $this->user->getMatriculation();
		$study_no = 1;
		foreach($data as $study)
		{
			$values['study'.$study_no.'_ref_semester'] = $study['ref_semester'];
			$values['study'.$study_no.'_degree_id'] = $study['degree_id'];
			$values['study'.$study_no.'_school_id'] = $study['school_id'];
			
			$subject_no = 1;
			foreach($study['subjects'] as $subject)
			{
				$values['study'.$study_no.'_subject'.$subject_no.'_subject_id'] = $subject['subject_id'];
				$values['study'.$study_no.'_subject'.$subject_no.'_semester'] = $subject['semester'];
				$subject_no++;
			}
			
			$study_no++;
		}

		$this->form->setValuesByArray($values);
	}
	
	
	/**
	 * set the study data of the user
	 */
	private function checkAndSetValues()
	{
		$studydata = array();
		for ($study_no = 1; $study_no <= 3; $study_no++)
		{
			$study = array();
			$study['ref_semester'] = $this->form->getInput('study'.$study_no.'_ref_semester');
			$study['degree_id'] = $this->form->getInput('study'.$study_no.'_degree_id');
			$study['school_id'] = $this->form->getInput('study'.$study_no.'_school_id');
			
			for ($subject_no = 1; $subject_no <= 3; $subject_no++)
			{
				$subject = array();
				$subject['subject_id'] = $this->form->getInput('study'.$study_no.'_subject'.$subject_no.'_subject_id');
				$subject['semester'] = $this->form->getInput('study'.$study_no.'_subject'.$subject_no.'_semester');
				
				if ($subject['subject_id'] > 0 and $subject['semester'] > 0)
				{
					$study['subjects'][] = $subject;
				}
				elseif ($subject['subject_id'] > 0 or $subject['semester'] > 0)
				{
					ilUtil::sendFailure($this->lng->txt("studydata_msg_incomplete"), false);
					return false;
				}
			}
			
			if (!empty($study['ref_semester']) and $study['degree_id'] > 0 and $study['school_id'] >= 0 and !empty($study['subjects']))
			{
				$studydata[] = $study;
			}
			elseif (!empty($study['ref_semester']) or $study['degree_id'] > 0  or $study['school_id'] >= 0 or !empty($study['subjects']))
			{
				ilUtil::sendFailure($this->lng->txt("studydata_msg_incomplete"), false);
				return false;				
			}
		}
		
		print_r($studydata);
		
		ilStudyData::_saveStudyData($this->user->getId(), $studydata);
		$this->user->setMatriculation($this->form->getInput("matriculation"));
		$this->user->update();	
		return true;
	}
}