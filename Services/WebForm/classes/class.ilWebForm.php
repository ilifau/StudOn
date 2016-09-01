<?php
/* fim: [webform] new class. */

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* Class ilWebForm
* Base class for handling form definitions
*
* @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
* $Id: $
*
* @extends ilObject
* @package content
*/
class ilWebForm
{

	var	$form_id;
	var	$lm_obj_id;
	var $form_name;

	var $dataset_id;
	var $title;
	var $path;
	var $send_maxdate;

	var $solution_ref;
	var $solution_mode;
	var $solution_date;

	var $forum;
	var $forum_parent;
	var $forum_subject;

	// will be set by makeForumLink()
	var $forum_link = NULL;
	
	var $hasData = false;
	
	/**
	* Constructor
	* The form is not automatically read from the database.
	* A new form is not created automatically in the database.
	* @param    int  	(optional) id of the form
	* @param	int		(optional) learning module id
	* @param    string  (optional) name of the form
	* @access	public  (optional)
	*/
	function ilWebForm($a_form_id = null, $a_lm_obj_id = null, $a_form_name = null)
	{
		$this->form_id = $a_form_id;
		$this->lm_obj_id =	$a_lm_obj_id;
		$this->form_name = $a_form_name;
		$this->setDefaults();
	}
	
	/**
	* Import an XML file of form definitions
	*
	* @param    int     learning module object id
	* @param    string  file path on server
	* @return   array  	names of imported form definitions
	*/
	function _importXML($a_lm_obj_id, $a_file)
	{
		$xml = simplexml_load_file($a_file);

		$imported = array();
		foreach($xml->UserForm as $formdef)
		{
			$form = new ilWebForm(null, $a_lm_obj_id, (string) $formdef["Name"]);
			$form_exists = $form->read();

			$form->setFormName((string) $formdef["Name"]);
			$form->setDatasetId((string) $formdef["Dataset"]);
			$form->setTitle((string) $formdef["Title"]);
			$form->setPath((string) $formdef["Path"]);
			$form->setSendMaxdate((string) $formdef["SendMaxDate"] == "" ? NULL : (string) $formdef["SendMaxDate"]);
			$form->setSolutionRef((string) $formdef["SolutionRef"]);
			$form->setSolutionMode((string) $formdef["SolutionMode"]);
			$form->setSolutionDate((string) $formdef["SolutionDate"] == "" ? NULL : (string) $formdef["SolutionDate"]);
			$form->setForum((string) $formdef["Forum"]);
			$form->setForumParent((string) $formdef["ForumParent"]);
			$form->setForumSubject((string) $formdef["ForumSubject"]);

			if ($form_exists)
			{
				$form->update();
			}
			else
			{
				$form->create();
			}

			$imported[] = $formdef["Title"]. " (".$formdef["Name"].")";
		}
		return $imported;
	}
	
	/**
	* Get all form definitions of a learning module
	* @access   static
	* @param    int     learning module id
	* @return   array 	of WebForm objects
	*/
	function _getFormsOfModule($a_lm_obj_id)
	{
		global $ilDB;
		
		$q = "SELECT form_id FROM webform_types WHERE lm_obj_id = "
			. $ilDB->quote($a_lm_obj_id, 'integer')
			. " ORDER BY title";
		$result = $ilDB->query($q);
		
		$forms = array();
		while ($row = $ilDB->fetchAssoc($result))
		{
			$form = new ilWebForm($row["form_id"]);
			$form->read();
			$forms[] = $form;
		}
		return $forms;
	}

	/**
	* Get all form data of a learning module
	* @access   static
	* @param    int     learning module id
	* @return   array 	of webform_types records
	*/
	function _getFormDataOfModule($a_lm_obj_id)
	{
		global $ilDB;

		$q = "SELECT * FROM webform_types WHERE lm_obj_id = "
			. $ilDB->quote($a_lm_obj_id, 'integer');
		$result = $ilDB->query($q);

		$data = array();
		while ($row = $ilDB->fetchAssoc($result))
		{
			$data[] = $row;
		}
		return $data;
	}

	
	/**
	* Writes a new form definition to the database
	* @access	public
	*/
	function create()
	{
		global $ilDB;

        $this->form_id = $ilDB->nextID('webform_types');

		$q = "INSERT INTO webform_types (
				form_id, lm_obj_id, form_name, dataset_id,
				title, path, send_maxdate,
				solution_ref, solution_mode, solution_date, forum, forum_parent, 
				forum_subject) VALUES ".
				"("
				.$ilDB->quote($this->getFormId(),'integer').","
				.$ilDB->quote($this->getLmObjId(),'integer').","
				.$ilDB->quote($this->getFormName(),'text').","
				.$ilDB->quote($this->getDatasetId(),'text').","
				.$ilDB->quote($this->getTitle(),'text').","
				.$ilDB->quote($this->getPath(),'text').","
				.$ilDB->quote($this->getSendMaxdate(),'timestamp').","
				.$ilDB->quote($this->getSolutionRef(),'text').","
				.$ilDB->quote($this->getSolutionMode(),'text').","
				.$ilDB->quote($this->getSolutionDate(),'timestamp').","
				.$ilDB->quote($this->getForum(),'text').","
				.$ilDB->quote($this->getForumParent(),'text').","
				.$ilDB->quote($this->getForumSubject(),'text').
				")";
		
		$ilDB->manipulate($q);
		$this->hasData = true;
	}
	
	
	/**
	* Update the current form definition in the database
	* @access	public
	*/
	function update()
	{
		global $ilDB;
		
		$q = "UPDATE webform_types SET "
			. "form_name = ".$ilDB->quote($this->getFormName(),'text')
			. ",dataset_id = ".$ilDB->quote($this->getDatasetId(),'text')
			. ",lm_obj_id = ".$ilDB->quote($this->getLmObjId(),'integer')
			. ",title = ".$ilDB->quote($this->getTitle(),'text')
			. ",path = ".$ilDB->quote($this->getPath(),'text')
			. ",send_maxdate = ".$ilDB->quote($this->getSendMaxdate(),'timestamp')
			. ",solution_ref = ".$ilDB->quote($this->getSolutionRef(),'text')
			. ",solution_mode = ".$ilDB->quote($this->getSolutionMode(),'text')
			. ",solution_date = ".$ilDB->quote($this->getSolutionDate(),'timestamp')
			. ",forum = ".$ilDB->quote($this->getForum(),'text')
			. ",forum_parent = ".$ilDB->quote($this->getForumParent(),'text')
			. ",forum_subject = ".$ilDB->quote($this->getForumSubject(),'text')
			. " WHERE form_id = ".$ilDB->quote($this->getFormId(),'integer');
				
		$ilDB->manipulate($q);
		$this->hasData = true;
	}


	/**
	* Read the form definition from the database
	* Either form_id or lm_obj_id and form_name must be set
	*
	* @return   boolean 	True, if form definition found
	* @access	public
	*/
	function read()
	{
		global $ilDB;
		
		if ($this->form_id)
		{

			$q = "SELECT * FROM webform_types WHERE form_id = ".$ilDB->quote($this->form_id,'integer');
		}
		else
		{
			$q = "SELECT * FROM webform_types WHERE lm_obj_id = ".$ilDB->quote($this->lm_obj_id,'integer')
							." AND form_name = ".$ilDB->quote($this->form_name,'text');
		}
		
		$result = $ilDB->query($q);
		if ($row = $ilDB->fetchAssoc($result))
		{
			$this->hasData = true;
			$this->setFormId($row["form_id"]);
			$this->setLmObjId($row["lm_obj_id"]);
			$this->setFormName($row["form_name"]);
			$this->setDatasetId($row["dataset_id"]);
			$this->setTitle($row["title"]);
			$this->setPath($row["path"]);
			$this->setSendMaxDate($row["send_maxdate"]);
			$this->setSolutionRef($row["solution_ref"]);
			$this->setSolutionMode($row["solution_mode"]);
			$this->setSolutionDate($row["solution_date"]);
			$this->setForum($row["forum"]);
			$this->setForumParent($row["forum_parent"]);
			$this->setForumSubject($row["forum_subject"]);
			return true;
		}
		else
		{
			return false;
		}
	}


	/**
	* Delete the form if no savings exist
	*
	* @return	boolean		true if the form is removed
	* @access	public
	*/
	function delete()
	{
		global $ilDB;
		
		$query = "SELECT save_id FROM webform_savings WHERE form_id= ".$ilDB->quote($this->getFormId(),'integer');
		$result = $ilDB->query($query);
    	if ($row = $ilDB->fetchAssoc($result))
    	{   
       		return false;
    	}

    	$query = "DELETE FROM webform_types WHERE form_id = ".$ilDB->quote($this->getFormId(),'integer');
		$result = $ilDB->query($query);
    	return true;
	}
	

	/**
	* Check, if a SendMaxdate is defined
	* @return   boolean
	* @access	private
	*/
	function hasSendMaxdate()
	{
		return (!is_null($this->getSendMaxdate()) and $this->getSendMaxdate() != "");
	}

	/**
	* Return the url path of the learning module directory (without trailing slash)
	* @return   string  URL of the learning module
	* @access	public
	*/
	function getLmHttpPath()
	{
		// create a server path and not a full URL
		// because the display of a forum article would convert
		// the full url into a clickable link, even inside a href attribute
		$parts = explode("/", ILIAS_HTTP_PATH);
		$loc = "/" . $parts[3]
			."/".ILIAS_WEB_DIR
			."/".CLIENT_ID
			."/lm_data/lm_".$this->getLmObjId();
		return $loc;
	}

	/**
	* Return the href of a form (relative to DOCUMENT_ROOT)
	* @param    int      (optional) id of a form_saving to include
	* @return   int   (optional) print mode ("user", "admin", null)
	* @return   string   relative URL of the form
	* @access	public
	*/
	function makeFormLink($a_save_id = null, $a_print = null)
	{
		$loc = $this->getLmHttpPath();
		$loc .= "/". $this->getPath();

		if ($a_save_id)
		{
			$loc .= "?form_save_id=" . $a_save_id;

			if ($a_print)
			{
				$loc .= "&form_print=" . $a_print;
			}
		}
		return $loc;
	}
	
	/**
	* Return the href of a the form's learning module
	* @return   string  URL of the learning module
	* @access	public
	*/
	function makeLMLink()
	{
		$loc = $this->getLmHttpPath();

		// TODO: check the start url of the learning module
		$loc .= "/_autogen/page1.html";

		return $loc;
	}
	
	/**
	* Return the href of a form's solution solution
	* @return   string  URL of the learning module
	* @access	public
	*/
	function makeSolutionLink()
	{
		$loc = $this->getLmHttpPath();
		$loc .= "/". $this->getSolutionRef();
		return $loc;
	}

	/**
	* Return the action url for a form to send
	* @return   string  action URL
	* @access	public
	*/
	function makeSendLink()
	{
		$loc = ILIAS_HTTP_PATH
			. "/Services/WebForm/send.php"
			."?form_id=".$this->getFormId();
		return $loc;
	}

	/**
	* Get the link to the forum thread
	* @return   string  locator string
	* @access	public
	*/
	function makeForumLink()
	{
		require_once "./Services/WebForm/classes/class.ilWebFormUtils.php";
		require_once "./Modules/Forum/classes/class.ilObjForum.php";

		// get a pre-produced forum link
		if (isset($this->forum_link))
		{
			return $this->forum_link;
		}

		// find the forum
		$forumRefId = ilWebFormUtils::_findForumRefId($this->getForum(),$this->getLmObjId());

		if (!$forumRefId)
		{
			return "";
		}

		// find the thread
		if ($this->hasForumParent())
		{
			$forumObj = new ilObjForum($forumRefId);
			$frm = $forumObj->Forum;
			$frm->setForumId($forumObj->getId());
			$frm->setForumRefId($forumObj->getRefId());
			$frm->setMDB2WhereCondition("top_frm_fk = %s", array('integer'), array($frm->getForumId()));
			$topicData = $frm->getOneTopic();
			$threadInfo = ilWebFormUtils::_findForumThreadId($topicData["top_pk"], $this->getForumParent());
			$thread_suffix = "_".$threadInfo["thr_pk"];
		}

		// build the link
		$parts = explode("/", ILIAS_HTTP_PATH);
		$this->forum_link =
				"/" . $parts[3] .
				"/goto.php?target=" .
				"frm_".$forumRefId.$thread_suffix .
				"&client_id=".CLIENT_ID;

		return $this->forum_link;
	}


	/**
	* Get the repository path of the form's learning module
	* @return   string  locator string
	* @access	public
	*/
	function getLmTreePath()
	{
		global $tree;
		require_once "./Services/WebForm/classes/class.ilWebFormUtils.php";

		$ref_id = ilWebFormUtils::_findReadableRefId($this->getLmObjId());
		$path = $tree->getPathFull($ref_id);
		
		for ($i = 0; $i < count($path); $i++)
		{
			$node = $path[$i];

			if ($i > 0 and $i < count($path) - 1)
			{
				$ret .= $node["title"];
			}
			if ($i > 0 and $i < count($path) - 2)
			{
				$ret .= " &gt; ";
			}
		}
		return $ret;
	}
	

	// Getters
	function getLmObjId() {
		return $this->lm_obj_id;
	}
	function getFormId()
	{
		return $this->form_id;
	}
	function getFormName()
	{
		return $this->form_name;
	}
	function getDatasetId()
	{
		return $this->dataset_id;
	}
	function getTitle()
	{
		return $this->title;
	}
	function getPath()
	{
		return $this->path;
	}
	function getSendMaxdate()
	{
		return $this->send_maxdate;
	}
	function getSolutionRef()
	{
		return $this->solution_ref;
	}
	function getSolutionMode()
	{
		return $this->solution_mode;
	}
	function getSolutionDate()
	{
		return $this->solution_date;
	}
	function getForum() {
		return $this->forum;
	}
	function getForumParent()
	{
		return $this->forum_parent;
	}
	function getForumSubject()
	{
		return $this->forum_subject;
	}

	// Setters
	function setLmObjId($val)
	{
		$this->lm_obj_id = $val;
	}
	function setFormId($form_id)
	{
		$this->form_id = $form_id;
	}
	function setFormName($val)
	{
		$this->form_name = $val;
	}
	function setDatasetId($val)
	{
		$this->dataset_id = $val;
	}
	function setTitle($title)
	{
		$this->title = $title;
	}
	function setPath($path)
	{
		$this->path = $path;
	}
	function setSendMaxdate($val)
	{
		$this->send_maxdate = $val;
	}
	function setSolutionRef($solution_ref)
	{
		$this->solution_ref = $solution_ref;
	}
	function setSolutionMode($solution_mode)
	{
		$this->solution_mode = $solution_mode;
	}
	function setSolutionDate($solution_date)
	{
		$this->solution_date = $solution_date;
	}
	function setForum($forum)
	{
		$this->forum = $forum;
	}
	function setForumParent($forum)
	{
		$this->forum_parent= $forum;
	}
	function setForumSubject($forum_subject)
	{
		$this->forum_subject = $forum_subject;
	}
	

	/**
	* Set default values for the object member variables
	* @access	private
	*/
	function setDefaults()
	{
		if (is_null($this->getSolutionMode()))
		{
			$this->setSolutionMode("checked");
		}
	} 
	
	/**
	* Check if the object is successfully created or read from the database
	* @access	public
	* @return	boolean  True, if the form data is read
	*/
	function hasData()
	{
		return $this->hasData;
	}

	/**
	* Check, if the form has a forum
	* @access	public
	* @return	boolean		True, if the form has a forum
	*/
	function hasForum()
	{
		return (strlen($this->getForum()) != 0);
	}

	/**
	* Check if the form has a forum parent thread
	* @access	public
	* @return	boolean		True, if the form has a forum parent thread
	*/
	function hasForumParent()
	{
		return (strlen($this->getForumParent()) != 0);
	}
	
	/**
	* Check if the form has a forum subject
	* @access	public
	* @return	boolean		True, if the form has a forum subject
	*/
	function hasForumSubject()
	{
		return (strlen($this->getForumSubject()) != 0);
	}
	
	/**
	* Check if the form has a solution
	* @access	public
	* @return	boolean		True, if a SolutionRef is defined
	*/
	function hasSolution()
	{
		return (strlen($this->getSolutionRef()) != 0);
	}

	/**
	* Check if the solution can be shown for the given user id
	* @access	public
	* @param    int   	 	User id
	* @param    string   	(return) error message, if false
	* @return	boolean     True, if solution can be shown
	*/
	function checkSolution($a_user_id, &$error)
	{
	    global $ilDB, $lng;
    
	    $error = "";

	    if (!$this->hasSolution())
	    {
        	$error = $lng->txt("webform_no_solution");
		}
    	elseif ($this->getSolutionMode() == "date"
				and strtotime($this->getSolutionDate()) > time())
		{
	        $error = sprintf($lng->txt("webform_solution_after_date"),
                		date("d.m.Y H:i",strtotime($this->getSolutionDate())));
    	}
    	elseif ($this->getSolutionMode() == "send")
    	{
	        $q = "SELECT save_id FROM webform_savings"
                            	. " WHERE form_id=". $ilDB->quote($this->getFormId(),'integer')
                            	. " AND user_id=". $ilDB->quote($a_user_id, 'integer')
                            	. " AND senddate IS NOT NULL"
                            	;
			$r = $ilDB->query($q);
        	if ($r->numRows() == 0)
        	{
	            $error = $lng->txt("webform_solution_after_sent");
        	}
    	}
    	elseif ($this->getSolutionMode() == "checked")
    	{
			$q = "SELECT save_id FROM webform_savings"
                            . " WHERE form_id=". $ilDB->quote($this->getFormId(),'integer')
                            . " AND user_id=". $ilDB->quote($a_user_id,'integer')
                            . " AND checkdate IS NOT NULL"
                            ;
                            
			$r = $ilDB->query($q);
        	if ($r->numRows() == 0)
        	{
	            $error = $lng->txt("webform_solution_after_checked");
        	}
    	}

    	if ($error == "")
    	{   return true;
    	}
    	else
    	{   return false;
    	}
	}
} // END class.ilWebForm
?>
