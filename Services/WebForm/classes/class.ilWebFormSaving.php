<?php
/* fim: [webform] new class. */

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* Class ilWebFormSaving
* Class for handling a form saving
*
* @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
* $Id: $
*
*/
class ilWebFormSaving
{

	/**
	* Fields belonging to the form saving
	* @var 		array 
	* @access 	private
	*/
	var $entries;
	/**
	
	* All fields belonging to the dataset form saving
	* @var 		array 
	* @access 	private
	*/
	var $all_entries;

	/**
	* Form this saving belongs to
	* @var 		object
	* @access 	private
	*/
	var $form;

	/**
	* User id this saving belongs to
	* @var 		int
	* @access 	private
	*/
	var $user_id;

	/**
	* Save id of the saving
	* @var 		int 
	* @access 	private
	*/
	var $save_id;
	
	/**
	* Dataset id of the saving
	* @var 		int 
	* @access 	private
	*/
	var $dataset_id;
	
	/**
	* The date when the saving was saved to the db
	* @var 		date 
	* @access 	private
	*/
	var $savedate;
	
	/**
	* The date when the saving was sent to the tutor or forum
	* @var 		date senddate
	* @access 	private
	*/
	var $senddate;
	
	/**
	* The date when the saving was checked by the tutor
	* @var 		date 
	* @access 	private
	*/
	var $checkdate;
	
	/**
	* The id of the corresponding form
	* @var 		int 
	* @access 	private
	*/
	var $form_id;
		
	/**
	* True, if the saving is a forum saving
	* @var 		bool 
	* @access 	private
	*/
	var $is_forum_saving;

	/**
	* Constructor
	* @param	object		Form object this saving belongs to
	* @param	int			User id
	* @param	int			The dataset id of the form saving
	* @param	int			The save id of the form saving
	* @param	array		The fields of the form saving
	* @param	boolean		True, if it is a forum saving
	* @access	public
	*/
	function ilWebFormSaving($a_form, $a_user_id, $a_dataset_id, $a_save_id = null, $a_entries = null, $a_is_forum_saving = false)
	{
		$this->entries = array();
		$this->all_entries = array();
		$this->form = $a_form;
		$this->form_id = $this->form ? $a_form->getFormId() : null;
		$this->user_id = $a_user_id;
		$this->save_id = $a_save_id;
		$this->dataset_id = $a_dataset_id;
		$this->setIsForumSaving($a_is_forum_saving);
		
		if (!is_null($a_entries))
		{			
			$this->entries = $a_entries;
		}
	}

	/**
	* Writes a form saving to the database
	* @param    string   form is sent ("true", "false" or "")
	* @param    string   form is checked ("true", "false" or "")
	* @param    array    array of form_ids
	* @param    array    array of user_ids
	* @return   array    array of row arrays
	* @access	static
	*/
	function _getSavingsData($a_sent = "", $a_checked = "", $a_form_ids = array(), $a_user_ids = array())
	{
		global $ilDB;

		$q = "SELECT fs.*, ft.title, ft.solution_ref, u.login, u.lastname, u.firstname
				FROM webform_savings AS fs, webform_types AS ft, usr_data as u
				WHERE fs.form_id = ft.form_id
				AND fs.user_id = u.usr_id";

		// filter sent
		if ($a_sent == "true")
		{
			$q .= " AND fs.senddate IS NOT NULL";
		}
		elseif ($a_sent == "false")
		{
			$q .= " AND fs.senddate IS NULL";
		}

		// filter checked
		if ($a_checked == "true")
		{
			$q .= " AND fs.checkdate IS NOT NULL";
		}
		elseif ($a_checked == "false")
		{
			$q .= " AND fs.checkdate IS NULL";
		}

		// filter users
		if (count($a_user_ids))
		{
			$quoted = array();
			foreach ($a_user_ids as $id)
			{
				$quoted[] = $ilDB->quote($id, 'integer');
			}
			
			$q .= " AND fs.user_id IN (".implode(",",$quoted).")";
		}

		// filter forms
		if (count($a_form_ids))
		{
			$quoted = array();
			foreach ($a_form_ids as $id)
			{
				$quoted[] = $ilDB->quote($id, 'integer');
			}
			$q .= " AND fs.form_id IN (".implode(",",$quoted).")";
		}

		// sort
		$q .= " ORDER BY fs.savedate DESC";

		$data = array();
		$result = $ilDB->query($q);
		while ($row = $ilDB->fetchAssoc($result))
		{
			$row["username"] = $row["lastname"] . ", ". $row["firstname"];
			$data[] = $row;
		}
		return $data;
	}

	/**
	* Set a form saving as corrected
	*
	* @param    int         save_id
	* @param    boolean     true, if it is corrected
	* @access	public
	*/
	function _setCorrected($a_save_id, $a_corrected = true)
	{
		global $ilDB;
		
		if ($a_corrected)
		{
			$checkdate = date("y-m-d H:i:s");
		}
		else
		{
			$checkdate = null;
		}

		$q = "UPDATE webform_savings SET checkdate = ". $ilDB->quote($checkdate, 'timestamp')
			. " WHERE save_id = ". $ilDB->quote($a_save_id, 'integer');
		$ilDB->manipulate($q);
	}

	/**
	* Set a form saving as sent (with savedate as senddate)
	*
	* @param    int         save_id
	* @access	public
	*/
	function _setSent($a_save_id)
	{
		global $ilDB;
		
		$q = "UPDATE webform_savings SET senddate = savedate WHERE save_id = ". $ilDB->quote($a_save_id, 'integer');
		$ilDB->manipulate($q);
	}
	
	
	/**
	* Writes a form saving to the database
	* @param    boolean     true, if it is a sending
	* @access	public
	*/
	function create($a_send)
	{
		global $ilDB;

	    $savedate = date("y-m-d H:i:s");
		if ($a_send)
		{
	    	$senddate = date("y-m-d H:i:s");
		}
		else
		{
			$senddate = null;
		}

		$save_id = $ilDB->nextID('webform_savings');
		$query = "INSERT INTO webform_savings(save_id, form_id, dataset_id,savedate,senddate,checkdate,is_forum_saving,user_id) "
				."VALUES (".$ilDB->quote($save_id,'integer')
				.",".$ilDB->quote($this->form->getFormId(),'integer')
				.",".$ilDB->quote($this->dataset_id,'text')
				.",".$ilDB->quote($savedate,'timestamp')
				.",".$ilDB->quote($senddate,'timestamp')
				.",".$ilDB->quote($this->getCheckdate(), 'timestamp')
				.",".$ilDB->quote($this->getIsForumSaving(),'integer')
				.",".$ilDB->quote($this->getUserId(),'integer')
				.")";
           
		if (!$ilDB->manipulate($query))
		{
			return false;
		}
		$this->save_id = $save_id;

		// Write the form content
		$this->writeEntries();

		// delete all old savings which are not sent
		$query = "SELECT save_id FROM webform_savings"
			." WHERE form_id=". $ilDB->quote($this->form->getFormId(),'integer')
			." AND dataset_id=". $ilDB->quote($this->getDatasetId(),'text')
			." AND user_id=". $ilDB->quote($this->getUserId(),'integer')
			." AND save_id<>". $ilDB->quote($this->getSaveId(),'integer')
			." AND senddate IS NULL"
			." AND is_forum_saving = 0";

		$result = $ilDB->query($query);
		if ($result)
		{
			while ($row = $ilDB->fetchAssoc($result))
			{
				self::_delete($row["save_id"]);
			}
		}

		return true;
	}

	/**
	* Read a form saving from the database
	* @param 	bool 	If false, then do not read the saving entries
	* @access	public
	*/
	function read($read_entries = true)
	{
		global $ilDB, $ilUser;
		
		if (!is_null($this->save_id))
		{
			$query = "
				SELECT * FROM webform_savings
				WHERE save_id = ". $ilDB->quote($this->save_id,'integer');
		}
		else
		{
			$query = "
				SELECT * FROM webform_savings
				WHERE form_id = ".$ilDB->quote($this->form_id, 'integer'). "
				AND user_id = ".$ilDB->quote($ilUser->getId(),'integer'). "
				ORDER BY savedate DESC";
		}

		$result = $ilDB->query($query);
		if ($row = $ilDB->fetchAssoc($result))
		{
			$this->setSaveId($row["save_id"]);
			$this->setUserId($row["user_id"]);
			$this->setFormId($row["form_id"]);
			$this->setDatasetId($row["dataset_id"]);
			$this->setSavedate($row["savedate"]);
			$this->setSenddate($row["senddate"]);
			$this->setCheckdate($row["checkdate"]);
			$this->setIsForumSaving($row["is_forum_saving"]);
		}

		// Anyhow if we exist in db or not,
		// assign the fields from other savings for our dataset id
		if ($read_entries)
		{
			$this->readEntries();		
		}
	}
	
	/**
	* Read all entries of this saving and all entries of the dataset
	* assigns the data to the object members "entries" and "all_entries".
	* TODO: is slow. Only the youngest savings for the same dataset should be read, not all savings.
	* @access	private
	*/
	function readEntries()
	{
		global $ilDB;

		// First read our own entries
		$q = "SELECT * FROM webform_entries WHERE save_id = ".$ilDB->quote($this->getSaveId(),'integer');
		$result = $ilDB->query($q);
 		while ($row = $ilDB->fetchAssoc($result))
		{
			$this->entries[$row["fieldname"]] = $row["fieldvalue"];
		}

		// Now read all entries of the dataset
		$q = "SELECT * FROM webform_savings fs
				INNER JOIN webform_types ft ON fs.form_id = ft.form_id".
				" WHERE ft.dataset_id=".$ilDB->quote($this->getDatasetId(),'text').
				" AND ft.lm_obj_id=".$ilDB->quote($this->form->getLmObjId(),'integer').
				" AND fs.user_id = ".$ilDB->quote($this->getUserId(),'integer').
				" ORDER BY savedate ASC";

		$result = $ilDB->query($q);
		while ($saving_row = $ilDB->fetchAssoc($result))
		{
    		$q =
			"SELECT * FROM webform_entries WHERE save_id=".$ilDB->quote($saving_row["save_id"],'integer')
			." ORDER BY entry_id";
			$entries_set = $ilDB->query($q);
 			while ($row = $ilDB->fetchAssoc($entries_set))
			{
				$this->all_entries[$row["fieldname"]] = $row["fieldvalue"];
			}
		}
	}


	/**
	* Updates the current object members to the db
	* @param 	bool 	If false, then do not write the saving entries
	* @access	public
	*/
	function update($write_entries = true)
	{
		global $ilDB;
		
		$q = "UPDATE webform_savings SET "
			. "user_id = ".$ilDB->quote($this->getUserId(),'integer')
			. ",form_id = ".$ilDB->quote($this->getFormId(),'integer')
			. ",dataset_id = ".$ilDB->quote($this->getDatasetId(),'text')
			. ",savedate = ".$ilDB->quote($this->getSavedate(), 'timestamp')
			. ",senddate = ".$ilDB->quote($this->getSenddate(), 'timestamp')
			. ",checkdate = ".$ilDB->quote($this->getCheckdate(),'timestamp')
			. ",is_forum_saving = ".$ilDB->quote($this->getIsForumSaving(),'integer')
			. " WHERE save_id = ".$ilDB->quote($this->getSaveId(),'integer');
				
		$ilDB->manipulate($q);
				
		// Write the entries
		if ($write_entries)
		{
			$this->writeEntries();
		}
	}
	
	/**
	* Write the entries to the db
	* The entries are also written into savings with the same dataset.
	* But only for savings wich are no sendings.
	* TODO: check implementation
	* @param	boolean 	If false, then only the own entries are written
	* @access	private
	*/
	function writeEntries($write_dataset = true)
	{
		global $ilDB;
		
		//First write our own entries
		while (list ($fieldname, $fieldvalue) = each($this->entries))
		{
			$entry_id = $ilDB->nextID('webform_entries');
			$query = "INSERT into webform_entries(entry_id,save_id,fieldname,fieldvalue) "
					."VALUES (".$ilDB->quote($entry_id,'integer')
					.",".$ilDB->quote($this->getSaveId(),'integer')
					.",".$ilDB->quote($fieldname,'text').",".$ilDB->quote($fieldvalue,'text')
					.")";
			$result = $ilDB->manipulate($query);
		}
		
		// Now write our own entries to all other savings with the same dataset
		if ($write_dataset)
		{
			$q = "SELECT * FROM webform_savings AS fs
					INNER JOIN webform_types AS ft ON (fs.form_id = ft.form_id)".
					" WHERE fs.senddate is NULL AND ft.form_id is NOT NULL ".
					" AND ft.lm_obj_id=".$ilDB->quote($this->form->getLmObjId(),'integer').
					" AND fs.dataset_id=".$ilDB->quote($this->getDatasetId(),'text').
					" AND fs.save_id<>".$ilDB->quote($this->getSaveId(),'integer').
					" AND fs.user_id = ".$ilDB->quote($this->getUserId(),'integer');

			$result = $ilDB->query($q);
			while ($saving_row = $ilDB->fetchAssoc($result))
			{
	    		$q = "select * FROM webform_entries".
						" WHERE save_id=".$ilDB->quote($saving_row["save_id"],'integer').
						" ORDER BY entry_id";
				$entries_set = $ilDB->query($q);
 				while ($row = $ilDB->fetchAssoc($entries_set))
				{
					$fname = $row["fieldname"];
					$fvalue = $row["fieldvalue"];
					$entry_id = $row["entry_id"];
					$save_id = $row["save_id"];
					if (isset($this->entries[$fname] ))
					{
						// fred: does this write the old entries again ???
						$q = "UPDATE webform_entries SET".
							" fieldname =".$ilDB->quote($fname,'text').
							",fieldvalue =".$ilDB->quote($fvalue,'text').
							" WHERE entry_id = ".$ilDB->quote($entry_id,'integer')
							;
					}
					else
					{
						$entry_id = $ilDB->nextID('webform_entries');
						// fred: does this write the old entries again ???
						$query = "INSERT into webform_entries(entry_id,save_id,fieldname,fieldvalue) "
								."VALUES (".$ilDB->quote($entry_id,'integer')
								.",".$ilDB->quote($save_id,'integer')
								.",".$ilDB->quote($fname,'text').",".$ilDB->quote($fvalue,'text')
								.")";
					}				
					$ilDB->manipulate($q);
				}
			}
		
		} // END if ($write_dataset)		
	}

	/**
	* Write the saving to the forum specified in the form definition
	* TODO: localize messages
	* @access	public
	*/
	function writeToForum()
	{
		global $ilDB, $ilUser, $ilErr, $ilAccess;
		require_once ("./Services/WebForm/classes/class.ilWebFormUtils.php");
		
		$forumRefId = ilWebFormUtils::_findForumRefId($this->form->getForum(),$this->form->getLmObjId());
		if (!$forumRefId)
		{
			$ilErr->raiseError("Forum \"". $this->form->getForum() . "\" nicht gefunden.",$ilErr->FATAL);
		}
		if ($this->form->hasForumParent())
		{
			if (!$ilAccess->checkAccess("add_reply","", $forumRefId))
			{
				$ilErr->raiseError("Daten k&ouml;nnen nicht gesendet werden. Sie k&ouml;nnen keine Antwort im Forum \"". $this->form->getForum() . "\" erstellen.",$ilERR->FATAL);
				exit;
			}
		}
		else
		{
			if (!$ilAccess->checkAccess("add_thread","", $forumRefId))
			{
				$ilErr->raiseError("Daten k&ouml;nnen nicht gesendet werden. Sie k&ouml;nnen kein Thema im Forum \"". $this->form->getForum() . "\" erstellen.",$ilERR->FATAL);
				exit;
			}
			
		}
		
		
		// Create the forum object and attach to the correct forum (copied from forums_threads_new.php))
		require_once "./Modules/Forum/classes/class.ilObjForum.php";
		$forumObj = new ilObjForum($forumRefId);
		$frm = $forumObj->Forum;
		$frm->setForumId($forumObj->getId());
		$frm->setForumRefId($forumObj->getRefId());
		$frm->setMDB2WhereCondition("top_frm_fk = %s", array('integer'), array($frm->getForumId()));
		$topicData = $frm->getOneTopic();
		
		// Create href to form	
		$linkToForm = $this->form->makeFormLink($this->getSaveId(),"user");
		$message = "Formular "
					. "<a href=\"".$linkToForm."\" target=\"_blank\">". $this->form->getTitle() . "</a>:"
					. "<br/>";
		
		foreach ($this->entries as $fname =>$fvalue)
		{
			$message .= "<br/><b>".str_replace("_", " ", $fname)."</b>:<br/>$fvalue";
		}		

		// Note: Text form definition is utf-8, text written here is iso-8859-1
		$subject =	$this->form->hasForumSubject()
					? $this->form->getForumSubject()
					: utf8_encode("Einsendung f�r das Formular ") .$this->form->getTitle();
				
		if ($this->form->hasForumParent())
		{
			$threadInfo = ilWebFormUtils::_findForumThreadId($topicData["top_pk"], $this->form->getForumParent());
			if (!isset($threadInfo["thr_pk"]))
			{
				$ilErr->raiseError("Thread \"" .$this->form->getForumParent(). "\" in Forum \"". $this->form->getForum() . "\" nicht gefunden.",$ilErr->FATAL);
			}
			$newPost = $frm->generatePost($topicData["top_pk"], $threadInfo["thr_pk"],
										  $ilUser->getId(), $message,
										  $threadInfo["pos_pk"] , false, $subject);
		}
		else
		{
			$newPost = $frm->generateThread($topicData["top_pk"], $ilUser->getId(),
			$subject, $message, false, false);
		}
		
		// The forum interface strips tags in the message,
		// therefore we must update the db with our raw message.
		$q = "UPDATE frm_posts SET pos_message = ".$ilDB->quote($message,'text')
				. " WHERE pos_pk = ".$ilDB->quote($newPost,'integer');
		$ilDB->manipulate($q);
	}


	/**
	* delete the form saving and all related data	
	* @return	boolean		true if all object data were removed
	* @access	public
	*/
	function delete()
	{
		return self::_delete($this->save_id);
	}
	
	
	/**
	* delete the form saving and all related data
	* @param    int     id of the saving
	* @access	static
	*/
	function _delete($save_id)
	{
		global $ilDB;

		$result = $ilDB->manipulate("DELETE FROM webform_savings WHERE save_id=".$ilDB->quote($save_id,'integer'));
		$result = $ilDB->manipulate("DELETE FROM webform_entries WHERE save_id=".$ilDB->quote($save_id,'integer'));
	}
	
	// Getters
	function getForm()
	{
		return $this->form;
	}
	function getFormId()
	{
		return $this->form_id;
	}
	function getDatasetId()
	{
		return $this->dataset_id;
	}
	function getSaveId()
	{
		return $this->save_id;
	}
	function getUserId()
	{
		return $this->user_id;
	}
	function getEntries()
	{
		return $this->entries;
	}
	function getAllEntries()
	{
		return $this->all_entries;
	}
	function getSavedate()
	{
		return $this->savedate;
	}
	function getSenddate()
	{
		return $this->senddate;
	}
	function getCheckdate()
	{
		return $this->checkdate;
	}
	function getIsForumSaving()
	{
		return $this->is_forum_saving ? 1 : 0;
	}


	// Setters
	function setForm($form)
	{
		$this->form = $form;
	}
	function setFormId($form_id)
	{
		$this->form_id = $form_id;
	}
	function setDatasetId($val)
	{
		$this->dataset_id = $val;
	}
	function setSaveId($save_id)
	{
		$this->save_id = $save_id;
	}
	function setUserId($user_id)
	{
		$this->user_id = $user_id;
	}
	function setEntries($entries)
	{
		$this->entries = $entries;
	}
	function setSavedate($val)
	{
		$this->savedate = $val;
	}
	function setSenddate($val)
	{
		$this->senddate = $val;
	}
	function setCheckdate($val)
	{
		$this->checkdate = $val;
	}
	function setIsForumSaving($val)
	{
		$this->is_forum_saving = $val ? 1 : 0;
	}
	
	/**
	* Check, if this saving is corrected
	* @return   boolean     True, if corrected
	*/
	function isCorrected()
	{
		return !(is_null($this->checkdate));
	}
	
	/**
	* Sets the saving as corrected or uncorrected
	* @param    boolean     True, to set corrected
	*/
	function setCorrected($corrected = true)
	{
		if ($corrected)
		{
			$this->setCheckdate(date("y-m-d H:i:s"));
		}
		else
		{
			$this->setCheckdate(null);
		}
	}
	
} // END class.ilWebFormSaving

?>
