<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */
 
/**
* fim: [univis] generic GUI for wizards
*
* @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
* @version $Id: $
*
*/
class ilWizardGUI
{
	/**
	* General wizard mode
	* (defined by setMode)
	*/
	protected $mode = 'general';


	/**
	* Currently execured command 
	* (set in executeCommand)
	*/
	protected $cmd = '';


	/**
	* Data of currently visible step
	* (set in executeCommand, depending from mode and command)
	*/
	protected $step = array();


	/**
	* Data of all steps defined by the mode
	* (defined by setMode)
	*/
	protected $steps = array();


	/**
	* Data of the current wizard mode
	* (defined by setMode)
	*/
	protected $mode_data = array();

	/**
	* Definition of all wizard modes
	* (pre-defined in an actual wizard)
	*
	* This should be overwritten in the derived classes.
	* The entries are only examples!
	*/
	protected $mode_definitions = array (

		'general' => array (
			'title_var' => 'general_title',                     // lang var of main title
			'steps' 	=> array (                              // list if all visible steps
		        array (
						'cmd' => 'showSearchForm',             	// step command
						'title_var' => 'general_search_title',  // lang var for title
						'desc_var' => 'general_search_desc',    // lang var for description
						'prev_cmd' => '',                       // command of previous step
						'next_cmd' => 'submitSearchForm'        // command of next step
				),
		        array (
						'cmd' => 'showSelection',
						'title_var' => 'general_selection_title',
						'desc_var' => 'general_selection_desc',
						'prev_cmd' => 'showSearchForm',
						'next_cmd' => 'submitSelection'
				)
			)
		)
 	);


	/**
	* Constructor
	* @access public
	*/
	function __construct($a_parent_gui)
	{
		global $lng, $tpl, $ilCtrl;

		$this->tpl =& $tpl;
		$this->lng =& $lng;
		$this->cmd = '';

		$this->parent_gui = $a_parent_gui;
		$this->parent_obj_id = $this->parent_gui->object->getId();
		$this->parent_ref_id = $this->parent_gui->object->getRefId();
		$this->parent_type = $this->parent_gui->object->getType();

		// class values stored in user session
		require_once('./Services/Utilities/classes/class.ilSessionValues.php');
		$this->values = new ilSessionValues(get_class($this));

		// init mode as saved in session
		$this->setMode();
	}


	/**
	* Set the wizard mode and depending steps
	*
	* The mode should once be set in a start function of the wizard
	* Afterwards it is read from the session in the class constructor
	*
	* @param    string  	mode
	*/
	protected function setMode($a_mode = '')
	{
	    // determine the current mode (new or saved)
	    if ($a_mode)
  		{
			$this->mode = $a_mode;
			$this->values->setSessionValue('common','mode',$a_mode);
		}
		else
		{
			$this->mode = $this->values->getSessionValue('common','mode');
		}


		// set data and steps defined by the mode
		$this->mode_data = $this->mode_definitions[$this->mode];

		if (is_array($this->mode_data['steps']))
		{
			$this->steps = $this->mode_data['steps'];
		}
		else
		{
	        $this->steps = array();
	    }
	}


	/**
	* Execute a command (main entry point)
	* @param 	string      specific command to be executed (or empty)
	* @access 	public
	*/
	function &executeCommand($a_cmd = '')
	{
	    global $ilCtrl;

		// get the current command
		$cmd = $a_cmd ? $a_cmd : $ilCtrl->getCmd('returnToParent');

		if ($pos = strpos($cmd, ':'))
		{
			// specific handling of parameters added to command
			// needed for navigation in called table gui
	        $params = substr($cmd, $pos + 1);
	        $cmd = substr($cmd, 0, $pos);
			$this->cmd = $cmd;
			$this->step = $this->getStepByCommand($cmd);
			return $this->$cmd($params);
		}
		else
		{
	        // simple command
			$this->cmd = $cmd;
			$this->step = $this->getStepByCommand($cmd);
			return $this->$cmd();
		}
	}


	/**
	* Get a step by command
	*
	* commands without visible steps will return an ampty array
	*
	* @param    string  	command
	* @return   array       step
	*/
	protected function getStepByCommand($a_cmd)
	{
	    foreach ($this->steps as $step)
		{
	        if ($step['cmd'] == $a_cmd)
			{
	            return $step;
	        }
		}
		return array();
	}

	/**
	* Get the form name used for the wizard
	*/
	protected function getFormName()
	{
		return get_class($this);
	}


	/**
	* Show the wizard content
	*/
	protected function output($a_html = '')
	{
	    global $ilCtrl;

		// show step list and determine the current step number
		if (count($this->steps) > 1)
		{
			$tpl = new ilTemplate("tpl.wizard_steps.html", true, true, "Services/Wizard");

			for ($i = 0; $i < count($this->steps); $i++)
			{
				if ($this->steps[$i]['cmd'] == $this->cmd)
				{
					$tpl->setCurrentBlock('strong');
					$stepnum = $i + 1;
		        }
				else
				{
					$tpl->setCurrentBlock('normal');
		        }
		  		$tpl->setVariable("TITLE", $this->lng->txt($this->steps[$i]['title_var']));
				$tpl->setVariable("STEP", sprintf($this->lng->txt("wizard_step"),$i + 1));
				$tpl->parseCurrentBlock();
				$tpl->setCurrentBlock("stepline");
				$tpl->parseCurrentBlock();
			}
	        $this->tpl->setRightContent($tpl->get());
		}
		else
		{
	        $this->tpl->setRightContent('&nbsp;');
	        $stepnum = 1;
	    }

		// show the main screen
		$tpl = new ilTemplate("tpl.wizard_page.html", true, true, "Services/Wizard");
		$tpl->setVariable("MAIN_TITLE", $this->lng->txt($this->mode_data['title_var']));
		$tpl->setVariable("FORMACTION", $ilCtrl->getFormAction($this));
		$tpl->setVariable("FORMNAME", $this->getFormName());
  		$tpl->setVariable("CONTENT", $a_html);

		// add step specific info and toolbar
		if ($this->step['cmd'])
		{
			$tpl->setVariable("STEP", sprintf($this->lng->txt("wizard_step"),$stepnum));
			$tpl->setVariable("DESCRIPTION", $this->lng->txt($this->step['desc_var']));

			require_once("./Services/UIComponent/Toolbar/classes/class.ilToolbarGUI.php");
			$tb = new ilToolbarGUI();

			if ($this->step['prev_cmd'])
			{
				$tb->addFormButton(sprintf($this->lng->txt('wizard_previous'),$stepnum - 1), $this->step['prev_cmd']);
			}
			if ($this->step['next_cmd'] and $stepnum == count($this->steps))
			{
				$tb->addFormButton($this->lng->txt('wizard_finish'), $this->step['next_cmd']);
			}
			elseif ($this->step['next_cmd'])
			{
				$tb->addFormButton(sprintf($this->lng->txt('wizard_next'),$stepnum + 1), $this->step['next_cmd']);
	        }

			$tb->addSeparator();
			$tb->addFormButton($this->lng->txt('cancel'), 'returnToParent');

			$tpl->setVariable("TOOLBAR",$tb->getHTML());
		}
		$tpl->parse();

		$this->tpl->setContent($tpl->get());
		return true;
	}


	/**
	* Return to the parent GUI
	*/
	protected function returnToParent()
	{
	    global $ilCtrl;
		$ilCtrl->returnToParent($this);
	}
}
?>
