<?php
// fau: campusSub - new class ilMyCampusSynchronisation.

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/** 
* Class to synchronizes course and group registrations from my campus.
* Adds dummy accounts for non-existing users identified by their matriculation
*/
class ilMyCampusSynchronisation
{
	protected $sum_added = 0;
	protected $error = "";

	/**
	 * ilMyCampusSynchronisation constructor.
	 * @throws ilLogException
	 */
	public function __construct()
	{
		global $ilUser;
		
		$this->enabled = ilCust::get('mycampus_sync_enabled');
		$this->campus = null;
		
		if ($this->logfile = ilCust::get('mycampus_sync_logfile'))
		{
			require_once('Services/Logging/classes/class.ilLog.php');
			$this->log = new ilLog(ILIAS_LOG_DIR, $this->logfile, CLIENT_ID);
			$this->log->setLogLevel('message');
		}
		
		if ($this->mail_interval = ilCust::get('mycampus_sync_mail_interval'))
		{
			$this->mail_verbose = ilCust::get('mycampus_sync_mail_verbose');
			
			require_once("Services/Mail/classes/class.ilMail.php");
			$this->mail = new ilMail($ilUser->getId());
			$this->mail_text = "";
		}
	}

	/**
	 * Get the number of added users
	 * @return int
	 */
	public function getAdded()
	{
		return $this->sum_added;
	}

	/**
	 * Check if the call produced an error
	 * @return bool
	 */
	public function hasError()
	{
		return !empty($this->error);
	}

	/**
	 * Get an error message
	 * @return string
	 */
	public function getError()
	{
		return $this->error;
	}

	
	/**
	 * Start the synchronisation
	 */
	public function start()
	{
		if ($this->enabled)
		{
			try
			{
				$this->message("Synchronisation started.", true);
	
				$this->login();
				$this->sync();
				$this->logout();
				
				$this->message("Synchronisation finished.", true);
				$this->mailResult();
			}
			catch (ilException $exc)
			{
				$this->message($exc->getMessage(), true); 
				$this->mailResult();

				$this->error = $exc->getMessage();
			}
		}
	}
	
	/**
	 * Login to my campus
	 * @throws ilException
	 */
	private function login()
	{
		require_once('Services/MyCampus/classes/class.ilMyCampusClient.php');		
		$this->campus = ilMyCampusClient::_getInstance();
		if ($this->campus->login() === false)
		{
			throw new ilException ($this->campus->getClientError());
			//throw new ilException('Could not connect: to my campus');
		}
	}

	/**
	 * Logout from my campus
	 */
	private function logout()
	{
		$this->campus->logout();
	}

	/**
	 * Sync course registrations with my campus
	 */
	private function sync()
	{
		require_once('Services/UnivIS/classes/class.ilUnivis.php');

		$semester = ilUnivis::_getRunningSemester();
		$this->message("Semester ". $semester);
		$objects = ilUnivis::_getUntrashedObjectsForSemester($semester);
		$this->syncObjects($objects);
		
		$semester = ilUnivis::_getNextSemester();
		$this->message("Semester ". $semester);
		$objects = ilUnivis::_getUntrashedObjectsForSemester($semester);
		$this->syncObjects($objects);
	 }
	
	/**
	 * Sync course registrations for specific objects
	 * @param array object data records
	 */ 
	private function syncObjects($a_objects)
	{
		global $lng, $ilSetting;
		
		require_once('Services/User/classes/class.ilUserUtil.php');
		require_once('Modules/Course/classes/class.ilCourseParticipants.php');

		foreach ($a_objects as $object)
		{
			$univis_id = $object['import_id'];
			$info = ' ['.$univis_id.'][http://www.studon.uni-erlangen.de/'.$object['type'].$object['ref_id'].'.html]';
			
			if ($object['type'] != 'crs')
			{
				$this->message('Not a Course!' . $info, true);
				continue;
			}
			$course = ilObjectFactory::getInstanceByRefId($object['ref_id'], false);
			if ($course->getSubscriptionLimitationType() != IL_CRS_SUBSCRIPTION_MYCAMPUS)
			{
				continue;
			}
			
			$univis_id = $object['import_id'];
			$participants = $this->campus->getParticipants($univis_id);

			if (!is_array($participants))
			{
				$this->message('Failed to get participants from my campus!' . $info, true);
				continue;
			}
			elseif (!count($participants))
			{
				$this->message('No participants found in my campus.' . $info);
				continue;	
			}
			
			$members_obj = ilCourseParticipants::_getInstanceByObjId($object['obj_id']);

			$added = array();
			$waiting = array();
			foreach ($participants as $part)
			{
				$identity = $part[1];

				if ($part[2] == "SUBSCRIBED")
				{
					$user_id = ilObjUser::_findUserIdByAccount($identity);
					if (!$user_id)
					{
                        $this->message('Create User...: ' . $identity, false);
						$user_id = ilUserUtil::_createDummyAccount(
							$identity,
							$lng->txt('dummy_user_firstname_mycampus'),
							$lng->txt('dummy_user_lastname_mycampus'),
							$ilSetting->get('mail_external_sender_noreply'));
					}
					if (!$members_obj->isAssigned($user_id))
					{
                        $this->message('Add to Course...: ' . $identity . $info, false);
						$members_obj->add($user_id,IL_CRS_MEMBER);
						$added[] = $identity;
					}
				}
				elseif ($part[2] == "WAITINGLIST")
				{
					$waiting[] = $identity;
				}
			}
			if (count($added))
			{
				$this->message('Added: ' . implode(', ', $added) . $info, true);
				$this->sum_added += count($added);
			}
			else
			{
				$this->message('In Sync.' . $info);
			}

			if (count($waiting))
			{
				$this->message('On waiting list in my campus: ' . implode(', ', $waiting) . $info);
			}
		}	
	}
	 
	
	/**
	 * print a message
	 * 
	 * @param 	string 		message
	 * @param	boolean		write the message to the log
	 */
	private function message($a_message, $a_log = false)
	{
		if ($a_log and isset($this->log))
		{
			$this->log->write($a_message);
		}
		
		if (($a_log or $this->mail_verbose) and isset($this->mail))
		{
			$this->mail_text .= $a_message . "\n";
		}
	}
	
	/**
	 * Generate a reult mail email to the cron job user
	 */
	private function mailResult()
	{
		global $ilUser;

		if (!isset($this->mail))
		{
			return;
		}
		
		if ($this->mail_intervall == "all")
		{
			$send = true;
		}
		elseif ($last_mail = $ilUser->getPref("mycampus_last_mail"))
		{	
			// last login before mail interval
			$check = new ilDateTime(time(),IL_CAL_UNIX);
			$check->increment($this->mail_interval, -1);
			$last = new ilDateTime($last_mail, IL_CAL_DATETIME);
			
			$send = ilDate::_before($last, $check);
		}
		else
		{
			$send = true;
		}
		
		if ($send)
		{
			$this->mail->sendMail($ilUser->getLogin(), '', '', 
							"MyCampus Synchronisation", $this->mail_text,
							array(), array("system"), false);
							
			$last = new ilDateTime(time(),IL_CAL_UNIX);
			$ilUser->writePref("mycampus_last_mail", $last->get(IL_CAL_DATETIME));
		}	
	}
}
?>