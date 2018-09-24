<?php
/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once("./Services/User/classes/class.ilObjUser.php");

/**
* Class for vhb authentication
*
* @author Fred Neumann <fred.neumann@fim.uni-erlangen.de> 
*
*/
class ilVhbAuth
{
	/**
	 * @var array session data stored for redirects or interactive course selection
	 */
	private $session = array();

	/**
	 * @var array data posted from the vhb
	 */
	private $data = array();

	private $salt = '';
	private $checked_by_hash = false;
	private $academy = "";
	private $error_suffix = "";
	private $fallback_password = "";

	private $courses = array(); 	// found courses: [ref_id => int, obj_id => int, title => string], [...]
	private $course_id = 0;			// selected course
	private $course_ref_id = 0;		// selected course
	
	private $user_obj = null;

	/**
	 * ilVhbAuth constructor.
	 */
	function __construct()
	{
		global $DIC;
		/** @var ilAuthSession $ilAuthSession */
		$ilAuthSession = $DIC['ilAuthSession'];

		$this->user_obj = new ilObjUser();

		if (!$ilAuthSession->isAuthenticated())
		{
			$ilAuthSession->setAuthenticated(true, ANONYMOUS_USER_ID);
			//$_SESSION['AccountId'] = ANONYMOUS_USER_ID;
		}

		$this->readSessionData();
	}

	/**
	 * Read data from a temporary vhb session
	 * This allows to get the original posted data after redirects or login
	 * The temporary session is destroyed after reading
	 */
	private function readSessionData()
	{
		// $_SESSION does not work when user is already logged in
		// so we use ilSession for an own pseudeo session, based on a GET parameter
		// we can destroy the pseudo session after being read

		if (!empty($_GET['vhbsession']))
		{
			require_once("./Services/Authentication/classes/class.ilSession.php");
			$data = ilSession::_getData($_GET['vhbsession']);
			ilSession::_destroy($_GET['vhbsession']);
		}
		$this->session = !empty($data) ? unserialize($data) : array();
	}


	/**
	 * Write data to a temporary vhb session
	 * This allows to set the original posted data for redirects or login
	 * @return	string	md5 code of the written vhb session
	 */
	private function writeSessionData()
	{
		require_once("./Services/Authentication/classes/class.ilSession.php");

		$data = serialize($this->session);
		$vhbsession = md5($data);
		ilSession::_writeData($vhbsession, $data);
		return $vhbsession;
	}

	public function setAcademy($a_academy)
	{
		$this->academy = $a_academy;
	}

	public function setCheckingSalt($salt = '')
	{
		$this->salt = $salt;
	}

	public function setErrorSuffix($a_suffix)
	{
		$this->error_suffix = $a_suffix;
	}
	
	public function setFallbackPassword($a_password)
	{
		$this->fallback_password = $a_password;
	}

	/**
	 * Set the authentication data either from POST or from the temporary session
	 */
	public function setPostedData()
	{
		$this->data = array();

		if (!empty($_POST['LVNR']))
		{
			$this->data["login"] = 			utf8_encode($_POST['LOGIN']);
			$this->data["passwort"] = 		utf8_encode($_POST['PASSWORT']);
			$this->data["dataversion"] = 	utf8_encode($_POST['DATAVERSION']);
			$this->data["bezeichnung"] = 	utf8_encode($_POST['BEZEICHNUNG']);
			$this->data["lvnr"] = 			utf8_encode($_POST['LVNR']);
			$this->data["vorname"] = 		utf8_encode($_POST['VORNAME']);
			$this->data["nachname"] = 		utf8_encode($_POST['NACHNAME']);
			$this->data["strasse"] = 		utf8_encode($_POST['STRASSE']);
			$this->data["plz"] = 			utf8_encode($_POST['PLZ']);
			$this->data["ort"] = 			utf8_encode($_POST['ORT']);
			$this->data["email"] = 			utf8_encode($_POST['EMAIL']);
			$this->data["hochschule"] = 	utf8_encode($_POST['HOCHSCHULE']);
			$this->data["sex"] = 			utf8_encode($_POST['SEX']);
			$this->data["gebdat"] = 		utf8_encode($_POST['GEBDAT']);
			$this->data["status"] = 		utf8_encode($_POST['STATUS']);
			$this->data["anmeldung"] = 		utf8_encode($_POST['ANMELDUNG']);
			$this->data["studienfach"] = 	utf8_encode($_POST['STUDIENFACH']);
			$this->data["abschluss"] = 		utf8_encode($_POST['ABSCHLUSS']);
			$this->data["matrikelnummer"] = utf8_encode($_POST['MATRIKELNUMMER']);
			$this->data["hash"] = 			utf8_encode($_POST['HASH']);

			$this->session['data'] = $this->data;
			$this->session['checked_by_hash'] = false;
		}
		elseif (!empty($this->session['data']))
		{
			$this->data = $this->session['data'];
		}
	}

	/**
	 * Check if the actual base url is the same as configured in ilias.ini.php
	 * Write the temporary session data and redirect if the url is different
	 */
	public function checkUrl()
	{
		global $ilIliasIniFile;
		$ini_http_path = $ilIliasIniFile->readVariable("server","http_path");

		if (ILIAS_HTTP_PATH != $ini_http_path)
		{
			$vhbsession = $this->writeSessionData();
			ilUtil::redirect($ini_http_path. '/vhblogin.php?vhbsession='.$vhbsession);
		}
	}


	public function checkData()
	{
		if ($this->session['checked_by_hash'])
		{
			$this->checked_by_hash = true;
		}
		elseif (!empty($this->salt))
		{
			$raw =  $_POST['LOGIN'].
					$_POST['PASSWORT'].
					$_POST['LVNR'].
					$_POST['BEZEICHNUNG'].
					$_POST['VORNAME'].
					$_POST['NACHNAME'].
					$_POST['STRASSE'].
					$_POST['PLZ'].
					$_POST['ORT'].
					$_POST['EMAIL'].
					$_POST['HOCHSCHULE'].
					$_POST['STUDIENFACH'].
					$_POST['ABSCHLUSS'].
					$this->salt;
					
			$hash = md5($raw);

			if ($hash != $_POST['HASH'])
			{
				$this->showError("Die Pr&uuml;fsumme " . $hash . " Ihrer Daten aus dem vhb-Portal ist nicht korrekt.");
			}
			else
			{
				$this->checked_by_hash = true;
				$this->session['checked_by_hash'] = true;
			}
		}		
		
		if (!strlen($this->data["login"]))
		{
		    $this->showError("Login nicht gesetzt.");
		}
		if (!strlen($this->data["vorname"]))
		{
		    $this->showError("Vorname nicht gesetzt.");
		}
		if (!strlen($this->data["nachname"]))
		{
		    $this->showError("Nachname nicht gesetzt.");
		}
		if (!strlen($this->data["lvnr"]))
		{
		    $this->showError("Kurs nicht gesetzt.");
		}
	}

	public function checkPassword()
	{
		require_once "./Services/User/classes/class.ilUserPasswordManager.php";
		$pmObj = ilUserPasswordManager::getInstance();
		return $pmObj->verifyPassword($this->user_obj, $this->data["passwort"]);
	}
	
	public function checkFallbackPassword()
	{
		require_once "./Services/User/classes/class.ilUserPasswordManager.php";
		$pmObj = ilUserPasswordManager::getInstance();
		return $pmObj->verifyPassword($this->user_obj, $this->fallback_password);
	}
	
	public function getData()
	{
		return $this->data;
	}
	
	public function getUserId()
	{
		return $this->user_obj->getId();
	}

	public function isExternal()
	{
		if (strcasecmp($this->data["hochschule"],  $this->academy) == 0)
		{
			return false;
		}
		else
		{
			return true;
		}
	}
	
	public function isUserActive()
	{
		return $this->user_obj->getActive();
	}
	
	public function isCheckedByHash()
	{
		return (bool) $this->checked_by_hash;
	}

	public function findCourse()
	{
		global $ilDB;

		require_once("./Modules/Course/classes/class.ilObjCourseAccess.php");
		require_once("./Modules/Course/classes/class.ilCourseParticipants.php");

		// find vhb course by catalog (not nice to use db here)
		$query = "SELECT o.obj_id, o.title, m.entry FROM il_meta_identifier m ".
			" INNER JOIN object_data o ON m.obj_id = o.obj_id ".
			" WHERE m.obj_type = 'crs'".
			" AND m.catalog = 'vhb'";
		$res = $ilDB->query($query);

		while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC))
		{
			// use file name matching with wildcards to get courses with the LV number
			// semester independend courses can have the following entries: LV_328_822_1_*_1
			if (fnmatch(trim($row['entry']), $this->data["lvnr"]))
			{
				if (ilObject::_hasUntrashedReference($row["obj_id"])) {
					if (ilObjCourseAccess::_isActivated($row["obj_id"])) {
						foreach (ilObject::_getAllReferences($row["obj_id"]) as $ref_id)
						{
							$this->course_id = $row["obj_id"];
							$this->course_ref_id = $ref_id;

							if ($this->getUserId() and ilCourseParticipants::_isParticipant($ref_id, $this->getUserId())) {
								return $this->course_id;
							}

							$this->courses[$ref_id] = array(
								'ref_id' => $ref_id,
								'obj_id' => $row["obj_id"],
								'title' => $row['title']
							);
						}
					}
				}
			}
		}

		// one course is found
		if (count($this->courses) == 1)
		{
			return $this->course_id;
		}
		// multiple courses are found
		elseif (count($this->courses) > 1)
		{
			// course is already selected
			if (!empty($_GET['course_ref_id']) and !empty($this->courses[$_GET['course_ref_id']]))
			{
				$this->course_ref_id = $_GET['course_ref_id'];
				$this->course_id = $this->courses[$_GET['course_ref_id']]['obj_id'];
				return $this->course_id;
			}
			// course has to be selected
			else
			{
				$this->courses = ilUtil::sortArray(array_values($this->courses),'title','asc');
				$this->showCourseSelection();
			}
		}
		// no course is found
		else
		{
			$this->showError("
			Der Kurs wurde nicht gefunden oder ist noch nicht verf&uuml;gbar!<br />
			Evtl. ist die vhb-Leistungsnummer noch nicht eingetragen oder aktualisiert.<br />
			");
		}
	}

	public function findUser()
	{
		global $ilDB;

		// user is already found
		if ($this->user_obj->getId())
		{
			return $this->user_obj->getId();
		}
		
		if ($this->isExternal())
		{
			// find external users by login as matriculation
			$query = "SELECT usr_id FROM usr_data".
				" WHERE matriculation=" . $ilDB->quote($this->data["login"]);
		}
		else
		{
			// find internal user by login part as matriculation
			$query = "SELECT usr_id FROM usr_data".
				" WHERE matriculation=" . $ilDB->quote($this->data["matrikelnummer"]);
		}

		$res = $ilDB->query($query);
		if ($row = $res->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$this->user_obj = new ilObjUser($row["usr_id"]);
		}
		else
		{
			$this->user_obj = new ilObjUser();
		}

		return $this->user_obj->getId();
	}

	
	public function createUser($a_login_prefix, $a_password, $a_matriculation, $a_auth_mode = "")
	{
		global $ilias;
		
		$this->user_obj = new ilObjUser();
		
		// set arguments (may differ between local and external users)
		$this->user_obj->setLogin($a_login_prefix.$a_matriculation);
		$this->user_obj->setPasswd($a_password, IL_PASSWD_PLAIN);
		$this->user_obj->setMatriculation($a_matriculation);
		if ($a_auth_mode)
		{
			$this->user_obj->setAuthMode($a_auth_mode);
		}
		
		// set other posted user data
		$this->user_obj->setGender($this->data["sex"] == "weiblich" ? "f" : "m");
		$this->user_obj->setFirstname($this->data["vorname"]);
		$this->user_obj->setLastname($this->data["nachname"]);
		$this->user_obj->setInstitution($this->data["hochschule"]);
		$this->user_obj->setStreet($this->data["strasse"]);
		$this->user_obj->setZipcode($this->data["plz"]);
		$this->user_obj->setCity($this->data["ort"]);
		$this->user_obj->setEmail($this->data["email"]);

		// set dependent data
		$this->user_obj->setFullname();
		$this->user_obj->setTitle($this->user_obj->getFullname());
		$this->user_obj->setDescription($this->user_obj->getEmail());
		$this->user_obj->setTimeLimitOwner(7);
		$this->user_obj->setTimeLimitUnlimited(1);     // TODO: may be different for external users
		$this->user_obj->setTimeLimitFrom(time());		// ""
		$this->user_obj->setTimeLimitUntil(time());    // ""

		// create the user object
		$this->user_obj->create();
		$this->user_obj->setActive(1);
		$this->user_obj->updateOwner();
		$this->user_obj->saveAsNew();
		// $this->user_obj->writeAccepted();

		//set personal preferences
		$this->user_obj->setLanguage("de");
		$this->user_obj->setPref("hits_per_page", max($ilias->getSetting("hits_per_page"),10));
		$this->user_obj->setPref("public_profile", "n");
		$this->user_obj->setPref("show_users_online", "n");
		$this->user_obj->setPref("hide_own_online_status", "y");
		$this->user_obj->writePrefs();

		// DATA PROTECTION: user login will be anonymized
		$this->setNewLogin($a_login_prefix.$this->user_obj->getId());
	}


	private function setNewLogin($a_new_login)
	{
		global $ilDB;

		$query = "SELECT usr_id FROM usr_data"
			. " WHERE login = " . $ilDB->quote($a_new_login, 'text')
			. " AND usr_id <> " . $ilDB->quote($this->user_obj->getId(), 'integer');
		$res = $ilDB->query($query);

		if ($row = $res->fetchRow(DB_FETCHMODE_ASSOC))
		{
		    return false;
		}

		$query = "UPDATE usr_data "
				. " SET login = " . $ilDB->quote($a_new_login, 'text')
				. " WHERE usr_id = " . $ilDB->quote($this->user_obj->getId(), 'integer');
        $ilDB->manipulate($query);
        
        $this->user_obj->setLogin($a_new_login);
	}


	public function assignRole($a_role_name)
	{
		global $ilDB, $rbacadmin;

		// search for role by title (not nice to use db here)
		$query = "SELECT obj_id FROM object_data".
			" WHERE title=" . $ilDB->quote($a_role_name).
			" AND type ='role'";
		$res = $ilDB->query($query);
		if ($row = $res->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$rbacadmin->assignUser($row["obj_id"], $this->user_obj->getId(), true);
		}
	}
	
	public function assignCourse()
	{
		require_once("./Modules/Course/classes/class.ilCourseParticipants.php");
		$cp = ilCourseParticipants::_getInstanceByObjId($this->course_id);
		$cp->add($this->user_obj->getId(), IL_CRS_MEMBER);
	}

	public function assignPassword($a_password)
	{
		$this->user_obj->resetPassword($a_password, $a_password);
	}
	
	public function activateUser()
	{
		$this->user_obj->setActive(true);
		$this->user_obj->setTimeLimitUnlimited(1);     // TODO: may be different for external users
		$this->user_obj->setTimeLimitFrom(time());		// ""
		$this->user_obj->setTimeLimitUntil(time());    // ""
		$this->user_obj->update();
	}

	private function initUserSession($a_user_id)
	{
		global $DIC;
		/** @var ilAuthSession $ilAuthSession */
		$ilAuthSession = $DIC['ilAuthSession'];

		$ilAuthSession->setAuthenticated(true, $a_user_id);
        //$_SESSION['AccountId'] = $a_user_id;
		ilInitialisation::initUserAccount();
	}

	
	public function enterCourse()
	{
		$this->initUserSession($this->user_obj->getId());
		
		// go to the course
		require_once ("./Services/Link/classes/class.ilLink.php");
		ilUtil::redirect(ilLink::_getLink($this->course_ref_id),"crs");
	}		
	
	public function showData()
	{
		header("content-type: text/html; charset=UTF-8");
		echo "<pre>";
		var_dump($this->data);
		echo "</pre>";
	}
	
	public function showLoginForm()
	{
		global $tpl, $ilMainMenu;

		$template = new ilTemplate("tpl.manu_login.html", true, true, "Customizing/Vhb");
		$template->setVariable("CLIENT_ID", CLIENT_ID);
		$template->setVariable("COURSE_REF_ID", $this->course_ref_id);
		$template->setVariable("USERNAME", ilObjUser::_lookupLogin($this->user_obj->getId()));
		$template->setVariable("ERROR_SUFFIX", $this->error_suffix);

		$this->initUserSession(ANONYMOUS_USER_ID);
		$ilMainMenu->showLogoOnly(true);
		$tpl->getStandardTemplate();
		$tpl->setContent($template->get());
		$tpl->show();
		exit;
	}


	public function showCourseSelection()
	{
		global $tpl, $lng,  $ilMainMenu;

		$vhbsession = $this->writeSessionData();

		require_once("./Customizing/Vhb/classes/class.ilVhbCourseListGUI.php");

		$lng->loadLanguageModule('course');
		$template = new ilTemplate("tpl.course_selection.html", true, true, "Customizing/Vhb");
		$list_gui = new ilVhbCourseListGUI();
		$list_gui->setVhbSession($vhbsession);

		foreach($this->courses as $course)
		{
			$description = ilObject::_lookupDescription($course['obj_id']);
			$html = $list_gui->getListItemHTML($course['ref_id'], $course['obj_id'], $course['title'], $description);

			$template->setCurrentBlock('course_selection');
			$template->setVariable('COURSE', $html);
			$template->parseCurrentBlock();
		}
		$template->setVariable('LV_TITLE', $this->data['bezeichnung']);
		$template->setVariable('LV_NUMBER', $this->data['lvnr']);
		$template->setVariable("ERROR_SUFFIX", $this->error_suffix);

		$ilMainMenu->showLogoOnly(true);
		$tpl->getStandardTemplate();
		$tpl->setContent($template->get());
		$tpl->show();
		exit;
	}
	
	public function showError($a_message)
	{
		global $ilErr;
	 	$ilErr->raiseError($a_message.'<br />'.$this->error_suffix, $ilErr->WARNING);
	}
}
?>
