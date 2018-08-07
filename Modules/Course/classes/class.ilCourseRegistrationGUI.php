<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once('./Services/Membership/classes/class.ilRegistrationGUI.php');

/**
* GUI class for course registrations
* 
* @author Stefan Meyer <smeyer.ilias@gmx.de>
* @version $Id$
*
* @ingroup ModulesCourse
* 
* @ilCtrl_Calls ilCourseRegistrationGUI: 
*/
class ilCourseRegistrationGUI extends ilRegistrationGUI
{
// fau: fairSub - added type hints
	/** @var ilRegistrationGUI $parent_gui */
	private $parent_gui = null;

	/** @var ilObjCourse $container */
	protected $container = null;

	/** @var  ilCourseParticipants  $participants*/
	protected $participants;

	/** @var int $subscription_type */
	protected $subscription_type;
// fau.

	/**
	 * Constructor
	 *
	 * @access public
	 * @param object course object
	 */
	public function __construct($a_container, $a_parent_gui)
	{
		parent::__construct($a_container);	
		
		$this->parent_gui = $a_parent_gui;

		// fim: [memcond] set the actual subscription type according to the studydata condition
		if ($this->matches_studycond)
		{
		    $this->subscription_type = $this->container->getSubscriptionType();
		}
		else
		{
			$this->subscription_type = IL_CRS_SUBSCRIPTION_CONFIRMATION;
		}
		// fim.
	}
	
	/**
	 * Execute command
	 *
	 * @access public
	 */
	public function executeCommand()
	{
		global $ilTabs,$ilUser;

        // fim: [memfix] do the permission check with a fitting command
        $cmd = $this->ctrl->getCmd("show");
        switch ($cmd)
        {
			case 'joinAsGuest':
			case 'joinAsGuestConfirmed':
				// don't check permission
				$this->$cmd();
				return true;

         	// action buttons on registration screen
			case 'updateWaitingList':
            case 'leaveWaitingList':
            case 'updateSubscriptionRequest':
            case 'cancelSubscriptionRequest':
                $checkCmd = 'leave';
                break;

			// called for updating scubscription requests
            case 'leave':
                $checkCmd = 'leave';
                $cmd = 'show';
                break;

			// called for joining
            default:
                $checkCmd = '';
        }
		if(!$GLOBALS['ilAccess']->checkAccess('join', $checkCmd, $this->getRefId()))
 		{
			$this->ctrl->setReturn($this->parent_gui,'infoScreen');
			$this->ctrl->returnToParent($this);
			return FALSE;
		}
		
		$next_class = $this->ctrl->getNextClass($this);
		switch($next_class)
		{
			default:
                // command is already prepared
				// $cmd = $this->ctrl->getCmd("show");
				$this->$cmd();
				break;
		}
        // fim.
		return true;
	}
	
	/**
	 * get form title
	 *
	 * @access protected
	 * @return string title
	 */
	protected function getFormTitle()
	{
		global $ilUser;
		
		if($this->getWaitingList()->isOnList($ilUser->getId()))
		{
			return $this->lng->txt('member_status');
		}
		return $this->lng->txt('crs_registration');
	}
	
	/**
	 * fill informations
	 *
	 * @access protected
	 * @param
	 * @return
	 */
	protected function fillInformations()
	{
		if($this->container->getImportantInformation())
		{
	        // fim: [univis] user custom input for info
			$imp = new ilCustomInputGUI($this->lng->txt('crs_important_info'), "", true);
			$html =  nl2br(ilUtil::makeClickable($this->container->getImportantInformation(), true));
			$imp->setHtml($html);
			$this->form->addItem($imp);
			// fim.
		}
		
		/* fim: [univis] don't show syllabus
		if($this->container->getSyllabus())
		{
			$syl = new ilNonEditableValueGUI($this->lng->txt('crs_syllabus'), "", true);
			$value = nl2br(ilUtil::makeClickable ($this->container->getSyllabus(), true));
			$syl->setValue($value);
			$this->form->addItem($syl);
		}
		fim. */
	}
	
	/**
	 * show informations about the registration period
	 *
	 * @access protected
	 */
	protected function fillRegistrationPeriod()
	{
		include_once('./Services/Calendar/classes/class.ilDateTime.php');
		$now = new ilDateTime(time(),IL_CAL_UNIX,'UTC');

		// fim: [campus] no registration period for subscription by my campus
		if ($this->container->getSubscriptionLimitationType() == IL_CRS_SUBSCRIPTION_MYCAMPUS
			or $this->container->getSubscriptionType() == IL_CRS_SUBSCRIPTION_MYCAMPUS)
		{
			return true;
		}
		// fim.

// fau: objectSub - no registration period for subscription by object
		if ($this->container->getSubscriptionType() == IL_CRS_SUBSCRIPTION_OBJECT)
		{
			return true;
		}
// fau.
		if($this->container->getSubscriptionUnlimitedStatus())
		{
// fau: fairSub	- add info about fair time for unlimited subscription
			if($this->container->inSubscriptionFairTime())
			{
				$suffix = " | ".$this->lng->txt('sub_fair_date'). ': '. $this->container->getSubscriptionFairDisplay(false);
			}
			$reg = new ilNonEditableValueGUI($this->lng->txt('mem_reg_period'));
			$reg->setValue($this->lng->txt('mem_unlimited').$suffix);
			$this->form->addItem($reg);
// fau.
			return true;
		}
		elseif($this->container->getSubscriptionLimitationType() == IL_CRS_SUBSCRIPTION_DEACTIVATED)
		{
			return true;
		}

		$start = new ilDateTime($this->container->getSubscriptionStart(),IL_CAL_UNIX,'UTC');
		$end = new ilDateTime($this->container->getSubscriptionEnd(),IL_CAL_UNIX,'UTC');
		
		if(ilDateTime::_before($now,$start))
		{
			$tpl = new ilTemplate('tpl.registration_period_form.html',true,true,'Services/Membership');
			$tpl->setVariable('TXT_FIRST',$this->lng->txt('mem_start'));
			$tpl->setVariable('FIRST',ilDatePresentation::formatDate($start));
			
			$tpl->setVariable('TXT_END',$this->lng->txt('mem_end'));
			$tpl->setVariable('END',ilDatePresentation::formatDate($end));
			
			$warning = $this->lng->txt('mem_reg_not_started');
		}
		elseif(ilDateTime::_after($now,$end))
		{
			$tpl = new ilTemplate('tpl.registration_period_form.html',true,true,'Services/Membership');
			$tpl->setVariable('TXT_FIRST',$this->lng->txt('mem_start'));
			$tpl->setVariable('FIRST',ilDatePresentation::formatDate($start));
			
			$tpl->setVariable('TXT_END',$this->lng->txt('mem_end'));
			$tpl->setVariable('END',ilDatePresentation::formatDate($end));
			
			
			$warning = $this->lng->txt('mem_reg_expired');
		}
		else
		{
			$tpl = new ilTemplate('tpl.registration_period_form.html',true,true,'Services/Membership');
			$tpl->setVariable('TXT_FIRST',$this->lng->txt('mem_end'));
			$tpl->setVariable('FIRST',ilDatePresentation::formatDate($end));
		}

// fau: fairSub	- add info about fair time for limited subscription
		if($this->container->isSubscriptionMembershipLimited() &&$this->container->getSubscriptionMaxMembers())
		{
			if ($this->container->getSubscriptionFair() >= 0)
			{
				$tpl->setVariable('TXT_FAIR', $this->lng->txt('sub_fair_date'). ': ');
				$tpl->setVariable('FAIR', $this->container->getSubscriptionFairDisplay(false));
			}
			else
			{
				$tpl->setVariable('TXT_FAIR', $this->lng->txt('sub_fair_inactive_short'));
			}
		}
// fau.

		$reg = new ilCustomInputGUI($this->lng->txt('mem_reg_period'));
		$reg->setHtml($tpl->get());
		if(strlen($warning))
		{
			// Disable registration
			$this->enableRegistration(false);
			ilUtil::sendFailure($warning);
			#$reg->setAlert($warning);
		}
		$this->form->addItem($reg);
		return true;
	}
	
	
	/**
	 * fill max members
	 *
	 * @access protected
	 * @param
	 * @return
	 */
	protected function fillMaxMembers()
	{
		global $ilUser;
		
		// fim: [campus] no membership info for subscription by my campus
		if ($this->container->getSubscriptionLimitationType() == IL_CRS_SUBSCRIPTION_MYCAMPUS
			or $this->container->getSubscriptionType() == IL_CRS_SUBSCRIPTION_MYCAMPUS)
		{
			return true;
		}
		// fim.

// fau: objectSub - no max members for subscription by object
		if ($this->container->getSubscriptionType() == IL_CRS_SUBSCRIPTION_OBJECT)
		{
			return true;
		}
// fau.

		if(!$this->container->isSubscriptionMembershipLimited())
		{
			return true;
		}
		$tpl = new ilTemplate('tpl.max_members_form.html',true,true,'Services/Membership');

		if($this->container->getSubscriptionMinMembers())
		{
			$tpl->setVariable('TXT_MIN',$this->lng->txt('mem_min_users').':');
			$tpl->setVariable('NUM_MIN',$this->container->getSubscriptionMinMembers());
		}

		if($this->container->getSubscriptionMaxMembers())
		{
			$tpl->setVariable('TXT_MAX',$this->lng->txt('mem_max_users'));
			$tpl->setVariable('NUM_MAX',$this->container->getSubscriptionMaxMembers());

			$tpl->setVariable('TXT_FREE',$this->lng->txt('mem_free_places').":");
			$free = max(0,$this->container->getSubscriptionMaxMembers() - $this->participants->getCountMembers());

			if($free)
				$tpl->setVariable('NUM_FREE',$free);
			else
				$tpl->setVariable('WARN_FREE',$free);

// fau: fairSub - get already instantiated waiting list and use own check function
			$waiting_list = $this->getWaitingList();
			if ($this->isWaitingListActive())
// fau.
			{
				if($waiting_list->isOnList($ilUser->getId()))
				{
					$tpl->setVariable('TXT_WAIT',$this->lng->txt('mem_waiting_list_position'));
// fau: fairSub - show effective position and other sharing users
					$tpl->setVariable('NUM_WAIT',$waiting_list->getPositionInfo($ilUser->getId()));
// fau.
				}
				else
				{
					$tpl->setVariable('TXT_WAIT',$this->lng->txt('mem_waiting_list'));
					if($free and $waiting_list->getCountUsers())
						$tpl->setVariable('WARN_WAIT',$waiting_list->getCountUsers());
					else
						$tpl->setVariable('NUM_WAIT',$waiting_list->getCountUsers());
				}
			}

			$alert = '';
// fau: fairSub - add message and adjust label for fair subscription
			if ($this->container->getSubscriptionFair() < 0)
			{
				ilUtil::sendInfo($this->lng->txt('sub_fair_inactive_message'));
			}

			if ($this->container->inSubscriptionFairTime())
			{
				ilUtil::sendInfo(sprintf($this->lng->txt('sub_fair_subscribe_message'), $this->container->getSubscriptionFairDisplay(true)));
				$this->join_button_text = $this->lng->txt('sub_fair_subscribe_label');
			}
			elseif(
// fau.
					!$free and
					!$this->container->enabledWaitingList())
			{
				// Disable registration
				$this->enableRegistration(false);
				ilUtil::sendFailure($this->lng->txt('mem_alert_no_places'));
				#$alert = $this->lng->txt('mem_alert_no_places');
			}
			elseif(
					$this->container->enabledWaitingList() and
					$this->container->isSubscriptionMembershipLimited() and
					$waiting_list->isOnList($ilUser->getId())
			)
			{
				// Disable registration
				$this->enableRegistration(true);
			}
			elseif(
					!$free and
					$this->container->enabledWaitingList() and
					$this->container->isSubscriptionMembershipLimited())

			{
				ilUtil::sendFailure($this->lng->txt('crs_warn_no_max_set_on_waiting_list'));
				#$alert = $this->lng->txt('crs_warn_no_max_set_on_waiting_list');
// fau: fairSub - set join button text
				$this->join_button_text = $this->lng->txt('mem_request_waiting');
// fau.
			}
// fau: fairSub - add to waiting list if free places are needed for already waiting users (see also add() function)
			elseif(
					$free and
					$this->container->enabledWaitingList() and
					$this->container->isSubscriptionMembershipLimited() and
					($this->getWaitingList()->getCountUsers() >= $free) )
			{
					ilUtil::sendFailure($this->lng->txt('crs_warn_wl_set_on_waiting_list'));
					#$alert = $this->lng->txt('crs_warn_wl_set_on_waiting_list');
					$this->join_button_text = $this->lng->txt('mem_request_waiting');
			}
// fau.
		}

		$max = new ilCustomInputGUI($this->lng->txt('mem_participants'));
		$max->setHtml($tpl->get());
		if(strlen($alert))
		{
			$max->setAlert($alert);
		}
		$this->form->addItem($max);
		return true;
	}
	
	/**
	 * fill registration type
	 *
	 * @access protected
	 * @return
	 */
	protected function fillRegistrationType()
	{
		global $ilUser;

		// fim: [campus] handle subscription by my campus
		if($this->container->getSubscriptionType() == IL_CRS_SUBSCRIPTION_MYCAMPUS
			or $this->container->getSubscriptionLimitationType() == IL_CRS_SUBSCRIPTION_MYCAMPUS)
		{

			$reg = new ilCustomInputGUI($this->lng->txt('mem_reg_type'));

			$reg->setHtml(sprintf($this->lng->txt('crs_subscription_mycampus_registration'),
				ilUtil::getImagePath('studon/meinCampusSmall.gif'),
				sprintf(ilCust::get('mycampus_reg_url'),$this->container->getImportId())));
			$this->form->addItem($reg);

			// Disable registration
			$this->enableRegistration(false);
			return true;
		}
		// fim.

// fau: objectSub - fill registration by separate object
		if ($this->container->getSubscriptionType() == IL_CRS_SUBSCRIPTION_OBJECT)
		{
			return $this->fillRegistrationTypeObject($this->container->getSubscriptionRefId());
		}
// fau.

		if($this->container->getSubscriptionLimitationType() == IL_CRS_SUBSCRIPTION_DEACTIVATED)
		{
			$reg = new ilCustomInputGUI($this->lng->txt('mem_reg_type'));
			#$reg->setHtml($this->lng->txt('crs_info_reg_deactivated'));
			$reg->setAlert($this->lng->txt('crs_info_reg_deactivated'));
			#ilUtil::sendFailure($this->lng->txt('crs_info_reg_deactivated'));
			#$reg = new ilNonEditableValueGUI($this->lng->txt('mem_reg_type'));
			#$reg->setValue($this->lng->txt('crs_info_reg_deactivated'));
			#$reg->setAlert($this->lng->txt('grp_reg_deactivated_alert'));
			$this->form->addItem($reg);
		
			// Disable registration
			$this->enableRegistration(false);
			return true;
		}

		//fim: [memcond] check actual subscription type
		switch($this->subscription_type)
		// fim.
		{
			case IL_CRS_SUBSCRIPTION_DIRECT:

// fau: fairSub - allow "request" info if waiting list is active
// fau.

				// fim: [memcond] set direct subscription info for studycond
				$txt = new ilCustomInputGUI($this->lng->txt('mem_reg_type'));
				if ($this->has_studycond)
				{
					$txt->setHtml(sprintf($this->lng->txt('crs_subscription_options_direct_studycond'), $this->describe_studycond));
				}
				else
				{
					$txt->setHtml($this->lng->txt('crs_subscription_options_direct'));
				}
				// fim.

				$this->form->addItem($txt);
				break;

			case IL_CRS_SUBSCRIPTION_PASSWORD:
				// fim: [memcond] set password subscription info for studycond
				$txt = new ilCustomInputGUI($this->lng->txt('mem_reg_type'));
				if ($this->has_studycond)
				{
					$txt->setHtml(sprintf($this->lng->txt('crs_subscription_options_password_studycond'), $this->describe_studycond));
				}
				else
				{
					$txt->setHtml($this->lng->txt('crs_subscription_options_password'));
				}
				// fim.

				$pass = new ilTextInputGUI($this->lng->txt('passwd'),'grp_passw');
				$pass->setInputType('password');
				$pass->setSize(12);
				$pass->setMaxLength(32);
				#$pass->setRequired(true);
				$pass->setInfo($this->lng->txt('crs_info_reg_password'));

				$txt->addSubItem($pass);
				$this->form->addItem($txt);
				break;
				
			case IL_CRS_SUBSCRIPTION_CONFIRMATION:

// fau: fairSub - allow "request" info if waiting list is active
// fau.
				// fim: [memcond] set confirmation subscription info for studycond
				$txt = new ilCustomInputGUI($this->lng->txt('mem_reg_type'));
				if ($this->has_studycond and $this->container->getSubscriptionType() == IL_CRS_SUBSCRIPTION_DIRECT)
				{
					$txt->setHtml(sprintf($this->lng->txt('crs_subscription_options_direct_studycond'), $this->describe_studycond));
				}
				elseif ($this->has_studycond and $this->container->getSubscriptionType() == IL_CRS_SUBSCRIPTION_PASSWORD)
				{
					$txt->setHtml(sprintf($this->lng->txt('crs_subscription_options_password_studycond'), $this->describe_studycond));
				}
				else
				{
					$txt->setHtml($this->lng->txt('crs_subscription_options_confirmation'));
				}
				// fim.
			
				$sub = new ilTextAreaInputGUI($this->lng->txt('crs_reg_subject'),'subject');
				$sub->setValue($_POST['subject']);
				$sub->setInfo($this->lng->txt('crs_info_reg_confirmation'));
// fau: fairSub - extend size of subject field
				$sub->setRows(10);
// fau.
// fau: fairSub - treat existing subscription on waiting list
				if ($this->getWaitingList()->isToConfirm($ilUser->getId()))
				{
					$sub->setValue($this->getWaitingList()->getSubject($ilUser->getId()));
					$sub->setInfo('');
					ilUtil::sendQuestion('mem_user_already_subscribed');
					//$this->enableRegistration(true);
				}
// fim.
				$txt->addSubItem($sub);
				$this->form->addItem($txt);

// fau: fairSub - set join_button_text
				$this->join_button_text = $this->lng->txt('mem_request_joining');
// fau.
				break;
				

			default:
				return true;
		}
		
		return true;
	}
	
	// fim: [memsess] new function fillEventRegistration()
	protected function fillEventRegistration()
	{
		switch ($this->container->getSubscriptionWithEvents())
		{
			case IL_CRS_SUBSCRIPTION_EVENTS_OFF:
			    return;

			case IL_CRS_SUBSCRIPTION_EVENTS_UNIQUE:
			    $input_type = "radio";
			    break;

			case IL_CRS_SUBSCRIPTION_EVENTS_MULTIPLE:
			    $input_type = "checkbox";
			    break;
		}


		require_once("./Modules/Session/classes/class.ilObjSession.php");
		require_once("./Modules/Session/classes/class.ilObjSessionAccess.php");

		$events =& ilObjSession::_getSessions($this->container->getRefId(), true);
		if (!count($events))
		{
			return;
		}

		$section = new ilFormSectionHeaderGUI();
		$section->setTitle($this->lng->txt("crs_subscription_events_header"));
		$this->form->addItem($section);

		if ($this->subscription_type == IL_CRS_SUBSCRIPTION_CONFIRMATION)
		{
			$txt = new ilCustomInputGUI($this->lng->txt(''));
			$txt->setAlert($this->lng->txt('crs_event_subscription_after_confirmation'));
			$this->form->addItem($txt);
			$reg_after_confirm = true;
		}


		if (!is_array($_POST["events"]))
		{
			$_POST["events"] = array();
		}

		$chosen = false;
		foreach ($events as $event_obj)
		{
			$event_id = $event_obj->getId();

			$tpl = new ilTemplate('tpl.crs_event_info.html',true,true,'Modules/Course');
			if ($description = $event_obj->getDescription())
			{
				$tpl->setCurrentBlock("event_description");
				$tpl->setVariable("DESCRIPTION", $description);
				$tpl->parseCurrentBlock();
			}
			if ($location = $event_obj->getLocation())
			{
				$tpl->setCurrentBlock("event_location");
				$tpl->setVariable("TXT_LOCATION", $this->lng->txt("crs_subscription_event_location"));
				$tpl->setVariable("LOCATION", $location);
				$tpl->parseCurrentBlock();
			}
			if ($referee = $event_obj->getName())
			{
				if ($referee_mail = $event_obj->getEmail())
				{
					$tpl->setCurrentBlock("event_referee_mail");
					$tpl->setVariable("REFEREE_MAIL", $referee_mail);
					$tpl->parseCurrentBlock();
				}

				$tpl->setCurrentBlock("event_referee");
				$tpl->setVariable("TXT_REFEREE", $this->lng->txt("crs_subscription_event_referee"));
				$tpl->setVariable("REFEREE", $referee);
				$tpl->parseCurrentBlock();
			}

			$registrations = ilObjSessionAccess::_lookupRegisteredUsers($event_obj->getId());
			$tpl->setCurrentBlock("event_registrations");
			$tpl->setVariable("TXT_REGISTRATIONS", $this->lng->txt("crs_subscription_event_registered").":");
			$tpl->setVariable("REGISTRATIONS", $registrations);
			$tpl->parseCurrentBlock();

			$max_participants = $event_obj->getRegistrationMaxUsers();
			if ($max_participants == 0)
			{
				$reg_allowed = true;
			}
			else
			{
				$free = max($max_participants - $registrations, 0);
				$reg_allowed = ($free > 0);

				$tpl->setCurrentBlock("event_free");
				$tpl->setVariable("TXT_FREE", $this->lng->txt("crs_subscription_event_free").":");
				$tpl->setVariable("FREE", $free);
				$tpl->parseCurrentBlock();
			}
			$tpl->setVariable("EVENT_DATE", $event_obj->getFirstAppointment()->appointmentToString());
			$info = $tpl->get();

			if ($input_type == "radio")
			{
				$item = new ilRadioGroupInputGUI($event_obj->getTitle(), "events[]");
				$opt = new ilRadioOption($info, $event_obj->getId());
				$item->addOption($opt);
				if (!$reg_allowed or $reg_after_confirm)
				{
					$item->setDisabled(true);
				}
				elseif(in_array($event_obj->getId(), $_POST["events"]))
				{
					if (!$chosen)
					{
						$item->setValue($event_obj->getId());
						$chosen = true;
					}
				}
			}
			else
			{
			    $item = new ilCheckboxInputGUI($event_obj->getTitle(), "events[]");
			    $item->setOptionTitle($info);
			    $item->setValue($event_obj->getId());
				if (!$reg_allowed or $reg_after_confirm)
				{
					$item->setDisabled(true);
					$item->setChecked(false);
				}
				elseif(in_array($event_obj->getId(), $_POST["events"]))
				{
					$item->setChecked(true);
					$chosen = true;
				}
			}
			$this->form->addItem($item);
		}
	}
	// fim.


	/**
	 * Add course specific command buttons
	 * @return 
	 */
	protected function addCommandButtons()
	{
		global $ilUser;

// fau: fairSub - use parent addCommandButtons()
		parent::addCommandButtons();
		return true;
// fau.
	}

	/**
	 * Validate subscription request
	 *
	 * @access protected
	 * @param
	 * @return
	 */
	protected function validate()
	{
		global $ilUser;
		
		if($ilUser->getId() == ANONYMOUS_USER_ID)
		{
			$this->join_error = $this->lng->txt('permission_denied');
			return false;
		}
		
		// Set aggrement to not accepted
		$this->setAccepted(false);
		
		if(!$this->isRegistrationPossible())
		{
			$this->join_error = $this->lng->txt('mem_error_preconditions');
			return false;
		}
		// fim: [memcond] check actual subscription type
		if($this->subscription_type == IL_CRS_SUBSCRIPTION_PASSWORD)
		// fim.
		{
			if(!strlen($pass = ilUtil::stripSlashes($_POST['grp_passw'])))
			{
				$this->join_error = $this->lng->txt('crs_password_required');
				return false;
			}
			if(strcmp($pass,$this->container->getSubscriptionPassword()) !== 0)
			{
				$this->join_error = $this->lng->txt('crs_password_not_valid');
				return false;
			}
		}

// fau: courseUdf - custom fields are validate with the form
//		if(!$this->validateCustomFields())
//		{
//			$this->join_error = $this->lng->txt('fill_out_all_required_fields');
//			return false;
//		}
// fau.

		// fim: [memsess] check event registration
		if ($this->container->getSubscriptionWithEvents() != IL_CRS_SUBSCRIPTION_EVENTS_OFF
		and $this->subscription_type != IL_CRS_SUBSCRIPTION_CONFIRMATION)
		{
			if (!is_array($_POST["events"]))
			{
				$this->join_error = $this->lng->txt('crs_choose_one_event');
				return false;
			}

			$chosen = false;
			foreach ($_POST["events"] as $event_id)
			{
				if ($event_id)
				{
					if ($chosen and $this->container->getSubscriptionWithEvents()
						== IL_CRS_SUBSCRIPTION_EVENTS_UNIQUE)
					{
						$this->join_error = $this->lng->txt('crs_choose_one_event');
						return false;
					}

					$event_obj = $session = ilObjectFactory::getInstanceByObjId($event_id, false);
					if ($event_obj->registrationPossible() == false)
					{
						$this->join_error =
							sprintf($this->lng->txt('crs_event_registration_not_possible'),
									$event_obj->getTitle());
						return false;
					}
					else
					{
						$chosen = true;
					}
				}
			}
			if (!$chosen)
			{
				$this->join_error = $this->lng->txt('crs_choose_one_event');
				return false;
			}
		}
		// fim.

		if(!$this->validateAgreement())
		{
			$this->join_error = $this->lng->txt('crs_agreement_required');
			return false;
		}
		
		return true;
	}

// fau: fairSub - add subscription requests and requests in fair time to waiting list
	// fim: [memcond] use condition based subscription type
	// fim: [memfix] avoid failures on heavy concurrency
	/**
	 * add user 
	 *
	 * @access protected
	 * @param
	 * @return
	 */
	protected function add()
	{
		global $ilUser, $tree, $ilCtrl, $rbacreview, $lng;

		// TODO: language vars

		// get the membership role id
	    $mem_rol_id = $this->participants->getRoleId(IL_CRS_MEMBER);

		/////////////////////////////////////////////////////////////
		// FAKES SIMULATING PARALLEL REQUESTS

		// global $ilDB;

		// ADD AS MEMBER
		/*
		$query = "INSERT INTO rbac_ua (rol_id, usr_id) ".
			"VALUES (".
			$ilDB->quote($mem_rol_id ,'integer').", ".
			$ilDB->quote($ilUser->getId() ,'integer').
			")";
		$res = $ilDB->manipulate($query);
		*/

		// ADD TO WAITING LIST
		/*
		  $query = "INSERT INTO crs_waiting_list (obj_id, usr_id, sub_time, subject) ".
			"VALUES (".
			$ilDB->quote($this->container->getId() ,'integer').", ".
			$ilDB->quote($ilUser->getId() ,'integer').", ".
			$ilDB->quote(time() ,'integer').", ".
			$ilDB->quote($_POST['subject'] ,'text')." ".
			")";
		$res = $ilDB->manipulate($query);
		*/

		////////////////////////////////////////////////////////////////


		///////
		// first decide what to do
		// the sequence and nesting of checks is important!
		//////
		if ($this->participants->isAssigned($ilUser->getId()))
		{
	        // user is already a participant
	        $action = 'showAlreadyMember';
	    }
		elseif ($this->subscription_type == IL_CRS_SUBSCRIPTION_CONFIRMATION)
		{
			// always add requests to be confirmed to the waiting list (to keep them in the order)
			$action = 'addToWaitingList';
		}
		elseif ($this->container->inSubscriptionFairTime())
		{
			// always add to the waiting list if in fair time
			$action = 'addToWaitingList';
		}
		elseif ($this->container->isSubscriptionMembershipLimited() && $this->container->getSubscriptionMaxMembers() > 0)
		{
			$max = $this->container->getSubscriptionMaxMembers();
			$free = max(0, $max - $this->participants->getCountMembers());

			if ($this->isWaitingListActive())
			{
				$waiting = $this->getWaitingList()->getCountUsers();
				if ($waiting >= $free)
				{
					// add to waiting list if all free places have waiting candidates
					$action = 'addToWaitingList';
				}
				elseif ($this->participants->addLimited($ilUser->getId(),IL_CRS_MEMBER, $max - $waiting))
				{
					// try to add the users
					// free places are those without waiting candidates

					// member could be added
					$action = 'notifyAdded';
				}
				else
				{
					// maximum members reached
					$action = 'addToWaitingList';
				}
			}
			elseif ($this->participants->addLimited($ilUser->getId(),IL_CRS_MEMBER, $max))
			{
				// member could be added
            	$action = 'notifyAdded';
	        }
			elseif ($rbacreview->isAssigned($ilUser->getId(), $mem_rol_id))
			{
				// may have been added by a parallel request
				$action = 'showAlreadyMember';
			}
	        else
			{
	            // maximum members reached and no list active
            	$action = 'showLimitReached';
	        }
	    }
		elseif ($this->participants->addLimited($ilUser->getId(),IL_CRS_MEMBER, 0))
		{
	        // member could be added
           	$action = 'notifyAdded';
	    }
		elseif ($rbacreview->isAssigned($ilUser->getId(), $mem_rol_id))
		{
			// may have been added by a parallel request
			$action = 'showAlreadyMember';
		}
	    else
	    {
			// show an unspecified error
			$action = 'showGenericFailure';
	    }


	   	/////
	    // second perform the adding to the waiting list (this may set a new action)
	    ////
	    if ($action == 'addToWaitingList')
	    {
			$to_confirm = ($this->subscription_type == IL_CRS_SUBSCRIPTION_CONFIRMATION) ?
				ilWaitingList::REQUEST_TO_CONFIRM : ilWaitingList::REQUEST_NOT_TO_CONFIRM;
			$sub_time = $this->container->inSubscriptionFairTime() ? $this->container->getSubscriptionFair() : time();

	    	if ($this->getWaitingList()->addWithChecks($ilUser->getId(), $mem_rol_id, $_POST['subject'], $to_confirm, $sub_time))
 			{
 				if ($this->container->inSubscriptionFairTime($sub_time))
				{
					// show info about adding in fair time
					$action = 'showAddedToWaitingListFair';
				}
				else
				{
					// maximum members reached
					$action = 'notifyAddedToWaitingList';
				}
			}
			elseif ($rbacreview->isAssigned($ilUser->getId(), $mem_rol_id))
			{
				$action = 'showAlreadyMember';
			}
			elseif (ilWaitingList::_isOnList($ilUser->getId(), $this->container->getId()))
			{
				// check the failure of adding to the waiting list
				$action = 'showAlreadyOnWaitingList';
			}
			else
			{
				// show an unspecified error
				$action = 'showGenericFailure';
			}
	    }


	    /////
		// third perform the other actions
		////

		// get the link to the upper container
		$ilCtrl->setParameterByClass("ilrepositorygui", "ref_id",
			$tree->getParentId($this->container->getRefId()));

	    switch($action)
		{
			case 'notifyAdded':
				$this->setAccepted(true);

				// fim: [memsess] subscribe to events
				require_once("./Modules/Session/classes/class.ilEventParticipants.php");
				foreach ((array) $_POST["events"] as $event_id)
				{
					if ($event_id)
					{
						ilEventParticipants::_register($ilUser->getId(), $event_id);
					}
				}
				// fim.

				$this->participants->sendNotification($this->participants->NOTIFY_ADMINS,$ilUser->getId());
				$this->participants->sendNotification($this->participants->NOTIFY_REGISTERED,$ilUser->getId());
//fau: courseUdf - send external notifications
				$this->participants->sendExternalNotifications($this->container, $ilUser);
// fau.
				include_once './Modules/Forum/classes/class.ilForumNotification.php';
				ilForumNotification::checkForumsExistsInsert($this->container->getRefId(), $ilUser->getId());
								
				if($this->container->getType() == "crs")
				{
					$this->container->checkLPStatusSync($ilUser->getId());
				}

				if(!$_SESSION["pending_goto"])
				{
					ilUtil::sendSuccess($this->lng->txt("crs_subscription_successful"),true);
					$this->ctrl->returnToParent($this);
				}
				else
				{
					$tgt = $_SESSION["pending_goto"];
					unset($_SESSION["pending_goto"]);
					ilUtil::redirect($tgt);
				}
				break;

			case 'notifyAddedToWaitingList':
				$this->setAccepted(true);

				$this->participants->sendAddedToWaitingList($ilUser->getId(), $this->getWaitingList());	// mail to user
				if ($this->subscription_type == IL_CRS_SUBSCRIPTION_CONFIRMATION)
				{
					$this->participants->sendSubscriptionRequestToAdmins($ilUser->getId());				// mail to admins
				}
// fau: courseUdf - send external notifications
				$this->participants->sendExternalNotifications($this->container, $ilUser);
// fau.

				$info = sprintf($this->lng->txt('sub_added_to_waiting_list'), $this->getWaitingList()->getPositionInfo($ilUser->getId()));
				ilUtil::sendSuccess($info,true);
				$ilCtrl->redirectByClass("ilrepositorygui");
				break;

			case 'showLimitReached':
				ilUtil::sendFailure($this->lng->txt("crs_reg_limit_reached"),true);
				$ilCtrl->redirectByClass("ilrepositorygui");
				break;

			case 'showAlreadyMember':
				ilUtil::sendFailure($this->lng->txt("crs_reg_user_already_assigned"),true);
				$ilCtrl->redirectByClass("ilrepositorygui");
				break;

			case 'showAddedToWaitingListFair':
				$this->setAccepted(true);
// fau: courseUdf - send external notifications
				$this->participants->sendExternalNotifications($this->container, $ilUser);
// fau.

				ilUtil::sendSuccess($this->lng->txt("sub_fair_added_to_waiting_list"),true);
				$ilCtrl->redirectByClass("ilrepositorygui");
				break;

			case 'showAlreadyOnWaitingList':
				ilUtil::sendFailure($this->lng->txt("crs_reg_user_on_waiting_list"),true);
				$ilCtrl->redirectByClass("ilrepositorygui");
				break;

			case 'showGenericFailure':
				ilUtil::sendFailure($this->lng->txt("crs_reg_user_generic_failure"),true);
				$ilCtrl->redirectByClass("ilrepositorygui");
				break;

			default:
				break;
		}
	}
	// fim.
// fau.
	
	/**
	 * Init course participants
	 *
	 * @access protected
	 */
	protected function initParticipants()
	{
		include_once('./Modules/Course/classes/class.ilCourseParticipants.php');
		$this->participants = ilCourseParticipants::_getInstanceByObjId($this->obj_id);
	}
	

    /**
     * @see ilRegistrationGUI::initWaitingList()
     * @access protected
     */
    protected function initWaitingList()
    {
		include_once './Modules/Course/classes/class.ilCourseWaitingList.php';
		$this->waiting_list = new ilCourseWaitingList($this->container->getId());
    }
	
    /**
     * @see ilRegistrationGUI::isWaitingListActive()
     */
    protected function isWaitingListActive()
    {
		global $ilUser;
		static $active = null;
		
		if($active !== null)
		{
			return $active;
		}

// fau: fairSub - set waiting list to active if in fair time
		if ($this->container->inSubscriptionFairTime())
		{
			return $active = true;
		}
// fau.
		if(!$this->container->enabledWaitingList() or !$this->container->isSubscriptionMembershipLimited())
		{
			return $active = false;
		}
		if(!$this->container->getSubscriptionMaxMembers())
		{
			return $active = false;
		}

		$free = max(0,$this->container->getSubscriptionMaxMembers() - $this->participants->getCountMembers());
		return $active = (!$free or $this->getWaitingList()->getCountUsers());
    }
}
?>