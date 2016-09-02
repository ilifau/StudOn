<?php
/* fim: [webform] new class. */

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

define("IL_WEBFORM_SHOW",1);
define("IL_WEBFORM_PRINT",2);
define("IL_WEBFORM_SOLUTION",3);

/**
* Class ilWebFormLoaderGUI
*
* @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
* $Id: $
*
*/
class ilWebFormLoaderGUI
{
	/**
	* form object
	* @var 		object
	* @access 	private
	*/
	var $form;

	/**
	* saving object
	* @var 		object
	* @access 	private
	*/
	var $saving;


	/**
	* id of a specific saving to be shown
	* @var 		object
	* @access 	private
	*/
	var $save_id;


	/**
	* action mode
	* @var 		int
	* @access 	private
	*/
	var $mode;

	/**
	* Constructor
	* @access	public
	*/
	function ilWebFormLoaderGUI()
	{
	}
	
	function readByURI($a_uri)
	{
		global $ilDB, $ilUser;
		
		// split the request URI
		$uri = parse_url($a_uri);

		// check if a specific saving is requested
		parse_str($uri["query"], $query);
		if ($query["form_save_id"])
		{
			$this->save_id = $query["form_save_id"];
		}
		else
		{   // set explictly to null, because this will be checked
			$this->save_id = null;
		}

		//get the learning object and the relative form path
		$path = $uri["path"];
		$pos1 = strpos($path, "lm_data/lm_") + 11;
		$pos2 = strpos($path, "/", $pos1);
		$lm_obj_id = substr($path, $pos1, $pos2-$pos1);
		$path = substr($path, $pos2+1);

		// search for a form or solution in the form definitions
		$q = "SELECT * FROM webform_types "
			."WHERE lm_obj_id = ". $ilDB->quote($lm_obj_id, 'integer')
			." AND (path = ". $ilDB->quote($path, 'text')
			." OR solution_ref = ". $ilDB->quote($path, 'text'). ")";

		$result = $ilDB->query($q);
		
		if ($row = $ilDB->fetchAssoc($result))
		{
			if ($row["solution_ref"] == $path)
			{
				$this->mode = IL_WEBFORM_SOLUTION;
			}
			elseif ($query["form_print"] or $this->save_id)
			{
				$this->mode = IL_WEBFORM_PRINT;
			}
			else
			{
				$this->mode = IL_WEBFORM_SHOW;
			}
		}
		else
		{
			return false;
		}
		
		// read the form definition
		require_once ("./Services/WebForm/classes/class.ilWebForm.php");
		$this->form = new ilWebForm($row["form_id"],
									$row["lm_obj_id"],
								 	$row["form_name"]);
		$this->form->read();
		
		// read the form saving
		require_once ("./Services/WebForm/classes/class.ilWebFormSaving.php");
		$this->saving = new ilWebFormSaving($this->form,
										  $ilUser->getId(),
                                          $row["dataset_id"],
                                          $this->save_id);
        $this->saving->read();
        
        switch ($this->mode)
        {
        	case IL_WEBFORM_SHOW:
				return $this->getFilledForm();
				
			case IL_WEBFORM_PRINT:
			    return $this->getPrintForm();

			case IL_WEBFORM_SOLUTION:
			    return $this->getSolutionPage();
       	}
	}
	
	function getFilledForm()
	{
		global $lng;
		
		// check, if the saving can be shown
		require_once ("./Services/WebForm/classes/class.ilWebFormAccess.php");
		if (!ilWebFormAccess::_checkSavingAccess($this->saving))
		{
			return $this->getErrorPage($lng->txt("permission_denied"));
		}

		// read the source of the form
		$file = realpath(ILIAS_ABSOLUTE_PATH
						. "/" . ILIAS_WEB_DIR
						. "/" . CLIENT_ID
            			. "/" . "lm_data/lm_" . $this->form->getLmObjId()
            			. "/" . $this->form->getPath()
						);

    	$fp = fopen($file, "rb");
    	$page = fread($fp, filesize($file));
    	fclose($fp);

	    // read the source of the javascript functions
		$file = realpath(ILIAS_ABSOLUTE_PATH."/Services/WebForm/js/restore_forms.js");
    	$fp = fopen($file, "rb");
    	$js_functions = fread($fp, filesize($file));
    	fclose($fp);

		// prepare the array of entries
		if (is_null($this->save_id))
		{
			$entries = $this->saving->getAllEntries(); 	//all of the dataset
		}
		else
		{
			$entries = $this->saving->getEntries();     //only the specific
		}
		if (is_null($entries))
		{
			$entries = array();
		}
		else
		{
			$entries = array_map("rawurlencode", $entries);
		}
		
		// build the field assignments
		while (list ($fieldname, $fieldvalue) = each($entries))
		{
			$js_fields .= "fields['$fieldname'] = '$fieldvalue';\r\n";
		}

		// build the javascript code
		// TODO set form.action
       	$js_code ="
	   	<script language=\"JavaScript\">
			" . $js_functions . "
			var fields = new Object;
			" . $js_fields . "
			var form = new Object;
			form.id = \"". $this->form->getFormName(). "\";
			form.action = \"". $this->form->makeSendLink(). "\";
			form.fields = fields;
			var forms = new Array();
			forms[0] = form;
			restoreForms(forms);
		</script>
		";

		// define the placeholder to be replaced
    	$p_restore = "<!--FORMS: LOAD-FORM-DATA-->";

		return ereg_replace($p_restore, $js_code, $page);
	}

	function getPrintForm()
	{
		global $lng;

		// check, if the saving can be shown
		require_once ("./Services/WebForm/classes/class.ilWebFormAccess.php");
		if (!ilWebFormAccess::_checkSavingAccess($this->saving))
		{
			return $this->getErrorPage($lng->txt("permission_denied"));
		}

		// get the prepared form page
		$page = $this->getFilledForm();

		// prepare the array of entries
		if (is_null($this->save_id))
		{
			$entries = $this->saving->getAllEntries(); 	//all of the dataset
		}
		else
		{
			$entries = $this->saving->getEntries();     //only the specific
		}
		if (is_null($entries))
		{
			$entries = array();
		}
		
		require_once ("./Services/WebForm/classes/class.htmlparser.php");
		$hp = New HtmlParser($page);
		
		$index = 0;
		while ($hp->parse())
		{
			$ret.= substr($page, $index, $hp->iNodeStart - $index);
			$index = $hp->iNodeStart;

			// replace textareas
			if ($hp->iNodeName == "body" and $hp->iNodeType == NODE_TYPE_ELEMENT)
			{
				$ret.= substr($page, $hp->iNodeStart, $hp->iNodeLength);
				$ret .= $this->renderFormInfo();
				$index = $hp->iNodeStart + $hp->iNodeLength;
			}

			// replace textareas
			if ($hp->iNodeName == "textarea" and $hp->iNodeType == NODE_TYPE_ELEMENT)
			{
				$ret .= $this->renderEntry($entries[$hp->iNodeAttributes["name"]]);

				while ($hp->iNodeName != "textarea" or $hp->iNodeType != NODE_TYPE_ENDELEMENT)
				{
					$hp->parse();
				}
				$index = $hp->iNodeStart + $hp->iNodeLength;
			}
			
			// replace single line input fields
			if ($hp->iNodeName == "input" and $hp->iNodeType == NODE_TYPE_ELEMENT
				and $hp->iNodeAttributes["type"] == "text")
			{
				$ret .= $this->renderEntry($entries[$hp->iNodeAttributes["name"]]);

				$index = $hp->iNodeStart + $hp->iNodeLength;
			}

			// hide submit buttons
			if ($hp->iNodeName == "input" and $hp->iNodeType == NODE_TYPE_ELEMENT
				and $hp->iNodeAttributes["type"] == "submit")
			{
				$index = $hp->iNodeStart + $hp->iNodeLength;
			}
		}

		return $ret;
	}

	function renderEntry($entry)
	{
		return "<div style=\"
					border:1px solid black;
					background-color: white;
					padding: 3px;
					font-family: courier, sans-serif;
				\">". nl2br(htmlspecialchars($entry))."</div>";
	}	

	function renderFormInfo()
	{
		$user = ilObjUser::_lookupName($this->saving->getUserId());
		
		$ret = "<pre>"
			.   "<b>Pfad:       </b>   "
			. 	$this->form->getLmTreePath(). "<br>"
			.   "<b>Lerneinheit:</b>   "
			. 	ilObject::_lookupTitle($this->form->getLmObjId()). "<br>"
			.   "<b>Formular:</b>      "
			. 	$this->form->getTitle(). "<br>"
			.   "<b>Nutzer:</b>        "
			. 	$user["firstname"]. " " . $user["lastname"]. "<br>";
			
		if ($this->saving->getSenddate())
		{
			$ret .= "<b>Einsendedatum:</b> "
				.	date("d.m.Y H:i", strtotime($this->saving->getSenddate())). "<br>";
		}
		elseif ($this->saving->getSavedate())
		{
			$ret .= "<b>Speicherdatum:</b> "
				.	date("d.m.Y H:i", strtotime($this->saving->getSavedate())). "<br>";
		}
		
		$ret .=	"</pre>"
			.	"<hr>";
			
		return $ret;
	}


	function getSolutionPage()
	{
		global $ilUser, $lng;
		$lng->loadLanguageModule("webform");

		if ($this->form->checkSolution($ilUser->getId(), $error))
		{
			// read the source of the solution
			$file = realpath(ILIAS_ABSOLUTE_PATH
						. "/" . ILIAS_WEB_DIR
						. "/" . CLIENT_ID
            			. "/" . "lm_data/lm_" . $this->form->getLmObjId()
            			. "/" . $this->form->getSolutionRef()
						);

	    	$fp = fopen($file, "rb");
	    	$page = fread($fp, filesize($file));
	    	fclose($fp);
	    	
	    	return $page;
		}
		else
		{
			return ($this->getErrorPage($error));
		}
	}
	

	// TODO: make nicer
	function getErrorPage($errortext)
	{
		global $tpl;

		$tpl->addBlockFile("CONTENT", "content", "tpl.form_load.html", "Services/WebForm");

		$tpl->setCurrentBlock("error");
		$tpl->setVariable("ERROR", $errortext);
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock("content");
		$tpl->setVariable("LINK_CLOSE", "javascript:close()");
		$tpl->setVariable("LABEL_CLOSE", "[Fenster schlie&szlig;en]");
		$tpl->parseCurrentBlock();
		$tpl->show(false,false);

		exit;
	}
}
?>
