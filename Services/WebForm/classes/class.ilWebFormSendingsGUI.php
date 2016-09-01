<?php
/* fim: [webform] new class. */

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once "./Services/WebForm/classes/class.ilWebFormAccess.php";
include_once "./Services/WebForm/classes/class.ilWebFormUtils.php";

/**
* Class ilWebFormSendingsGUI
*
* @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
* @version $Id: $
*
* @ilCtrl_Calls ilWebFormSendingsGUI:
*
* @package webform
*/

class ilWebFormSendingsGUI
{
	/**
	 * Tutor mode: 
	 * sent forms of other users are shown
	 * forms can be set as checked / unchecked
	 * @var boolean
	 */
	private $tutor_mode = false;
	
	/**
	 * Root mode
	 * unsent forms or other users are shown
	 * forms can be set as sent
	 * @var boolean
	 */
	private $root_mode = false;
	
	private $forms_list = array();
	private $users_list = array();

	private $filter_checked;
	private $filter_form_id;
	private $filter_user_id;
	
	
	/**
	* Constructor
	* @access public
	*/
	function ilWebFormSendingsGUI($a_parent_gui)
	{
		global $ilCtrl, $tpl, $lng, $ilAccess;

		$this->ctrl = $ilCtrl;
		$this->tpl = $tpl;
		$this->lng = $lng;
		$this->lng->loadLanguageModule("webform");
		$this->parent_gui = $a_parent_gui;
		$this->parent_obj_id = $this->parent_gui->object->getId();
		$this->parent_ref_id = $this->parent_gui->object->getRefId();
		
		// determine the tutor and root modes
		$this->tutor_mode = ilWebFormAccess::_checkEditSendings($this->parent_ref_id);
		$this->root_mode = ilWebFormAccess::_checkViewAllSavings($this->parent_ref_id);
		
		// get the list for filtering
		$this->forms_list = ilWebformUtils::_getCourseFormsList($this->parent_ref_id);
		if ($this->tutor_mode)
		{
			$this->users_list = ilWebformUtils::_getCourseUsersList($this->parent_obj_id);
		}

		// initialize the session array to store the filter values
		$index = "webform_sendings_". $this->parent_ref_id;
		if (!is_array($_SESSION[$index]))
		{
			$_SESSION[$index] = array();
		}
		$this->session_params =& $_SESSION[$index];

		// read the last filter settings
		$this->filter_checked = $this->session_params['filter_checked'];
		$this->filter_form_id = $this->session_params['filter_form_id'];
		$this->filter_user_id = $this->session_params['filter_user_id'];

		// check the filter settings
		$this->checkFilter();
	}

	/**
	* Execute a command (main entry point)
	* @access public
	*/
	public function executeCommand()
	{
		global $ilAccess, $ilErr;

		$cmd = $this->ctrl->getCmd("view");
		$this->$cmd();

		return true;
	}

	/**
	* List the form sendings
	* @access private
	*/
	private function view()
	{
		global $ilUser;
		
		// select the forms to display
		if ($this->filter_form_id)
		{
			$form_ids = array($this->filter_form_id);
		}
		else
		{
			$form_ids = array_keys($this->forms_list);
		}
		
		// select the user sendings to display
		if (!$this->tutor_mode)
		{
			$user_ids = array($ilUser->getId());
		}
		elseif ($this->filter_user_id)
		{
			$user_ids = array($this->filter_user_id);
		}
		else
		{
			$user_ids = array_keys($this->users_list);
		}

		// build the table of form definitions
		include_once 'Services/WebForm/classes/class.ilWebFormSendingsTableGUI.php';
		$table_gui = new ilWebFormSendingsTableGUI(
							$this, "view", $this->tutor_mode, $this->root_mode,
							$this->filter_checked, $form_ids, $user_ids);

		// build the page
		$tpl = new ilTemplate("tpl.list_sendings.html", true, true, "Services/WebForm");
		$tpl->setVariable("SENDINGS_FILTER", $this->getFilterHTML());
  		$tpl->setVariable("SENDINGS_TABLE", $table_gui->getHTML());
  		$tpl->parse();

		$this->tpl->setContent($tpl->get());
	}

	/*
	 * set a form as sent
	 */
	private function setSent()
	{
		if (!$this->root_mode)
		{
			ilUtil::sendInfo($this->lng->txt("no_permission"), false);
		}
		else
		{
			include_once "./Services/WebForm/classes/class.ilWebFormSaving.php";
			ilWebFormSaving::_setSent((int) $_GET["save_id"]);
			ilUtil::sendInfo($this->lng->txt("webform_sent_entry"), false);
		}

		// remember the sorting of the sendings table (not nice)
		$this->ctrl->saveParameter($this, "webform_sendings_table_nav");
		$this->view();
	}
	
	/**
	 * set a form as checked
	 */
	private function setChecked()
	{
		if (!$this->tutor_mode)
		{
			ilUtil::sendInfo($this->lng->txt("no_permission"), false);
		}
		else
		{
			include_once "./Services/WebForm/classes/class.ilWebFormSaving.php";
			ilWebFormSaving::_setCorrected((int) $_GET["save_id"]);
			ilUtil::sendInfo($this->lng->txt("webform_corrected_entry"), false);
		}

		// remember the sorting of the sendings table (not nice)
		$this->ctrl->saveParameter($this, "webform_sendings_table_nav");
		$this->view();
	}

	/**
	 * set a form as unchecked
	 */
	private function setUnchecked()
	{
		if (!$this->tutor_mode)
		{
			ilUtil::sendInfo($this->lng->txt("no_permission"), false);
		}
		else
		{
			include_once "./Services/WebForm/classes/class.ilWebFormSaving.php";
			ilWebFormSaving::_setCorrected((int) $_GET["save_id"], false);
			ilUtil::sendInfo($this->lng->txt("webform_uncorrected_entry"), false);
		}

		// remember the sorting of the sendings table (not nice)
		$this->ctrl->saveParameter($this, "webform_sendings_table_nav");
		$this->view();
	}


	/**
	 * apply the selected filter criteria and show the sendings
	 */
	private function applyFilter()
	{
		$this->filter_checked = $_POST["filter_checked"];
		$this->filter_user_id = (int) $_POST["filter_user_id"];
		$this->filter_form_id = (int) $_POST["filter_form_id"];

		$this->checkFilter();
		$this->view();
	}

	/**
	 * check and remember the selected filter criteria
	 */
	private function checkFilter()
	{
		if (!in_array($this->filter_checked, array("true", "false")))
		{
			$this->filter_checked = "";
		}

		if (!$this->users_list[$this->filter_user_id])
		{
			$this->filter_user_id = 0;
		}

		if (!$this->forms_list[$this->filter_form_id])
		{
			$this->filter_form_id = 0;
		}

		$this->session_params['filter_checked'] = $this->filter_checked;
		$this->session_params['filter_form_id'] = $this->filter_form_id;
		$this->session_params['filter_user_id'] = $this->filter_user_id;
	}

	/**
	 * get the filter form
	 */
	private function getFilterHTML()
	{
        include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$this->form_gui = new ilPropertyFormGUI();
		
		$this->form_gui->setFormAction($this->ctrl->getFormAction($this));
		$this->form_gui->setTitle($this->lng->txt("webform_filter"));
        $this->form_gui->addCommandButton("applyFilter", $this->lng->txt("apply_filter"));

		// form
		$options = array(0 => $this->lng->txt("webform_filter_all"));
		foreach ($this->forms_list as $key => $value)
		{
			$options[$key] = $value;
		}
		$item = new ilSelectInputGUI($this->lng->txt("webform_filter_form_id"), "filter_form_id");
		$item->setOptions($options);
		$item->setValue($this->filter_form_id);
		$this->form_gui->addItem($item);

		// checked
		$options = array("" => $this->lng->txt("webform_filter_all"),
						"true" => $this->lng->txt("webform_all_corrected"),
		                "false" => $this->lng->txt("webform_all_not_corrected")
						);
		$item = new ilSelectInputGUI($this->lng->txt("webform_filter_checked"), "filter_checked");
		$item->setOptions($options);
		$item->setValue($this->filter_checked);
		$this->form_gui->addItem($item);

		// user
		if ($this->tutor_mode)
		{
			$options = array(0 => $this->lng->txt("webform_filter_all"));
			foreach ($this->users_list as $key => $value)
			{
				$options[$key] = $value;
			}
			$item = new ilSelectInputGUI($this->lng->txt("webform_filter_user_id"), "filter_user_id");
			$item->setOptions($options);
			$item->setValue($this->filter_user_id);
			$this->form_gui->addItem($item);
		}

		return $this->form_gui->getHtml();
	}
}
?>
