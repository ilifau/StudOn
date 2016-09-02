<?php
/* fim: [webform] new class. */

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */


include_once 'Services/Table/classes/class.ilTable2GUI.php';
include_once "./Services/WebForm/classes/class.ilWebForm.php";
include_once "./Services/WebForm/classes/class.ilWebFormSaving.php";

/**
* Class ilWebFormDefinitionsTableGUI
*
* @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
* @version $Id: $
*
* @package webform
*/

class ilWebFormSendingsTableGUI extends ilTable2GUI
{
	var $tutor_mode;
	var $forms = array();

    /**
    * Constructor
    * @param    object  parent gui
    * @param    string  commant of parent gui to show the table
    * @param    boolean  tutor commands are shown
	* @param    boolean  root commands and unsent entries are shown
	* @param    string   form is checked ("true", "false" or "")
	* @param    array    array of form_ids
	* @param    array    array of user_ids
    */
	function __construct($a_parent_obj, $a_parent_cmd, $a_tutor_mode,
	    				$a_root_mode = "", $a_checked = "",
						$a_form_ids = array(), $a_user_ids = array())
    {
        global $ilCtrl, $lng;

        parent::__construct($a_parent_obj, $a_parent_cmd);

		$this->tutor_mode = $a_tutor_mode;
		$this->root_mode = $a_root_mode;
		
        $this->addColumn($lng->txt("username"), "username", "15%");
        $this->addColumn($lng->txt("webform_field_title"), "title", "30%");
        $this->addColumn($lng->txt("webform_send_date"), "senddate", "15%");
        $this->addColumn($lng->txt("webform_check_date"), "checkdate", "15%");
        $this->addColumn($lng->txt("webform_solution"), "solution_ref", "25%");

        $this->setEnableHeader(true);
        $this->setEnableTitle(true);
        $this->setTitle($this->lng->txt("webform_sendings"));
        $this->setFormAction($ilCtrl->getFormAction($a_parent_obj));
        $this->setRowTemplate("tpl.list_sendings_row.html", "Services/WebForm");
        $this->setDefaultOrderField("send_date");
        $this->setDefaultOrderDirection("asc");
        $this->setPrefix("webform_sendings");

		$data = ilWebFormSaving::_getSavingsData(
				!$a_root_mode, $a_checked, $a_form_ids, $a_user_ids);

        $this->setData($data);
    }

    /**
    * Fill a single data row
    */
    protected function fillRow($a_set)
    {
        global $ilCtrl;

		// get the form object
		if (isset($this->forms[$a_set["form_id"]]))
		{
		    $form = $this->forms[$a_set["form_id"]];
		}
		else
		{
			$form = new ilWebForm($a_set["form_id"]);
			$form->read();
			$this->forms[$a_set["form_id"]] = $form;
		}
		
		// get the saving object (without entries)
		$saving = new ilWebFormSaving($form, null, null, $a_set["save_id"]);
		$saving->read(false);
		
		// user column
		$ilCtrl->setParameter($this->getParentObject(),"save_id", $saving->getSaveId());
		$this->tpl->setVariable("USERNAME", $a_set["username"]);
		$this->tpl->setVariable("LOGIN_LINK", "ilias.php?baseClass=ilMailGUI&type=new&rcp_to=".$a_set["login"]);

		// title column
		$this->tpl->setVariable("TITLE",$form->getTitle());
		$this->tpl->setVariable("FORM_LINK",$form->makeFormLink($saving->getSaveId(), true));
		$this->tpl->setVariable("MODULE",ilObject::_lookupTitle($form->getLmObjId()));
		if ($saving->getIsForumSaving())
		{
			if ($forum_link = $form->makeForumLink())
			{
				$this->tpl->setCurrentBlock("forum");
				$this->tpl->setVariable("FORUM_LINK", $forum_link);
				$this->tpl->setVariable("TXT_FORUM", $this->lng->txt("forum"));
				$this->tpl->setVariable("FORUM_TITLE", $form->getForum());
				$this->tpl->parseCurrentBlock();
			}
			else
			{
				$this->tpl->setVariable("TXT_DISCUSSION"," (Diskussionsaufgabe)");
			}
		}

		// senddate column
		if ($saving->getSenddate())
		{
			$this->tpl->setVariable("SENDDATE",date("d.m.Y H:i", strtotime($saving->getSenddate())));
		}
		elseif ($this->root_mode)
		{
			$this->tpl->setCurrentBlock("send_link");
			$this->tpl->setVariable("SEND_LINK", $ilCtrl->getLinkTarget($this->getParentObject(),"setSent"));
			$this->tpl->setVariable("SEND_LABEL", $this->lng->txt("set"));
			$this->tpl->parseCurrentBlock();
		}

		// checkdate column
		if ($saving->getCheckdate())
		{
			$this->tpl->setCurrentBlock("check_date");
			$this->tpl->setVariable("CHECKDATE",date("d.m.Y H:i", strtotime($saving->getCheckdate())));
			$this->tpl->parseCurrentBlock();
			
			if ($this->tutor_mode)
			{
				$this->tpl->setCurrentBlock("uncheck_link");
				$this->tpl->setVariable("CHECK_LINK", $ilCtrl->getLinkTarget($this->getParentObject(),"setUnchecked"));
				$this->tpl->setVariable("CHECK_LABEL", $this->lng->txt("reset"));
				$this->tpl->parseCurrentBlock();
			}

		}
		else if ($this->tutor_mode)
		{
			$this->tpl->setCurrentBlock("check_link");
			$this->tpl->setVariable("CHECK_LINK", $ilCtrl->getLinkTarget($this->getParentObject(),"setChecked"));
			$this->tpl->setVariable("CHECK_LABEL", $this->lng->txt("set"));
			$this->tpl->parseCurrentBlock();
		}

		// solution column
		if ($form->hasSolution())
		{
			$show_to_user = $form->checkSolution($saving->getUserId(),$error);
			
			// if ($show_to_user or $this->tutor_mode)
			if ($show_to_user)
			{
				$this->tpl->setCurrentBlock("solution");
				$this->tpl->setVariable("SOLUTION", $this->lng->txt("webform_goto_solution"));
				$this->tpl->setVariable("SOLUTION_LINK",$form->makeSolutionLink());
				$this->tpl->parseCurrentBlock();
			}

			if (!$show_to_user)
			{
				$this->tpl->setCurrentBlock("no_solution");
				$this->tpl->setVariable("TXT_SOLUTION_INFO", $error);
				$this->tpl->parseCurrentBlock();
			}
		}
		else
		{
			$this->tpl->setCurrentBlock("no_solution");
			$this->tpl->setVariable("TXT_SOLUTION_INFO", "--");
			$this->tpl->parseCurrentBlock();
		}
    }
}
?>
