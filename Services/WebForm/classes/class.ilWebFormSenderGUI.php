<?php
/* fim: [webform] new class. */

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once "./Services/WebForm/classes/class.ilWebForm.php";
require_once "./Services/WebForm/classes/class.ilWebFormSaving.php";

/**
* Class ilWebFormSenderGUI
*
* @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
* $Id: $
*/

class ilWebFormSenderGUI
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
	* Constructor
	* @access	public
	*/
	function ilWebFormSenderGUI()
	{
		global $ilDB, $ilErr, $ilUser, $lng;
		
		// load messages
		$lng->loadLanguageModule("webform");

		// Error handling
		$ilDB->setErrorHandling(PEAR_ERROR_CALLBACK, array($this,'errorHandler'));
		$ilErr->setErrorHandling(PEAR_ERROR_CALLBACK, array($this,'errorHandler'));

		// get the form definition
		$this->form = new ilWebForm($_REQUEST["form_id"], null, null);
		$this->form->read();

		// prepare the saving to be written
		$this->saving = new ilWebFormSaving(
			$this->form,
			$ilUser->getId(),
			$this->form->getDatasetId(),
			null,
			$this->makeEntries($_POST)
		);
	}
	
	/**
	* Execute the request
	* @access	public
	*/
	function execute()
	{
		global $ilAuth;
		
		// user authentication
		if (!$ilAuth->getAuth())
		{
			$this->showLoginForm();
			return;
		}

		// log the sending as early as possible
		$this->logSending();
		
		// check if the form definition exists
		if (!$this->form->hasData())
		{
			$this->sendResponse("Verarbeitungsfehler", "Die Formulardefinition wurde nicht gefunden.", true);
			return;
		}

		// execute the request
		if (isset($_REQUEST["form_save"]))
		{
			$this->writeFormSaving(false);
		}
		elseif (isset($_REQUEST["form_send"]))
		{
			$this->writeFormSaving(true, false);
		}
		elseif (isset($_REQUEST["form_forum_send"]))
		{
			$this->writeFormSaving(true, true);
		}
		else
		{
			$this->sendResponse("Verarbeitungsfehler", "Ein unbekanntes Kommando wurde gesendet.", true);
		}
	}
	
	/**
	* Remove all "meta data" from the given field list.
	* The returned array will be used as an "entries" array for form savings.
	* @param    array   raw data
	* @return   array   pure entries
	* @access   private
	*/
	function makeEntries($data)
	{
		$ret = array();
		foreach ($data as $key => $val)
		{
			if (!ereg("^form_",$key))
			{
				$ret[ilUtil::stripSlashes($key, false)] = ilUtil::stripSlashes($val, false);
			}
		}
		return $ret;
	}

	/**
	* Write a form saving into the database
	* @param 	bool  	If true, then send, otherwise save only
	* @param 	bool  	If true, then send it to the forum
	* @access	public
	*/
	function writeFormSaving($a_send = false, $a_forum = false)
	{
		// Sending after max date is not allowed
		if ($a_send and $this->form->hasSendMaxdate()
			and strtotime($this->form->getSendMaxdate()) < time())
		{
			// just save the entries
			$this->saving->create(false);

			$date = date("d.m.Y H:i", strtotime($this->form->getSendMaxDate()));
			$msg = "Die Einsendefrist $date ist abgelaufen.<br>"
					. "Ihre Eingaben wurden gespeichert aber nicht gesendet.";
			$this->sendResponse("Einsendefrist abgelaufen!", $msg, true);
		}
		
		// Send to forum
		elseif ($a_send and $a_forum)
		{
			// save with senddate and write to forum
		    $this->saving->setIsForumSaving(true);
			$this->saving->create(true);
			$this->saving->writeToForum();
			
			$msg = 'Ihr Beitrag wurde an das Forum '
			. '<a href="'.$this->form->makeForumLink().'" target="_blank">'.$this->form->getForum().'</a> '
			. 'gesendet.<br><br>'
			. 'Diese Einsendung wird nicht mehr ver&auml;ndert. '
			. 'Sie k&ouml;nnen aber f&uuml;r sich weitere &Auml;nderungen speichern '
			. 'und diese dann erneut einsenden.';

        	$this->sendResponse("Beitrag gesendet", $msg);
		}
		
		// Send to tutor
		elseif ($a_send)
		{
			// save with senddate
			$this->saving->create(true);

			$msg = "Ihre Eingaben wurde an die Tutoren eingesandt.<br><br>"
			. "Diese Einsendung wird nicht mehr ver&auml;ndert. "
			. "Falls n&ouml;tig, k&ouml;nnen Sie aber f&uuml;r sich weitere &Auml;nderungen speichern, "
			. "und diese dann erneut einsenden.<br><br>"
			. "Ihre Einsendungen k&ouml;nnen Sie im Kurs "
			. "unter [Einsendungen] jedezeit wieder aufrufen. "
			. "Dort finden Sie auch, sofern vorhanden, die Musterl&ouml;sungen.";

        	$this->sendResponse("Aufgabe gesendet", $msg);
		}
		
		// Save private
		else
		{
   			// just save the entries
			$this->saving->create(false);
       		ilUtil::redirect($this->form->makeFormLink());
		}
	}

	/**
	* Generate a response page
	* @param    string      title
	* @param    string      message text
	* @param    boolean     True: is an error message
	* @access	public
	*/
	function sendResponse($title, $message, $is_error = false)
	{
		global $tpl;
		
		$tpl->addBlockFile("CONTENT", "content", "tpl.form_sent.html", "Services/WebForm");

		if ($is_error)
		{
			$tpl->setCurrentBlock("error");
			$tpl->setVariable("ERROR", $message);
			$tpl->parseCurrentBlock();
		}
		else
		{
			$tpl->setCurrentBlock("message");
			$tpl->setVariable("MESSAGE", $message);
			$tpl->parseCurrentBlock();
		}

		$tpl->setCurrentBlock("content");
		$tpl->setVariable("TITLE", $title);
		$tpl->setVariable("LINK_FORM", $this->form->makeFormLink());
		$tpl->setVariable("LABEL_FORM", "[Formular wieder anzeigen]");
		$tpl->setVariable("LINK_CLOSE", "javascript:close()");
		$tpl->setVariable("LABEL_CLOSE", "[Fenster schlie&szlig;en]");

		$tpl->parseCurrentBlock();
		$tpl->show(false,false);

		exit;
	}

	/**
	* Show a form to re-login after timeout
	* @access	public
	*/
	function showLoginForm()
	{
		global $tpl;

		$tpl->addBlockFile("CONTENT", "content", "tpl.form_login.html", "Services/WebForm");

		foreach ($_POST as $name => $value)
		{
			if ($name != "username" and $name != "password")
			{
				$name = htmlspecialchars(ilUtil::stripSlashes($name, false));
				$value = htmlspecialchars(ilUtil::stripSlashes($value, false));

				$tpl->setCurrentBlock("hidden_field");
				$tpl->setVariable("HIDDEN_NAME", $name);
				$tpl->setVariable("HIDDEN_VALUE", $value);
				$tpl->parseCurrentBlock();
			}
		}

		$tpl->setCurrentBlock("content");
		$tpl->setVariable("USERNAME", $_POST["username"]);
		$tpl->setVariable("FORM_ACTION", $this->form->makeSendLink());
		$tpl->parseCurrentBlock();
		$tpl->show(false,false);

		exit;
	}

	/**
	* General error handler for all form server errors
	* Returns a response page containing an error message.
	* @param    object  error object
	* @access   private
	*/
	function errorHandler($error)
	{
		$this->sendResponse('Verarbeitungsfehler', $error->getMessage(), true);
	}
	

	/**
	* write a log for the current sending
	* @access   private
	*/
	function logSending()
	{
		global $ilCust, $ilUser;
		
		if (!$logfile = $ilCust->getSetting("webform_log"))
		{
			return;
		}
		
		$fp = fopen($logfile, 'a');
		
		fwrite($fp, "\n# ". date('Y-m-d H:i:s', time()));
		fwrite($fp, "\n# ". $ilUser->getLogin(). " \n");

		foreach ($_POST as $key => $value)
		{
			fwrite($fp, $key
						. ' = "'
						. htmlspecialchars(ilUtil::stripSlashes($value, false))
			            . '"'."\n");
		}
		fclose($fp);
	}
}
?>
