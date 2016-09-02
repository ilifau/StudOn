<?php
/* fim: [webform] new class. */

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* Class ilWebFormAccess
*
* This class provides checks for visibility of web forms and sendings
* based on existance and RBAC settings of the embedding learning modules
*
* @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
* @version $Id: $
*
*/
class ilWebFormAccess
{
	/**
	* Find the path of the XML file with form definitions
	*
	* @access   static
	* @param    object     learning module object
	* @return   string     path of the XML importfile
	*/
	function _findXMLImportFile($a_lm_obj)
	{
		$local_path = "/_metainf/userforms.xml";
        $file = $a_lm_obj->getDataDirectory().$local_path;
		if (is_file($file))
        {
        	return $file;
		}
		else
		{
			return "";
		}
	}
	
	/**
	* Check if a learning module has form definitions
	* This check is used for displaying the definitions tab
	*
	* @return   boolean     true or false
	*/
	function _hasForms($a_lm_obj_id)
	{
		global $ilDB;
		
		$q = "SELECT form_id FROM webform_types WHERE lm_obj_id = ".$ilDB->quote($a_lm_obj_id, 'integer');
		$result = $ilDB->query($q);
		if ($row = $ilDB->fetchAssoc($result))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	* Checks if a course has learning modules with web forms
	* This check is used for displaying the sendings tab
	*
	* @return   boolean     true or false
	*/
	function _courseHasForms($a_ref_id)
	{
		global $tree;
		
		$node = $tree->getNodeData($a_ref_id);
		$modules = $tree->getSubTree($node, true, "htlm");
		foreach ($modules as $module)
		{
			if (self::_hasForms($module["obj_id"]))
			{
				return true;
			}
		}
		return false;
	}

	/**
	* Checks if sendings of other users can be viewed
	* This check is used to determine the tutor mode of the sendings tab
	*
	* @return   boolean     true or false
	*/
	function _checkEditSendings($a_ref_id)
	{
		global $ilAccess;
		return $ilAccess->checkAccess("edit_learning_progress", "", $a_ref_id);
	}
	
	/**
	* Checks if all savings (not only sent) of other users can be viewed
	* This should be only possible for the root user
	*
	* @return   boolean     true or false
	*/
	function _checkViewAllSavings($a_ref_id)
	{
		global $rbacsystem;
		return $rbacsystem->checkAccess("visible,read", SYSTEM_FOLDER_ID);
	}
	
	
	/**
	* Checks if the current user has read acces to a saving
	* @param 	object 	ilWebFormSaving
	* @access 	public
	*/
	function _checkSavingAccess($saving)
	{
		global $ilUser;
		
		if (!$saving->getUserId())
		{
			return true;
		}
		elseif ($ilUser->getId() == $saving->getUserId())
		{
			return true;
		}
		elseif ($saving->getIsForumSaving())
		{
			require_once ("./Services/WebForm/classes/class.ilWebFormUtils.php");
			if ($ref_id = ilWebFormUtils::_findForumRefId(
			                $saving->form->getForum(),
							$saving->form->getLmObjId()))
			{
				// not entirely true
				// better would be to save the forum id in the saving
				return true;
			}
		}
		else
		{
			require_once ("./Services/WebForm/classes/class.ilWebFormUtils.php");
			if ($ref_id = ilWebFormUtils::_findCourseRefId($saving->form->getLmObjId()))
			{
				if (self::_checkEditSendings($ref_id))
				{
					return true;
				}
			}
		}
		return false;
	}
}

?>
