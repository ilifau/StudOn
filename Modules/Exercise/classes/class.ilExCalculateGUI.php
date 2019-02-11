<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once "./Modules/Exercise/classes/class.ilExCalculate.php";

/**
 * fau: exManCalc new GUI class for result calculations.
 *
 * @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
 * 
 * @ilCtrl_IsCalledBy ilExCalculateGUI: ilExerciseManagementGUI
 *
 * @ingroup ModulesExercise
 */
class ilExCalculateGUI
{
	/**
	 * Constructor
	 */
	function __construct($a_object)
	{
		global $tpl;

		$this->object = $a_object;
		$this->calc = new ilExCalculate($this->object);
		
		$this->tpl = $tpl;
	}

	
	/**
	 * Main entry
	 */
	function &executeCommand()
	{
  		global $ilCtrl;
  		
		$next_class = $ilCtrl->getNextClass($this);
		$cmd = $ilCtrl->getCmd("showForm");
  
  		switch($next_class)
		{
  	  		default:
  	  		{
				$this->$cmd();		
				break;
			}
		}
  		return true;
	}
	
	/**
	 * show the export form with the last saved options for this test
	 */
	function showForm()
	{
		$form = $this->initForm();
		$this->readFormValues($form);				
		$this->tpl->setContent($form->getHTML());
		
	}
	
	/**
	 * Seave the settings
	 */
	function saveSettings()
	{
		$this->processForm(false);	
	}
	
	/**
	 * save settings and calculate the result
	 */
	function calculateResults()
	{
		$this->processForm(true);
	}

	/**
	 * cancel the calculation
	 */
	function cancel()
	{
		global $ilCtrl;
		$ilCtrl->returnToParent($this);
	}
	
	/**
	 * initialize the export form
	 * 
	 * @return object	property form
	 */
	protected function initForm()
	{
		global $ilCtrl, $lng;
		
		include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
		$form->setFormAction($ilCtrl->getFormAction($this, "submitExportForm"));
		$form->setTitle($lng->txt("exc_calculate_overall_results"));
		
		// calculate selection
		$group = new ilRadioGroupInputGUI($lng->txt('exc_calc_mark_select'), 'mark_select');
		$group->setInfo($lng->txt('exc_calc_mark_select_info'));
		$group->setRequired(true);

		$option = new ilRadioOption($lng->txt('exc_calc_mark_select_marked'), 'marked');
		$group->addOption($option);

		$option = new ilRadioOption($lng->txt('exc_calc_mark_select_mandatory'), 'mandatory');
		$group->addOption($option);

		$option = new ilRadioOption($lng->txt('exc_calc_mark_select_number'), 'number');
		
			// order
			$subitem = new ilSelectInputGUI($lng->txt('exc_calc_mark_select_order'), 'mark_select_order');
			$subitem->setRequired(true);
			$subitem->setOptions(array(	
				'highest' => $lng->txt('exc_calc_mark_select_highest'),
				'lowest' => $lng->txt('exc_calc_mark_select_lowest')
			));
			$option->addSubItem($subitem);
			
			// number
			$subitem = new ilNumberInputGUI($lng->txt('exc_calc_mark_select_count'), 'mark_select_count');
			$subitem->setInfo($lng->txt('exc_calc_mark_select_count_info'));
			$subitem->setSize(2);
			$subitem->setMinValue(1);
			$subitem->setDecimals(0);
			$subitem->setRequired(true);
			$option->addSubItem($subitem);
						
		$group->addOption($option);
		$form->addItem($group);

		// calculate function
		$group = new ilRadioGroupInputGUI($lng->txt('exc_calc_mark_function'), 'mark_function');
		$group->setRequired(true);

		$option = new ilRadioOption($lng->txt('exc_calc_mark_function_sum'), 'sum');
		$option->setInfo($lng->txt('exc_calc_mark_function_sum_info'));
		$group->addOption($option);
		
		$option = new ilRadioOption($lng->txt('exc_calc_mark_function_average'), 'average');
		$option->setInfo($lng->txt('exc_calc_mark_function_average_info'));
		$group->addOption($option);
		
		$form->addItem($group);	
		
		// status calculation
		$item = new ilCheckboxInputGUI($lng->txt('exc_calc_status'), 'status_calculate');
		$item->setInfo($lng->txt('exc_calc_status_info'));

			// order
			$subitem = new ilSelectInputGUI($lng->txt('exc_calc_status_compare'), 'status_compare');
			$subitem->setRequired(true);
			$subitem->setOptions(array(	
				'lower' => $lng->txt('exc_calc_status_compare_lower'),
				'higher' => $lng->txt('exc_calc_status_compare_higher'),
				'lower_equal' => $lng->txt('exc_calc_status_compare_lower_equal'),
				'higher_equal' => $lng->txt('exc_calc_status_compare_higher_equal')
			));
			$item->addSubItem($subitem);
		
			// number
			$subitem = new ilNumberInputGUI($lng->txt('exc_calc_status_compare_value'), 'status_compare_value');
			$subitem->setInfo($lng->txt('exc_calc_status_compare_info'));
			$subitem->setSize(5);
			$subitem->setRequired(true);
			$item->addSubItem($subitem);
			
			// default value
			$subitem = new ilSelectInputGUI($lng->txt('exc_calc_status_default'), 'status_default');
			$subitem->setRequired(true);
			$subitem->setInfo($lng->txt('exc_calc_status_default_info'));
			$subitem->setOptions(array(	
				'notgraded' => $lng->txt('exc_notgraded'),
				'passed' => $lng->txt('exc_passed'),
				'failed' => $lng->txt('exc_failed')
			));
			$item->addSubItem($subitem);
							
		$form->addItem($item);
		
		
		$form->addCommandButton("saveSettings", $lng->txt("save_settings"));	
		$form->addCommandButton("calculateResults", $lng->txt("exc_calc_calculate"));	
		$form->addCommandButton("cancel", $lng->txt("cancel"));	

		return $form;
	}
	
	
	/**
	 * process the form 
	 * 
	 * @param	boolean		calculate Results
	 */
	protected function processForm($a_calculate = false)
	{
		global $ilCtrl, $lng;

		/** @var ilPropertyFormGUI $form */
		$form = $this->initForm();
		$form->setDisableStandardMessage(true);

		// standard checks
		$ok = $form->checkInput();
	
		// check for mandatory assignments
		if ($form->getInput('mark_select') == 'number' and $form->getInput('mark_select_count') != '')
		{	
			include_once "./Modules/Exercise/classes/class.ilExAssignment.php";
			$mandatory = ilExAssignment::countMandatory($this->object->getId());		
			if ($form->getInput('mark_select_count') < $mandatory)
			{
				$ok = false;
				$item = $form->getItemByPostVar('mark_select_count');
				$item->setAlert(sprintf($lng->txt('exc_calc_mark_select_count_message'),$mandatory));
			}
		}
		
		if ($ok)
		{
			$this->saveFormValues($form);
			
			if ($a_calculate)
			{
				$this->calc->calculateResults();
				ilUtil::sendSuccess($lng->txt("exc_calc_all_calculated"), true);			
				$ilCtrl->returnToParent($this);
			}
			else
			{
				ilUtil::sendInfo($lng->txt("settings_saved"), true);			
				$ilCtrl->returnToParent($this);
			}
		}
		else
		{
			ilUtil::sendFailure($lng->txt("form_input_not_valid"));			
			$form->setValuesByPost();
			$this->tpl->setContent($form->getHTML());
		}
	}
		
	
	/**
	 * read the form values
	 * 
	 * @param object	form
	 */
	protected function readFormValues($form)
	{
		$values = array();
		$values['mark_function'] = $this->calc->getOption('mark_function');
		$values['mark_select'] = $this->calc->getOption('mark_select');
		$values['mark_select_order'] = $this->calc->getOption('mark_select_order');
		$values['mark_select_count'] = $this->calc->getOption('mark_select_count');
		$values['status_calculate'] = $this->calc->getOption('status_calculate');
		$values['status_compare'] = $this->calc->getOption('status_compare');
		$values['status_compare_value'] = $this->calc->getOption('status_compare_value');
		$values['status_default'] = $this->calc->getOption('status_default');
		
		$form->setValuesByArray($values);
	}
	
	
	/**
	 * save the form values 
	 * 
	 * @param object	form
	 */
	protected function saveFormValues($form)
	{
		$this->calc->setOption('mark_function', $form->getInput('mark_function'));
		$this->calc->setOption('mark_select', $form->getInput('mark_select'));
		$this->calc->setOption('mark_select_order', $form->getInput('mark_select_order'));
		$this->calc->setOption('mark_select_count', $form->getInput('mark_select_count'));
		$this->calc->setOption('status_calculate', $form->getInput('status_calculate'));
		$this->calc->setOption('status_compare', $form->getInput('status_compare'));
		$this->calc->setOption('status_compare_value', $form->getInput('status_compare_value'));
		$this->calc->setOption('status_default', $form->getInput('status_default'));
		$this->calc->writeOptions();
	}
	
}
