<?php
/*
        +-----------------------------------------------------------------------------+
        | ILIAS open source                                                           |
        +-----------------------------------------------------------------------------+
        | Copyright (c) 1998-2006 ILIAS open source, University of Cologne            |
        |                                                                             |
        | This program is free software; you can redistribute it and/or               |
        | modify it under the terms of the GNU General Public License                 |
        | as published by the Free Software Foundation; either version 2              |
        | of the License, or (at your option) any later version.                      |
        |                                                                             |
        | This program is distributed in the hope that it will be useful,             |
        | but WITHOUT ANY WARRANTY; without even the implied warranty of              |
        | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
        | GNU General Public License for more details.                                |
        |                                                                             |
        | You should have received a copy of the GNU General Public License           |
        | along with this program; if not, write to the Free Software                 |
        | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
        +-----------------------------------------------------------------------------+
*/

include_once('./Services/Membership/classes/class.ilRegistrationGUI.php');
include_once './Modules/Group/classes/class.ilGroupMembershipMailNotification.php';

/**
* GUI class for group registrations
*
*
* @author Stefan Meyer <smeyer.ilias@gmx.de>
* @version $Id$
*
* @ingroup ModulesGroup 
*/
class ilGroupRegistrationGUI extends ilRegistrationGUI
{
	/**
	 * Constructor
	 *
	 * @access public
	 * @param object container object
	 */
	public function __construct($a_container)
	{
		parent::__construct($a_container);

		// fim: [memcond] set the actual registration type according to the studydata condition
		if ($this->matches_studycond
		or $this->container->getRegistrationType() == GRP_REGISTRATION_DEACTIVATED)
		{
		    $this->registration_type = $this->container->getRegistrationType();
		}
		else
		{
			$this->registration_type = GRP_REGISTRATION_REQUEST;
		}
		// fim.

		// fim: [memlot] initialize use_lot_list
		if ($this->container->isMembershipLimited()
			and $this->container->enabledLotList())
		{
			$this->use_lot_list = true;
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
		global $ilUser,$ilTabs;
		
		$next_class = $this->ctrl->getNextClass($this);
		
		if($this->getWaitingList()->isOnList($ilUser->getId()))
		{
			$ilTabs->activateTab('leave');
		}

		switch($next_class)
		{
			default:
				$cmd = $this->ctrl->getCmd("show");
				$this->$cmd();
				break;
		}
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
		return $this->lng->txt('grp_registration');
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
		if($this->container->getInformation())
		{
			$imp = new ilNonEditableValueGUI($this->lng->txt('crs_important_info'),'',true);
			$value =  nl2br(ilUtil::makeClickable($this->container->getInformation(), true));
			$imp->setValue($value);
			$this->form->addItem($imp);
		}
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

		if($this->container->isRegistrationUnlimited())
		{
			$reg = new ilNonEditableValueGUI($this->lng->txt('mem_reg_period'));
			$reg->setValue($this->lng->txt('mem_unlimited'));
			$this->form->addItem($reg);
			return true;
		}
		
		$start = $this->container->getRegistrationStart();
		$end = $this->container->getRegistrationEnd();
		
		
		if(ilDateTime::_before($now,$start))
		{
			$tpl = new ilTemplate('tpl.registration_period_form.html',true,true,'Services/Membership');
			$tpl->setVariable('TXT_FIRST',$this->lng->txt('mem_start'));
			$tpl->setVariable('FIRST',ilDatePresentation::formatDate($start));
			
			$tpl->setVariable('TXT_END',$this->lng->txt('mem_end'));
			$tpl->setVariable('END',ilDatePresentation::formatDate($end));
			
			$warning = $this->lng->txt('mem_reg_not_started');

			// fim: [memlot] special info for lot list
			if ($this->use_lot_list)
			{
				$warning .= '<br />' . $this->lng->txt('mem_lot_list_info');
			}
			// fim.
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
		
		$reg = new ilCustomInputGUI($this->lng->txt('mem_reg_period'));
		$reg->setHtml($tpl->get());
		if(strlen($warning))
		{
			// Disable registration
			$this->enableRegistration(false);
			#$reg->setAlert($warning);
			ilUtil::sendFailure($warning);
		}
		$this->form->addItem($reg);
		return true;
	}
	
	/**
	 * fill max member informations
	 *
	 * @access protected
	 * @return
	 */
	protected function fillMaxMembers()
	{
		global $ilUser;
		
		if(!$this->container->isMembershipLimited())
		{
			return true;
		}

		$tpl = new ilTemplate('tpl.max_members_form.html',true,true,'Services/Membership');

		if($this->container->getMinMembers())
		{
			$tpl->setVariable('TXT_MIN',$this->lng->txt('mem_min_users'));
			$tpl->setVariable('NUM_MIN',$this->container->getMinMembers());
		}

		if($this->container->getMaxMembers())
		{
			$tpl->setVariable('TXT_MAX',$this->lng->txt('mem_max_users'));
			$tpl->setVariable('NUM_MAX',$this->container->getMaxMembers());

			$tpl->setVariable('TXT_FREE',$this->lng->txt('mem_free_places').":");
			$free = max(0,$this->container->getMaxMembers() - $this->participants->getCountMembers());

			if($free)
				$tpl->setVariable('NUM_FREE',$free);
			else
				$tpl->setVariable('WARN_FREE',$free);

			// fim: [memlot] give info for lot list
			if ($this->container->enabledLotList())
			{
				include_once('./Services/Membership/classes/class.ilSubscribersLot.php');
				$lot_list = new ilSubscribersLot($this->container->getId());

				$tpl->setVariable('TXT_WAIT',$this->lng->txt('mem_lot_list_count'));
				$tpl->setVariable('NUM_WAIT',(int) $lot_list->getCountUsers());
			}
			else
			{
				include_once('./Modules/Group/classes/class.ilGroupWaitingList.php');
				$waiting_list = new ilGroupWaitingList($this->container->getId());

				if(
					$this->container->isWaitingListEnabled() and
					$this->container->isMembershipLimited() and
					(!$free or $waiting_list->getCountUsers()))
				{
					if($waiting_list->isOnList($ilUser->getId()))
					{
						$tpl->setVariable('TXT_WAIT',$this->lng->txt('mem_waiting_list_position'));
						$tpl->setVariable('NUM_WAIT',$waiting_list->getPosition($ilUser->getId()));

					}
					else
					{
						$tpl->setVariable('TXT_WAIT',$this->lng->txt('subscribers_or_waiting_list'));
						if($free and $waiting_list->getCountUsers())
							$tpl->setVariable('WARN_WAIT',$waiting_list->getCountUsers());
						else
							$tpl->setVariable('NUM_WAIT',$waiting_list->getCountUsers());

					}
				}
			}
			// fim.

			$alert = '';
			// fim: [memlot] give information
			if ($this->container->enabledLotList() and $lot_list->isOnList($ilUser->getId()))
			{
				$this->enableRegistration(false);
			}
			elseif ($this->container->enabledLotList())
			{
				$small_alert = $this->lng->txt('mem_lot_list_possible');
				$this->join_button_text = $this->lng->txt('mem_request_lot');
			}
			elseif(
				!$free and
				!$this->container->isWaitingListEnabled())
			// fim.
			{
				// Disable registration
				$this->enableRegistration(false);
				$alert = $this->lng->txt('mem_alert_no_places');
			}
			elseif(
					$this->container->isWaitingListEnabled() and
					$this->container->isMembershipLimited() and
					$waiting_list->isOnList($ilUser->getId()))
			{
				// Disable registration
				$this->enableRegistration(false);
			}
			elseif(
					!$free and
					$this->container->isWaitingListEnabled() and
					$this->container->isMembershipLimited())
			{
				$alert = $this->lng->txt('grp_warn_no_max_set_on_waiting_list');
			}
			// fim: [memcond] specific check for waiting lists (see also add() function)
			elseif(
				$free and
				$this->container->isWaitingListEnabled() and
				$this->container->isMembershipLimited())
			{
				$waiting_list = $this->getWaitingList();
				$waiting = $waiting_list->getCountUsers();
				// $to_confirm = $waiting_list->getCountToConfirm();

				// if ($waiting > $to_confirm or $waiting >= $free)
				if ($waiting >= $free)
				{
					$alert = $this->lng->txt('grp_warn_wl_set_on_waiting_list');
					// fim: [meminf] set join button text
					$this->join_button_text = $this->lng->txt('mem_request_waiting');
					// fim.
				}
			}
			// fim.
		}
		// fim.

		$max = new ilCustomInputGUI($this->lng->txt('mem_participants'));
		$max->setHtml($tpl->get());
		if(strlen($alert))
		{
			#$max->setAlert($alert);
			ilUtil::sendFailure($alert);
		}
		// fim: [memlot] add a small alert to max members info
		if (strlen($small_alert))
		{
			$max->setAlert($small_alert);
		}
		// fim.
		$this->form->addItem($max);
	}
	
	/**
	 * fill registration procedure
	 *
	 * @access protected
	 * @param
	 * @return
	 */
	protected function fillRegistrationType()
	{
		global $ilUser;
		
		// fim: [meminf] don't show registration type if user is on lot list
		if ($this->getLotList()->isOnList($ilUser->getId()))
		{
			return true;
		}
		// fim.


		//fim: [memcond] check actual registration type
		switch($this->registration_type)
		// fim.
		{
			case GRP_REGISTRATION_DEACTIVATED:
				$reg = new ilNonEditableValueGUI($this->lng->txt('mem_reg_type'));
				$reg->setValue($this->lng->txt('grp_reg_disabled'));
				#$reg->setAlert($this->lng->txt('grp_reg_deactivated_alert'));
				$this->form->addItem($reg);
		
				// Disable registration
				$this->enableRegistration(false);
				
				break;
				
			case GRP_REGISTRATION_PASSWORD:
				// fim: [memcond] set password subscription info for studycond
				$txt = new ilCustomInputGUI($this->lng->txt('mem_reg_type'));
				if ($this->has_studycond)
				{
					$txt->setHTML(sprintf($this->lng->txt('grp_pass_request_studycond'), $this->describe_studycond));
				}
				else
				{
					$txt->setHTML($this->lng->txt('grp_pass_request'));
				}
				// fim.

				$pass = new ilTextInputGUI($this->lng->txt('passwd'),'grp_passw');
				$pass->setInputType('password');
				$pass->setSize(12);
				$pass->setMaxLength(32);
				#$pass->setRequired(true);
				// fim: [memlot] use different message for lot
				$pass->setInfo(	$this->use_lot_list ?
								$this->lng->txt('group_password_registration_lot') :
								$this->lng->txt('group_password_registration_msg'));
				// fim.
				
				$txt->addSubItem($pass);
				$this->form->addItem($txt);
				break;
				
			case GRP_REGISTRATION_REQUEST:
				
				/* fim: [meminf] allow "request" subject if waiting list is active
				// no "request" info if waiting list is active
				if($this->isWaitingListActive())
					return true;
				*/

				// fim: [memcond] set confirmation subscription info for studycond
				$txt = new ilCustomInputGUI($this->lng->txt('mem_reg_type'));
				if ($this->has_studycond and $this->container->getRegistrationType() == GRP_REGISTRATION_DIRECT)
				{
					$txt->setHTML(sprintf($this->lng->txt('group_req_direct_studycond'), $this->describe_studycond));
				}
				elseif ($this->has_studycond and $this->container->getRegistrationType() == GRP_REGISTRATION_PASSWORD)
				{
					$txt->setHTML(sprintf($this->lng->txt('grp_pass_request_studycond'), $this->describe_studycond));
				}
				else
				{
					$txt->setHTML($this->lng->txt('grp_reg_request'));
				}
				// fim.

			
				$sub = new ilTextAreaInputGUI($this->lng->txt('grp_reg_subject'),'subject');
				$sub->setValue($_POST['subject']);
				// fim: [memlot] use different message for lot
				$sub->setInfo(	$this->use_lot_list ?
								$this->lng->txt('group_req_registration_lot') :
								$this->lng->txt('group_req_registration_msg'));
				// fim.
				// fim: [memad] extend size of subject field
				$sub->setRows(10);
				// fim.
				if($this->participants->isSubscriber($ilUser->getId()))
				{
					$sub_data = $this->participants->getSubscriberData($ilUser->getId());
					$sub->setValue($sub_data['subject']);
					$sub->setInfo('');
					// fim: [memad] question as message if user is subscribed
					ilUtil::sendQuestion($this->lng->txt('mem_user_already_subscribed'));
					// fim.
					$this->enableRegistration(false);
				}
				// fim: [memad] get subject also for waiting list
				elseif ($this->getWaitingList()->isToConfirm($ilUser->getId()))
				{
					$sub->setValue($this->getWaitingList()->getSubject($ilUser->getId()));
					$sub->setInfo('');
				}
				// fim.
				$txt->addSubItem($sub);
				$this->form->addItem($txt);
				// fim: [memlot] set join_button_text
				$this->join_button_text = $this->lng->txt('mem_request_joining');
				// fim.
				break;
				
			case GRP_REGISTRATION_DIRECT:

				// no "direct registration" info if waiting list is active
				if($this->isWaitingListActive())
					return true;

				// fim: [memcond] set password subscription info for studycond
				$txt = new ilCustomInputGUI($this->lng->txt('mem_reg_type'));
				if ($this->has_studycond)
				{
					$txt->setHTML(sprintf($this->lng->txt('group_req_direct_studycond'), $this->describe_studycond));
				}
				else
				{
					$txt->setHTML($this->lng->txt('group_req_direct'));
				}
				// fim.

    			// fim: [memlot] use different message for lot
				$txt->setInfo(	$this->use_lot_list ?
								$this->lng->txt('grp_reg_direct_info_screen_lot') :
								$this->lng->txt('grp_reg_direct_info_screen'));
				// fim.
				
				$this->form->addItem($txt);
				break;

			default:
				return true;
		}
		
		return true;
	}
	
	/**
	 * Add group specific command buttons
	 * @return 
	 */
	protected function addCommandButtons()
	{
		global $ilUser;
		
		parent::addCommandButtons();
		

		// fim: [memad] use the actual subscription type for update buttons
		switch($this->registration_type)
		// fim.
		{
			// fim: [memad] allow cancelling subscription request even if registration is deactivated
			case GRP_REGISTRATION_DEACTIVATED:
				if($this->participants->isSubscriber($ilUser->getId()))
				{
					$this->form->addCommandButton('cancelSubscriptionRequest', $this->lng->txt('grp_cancel_subscr_request'));
					$this->form->addCommandButton('cancel', $this->lng->txt('cancel'));
				}
				break;
			// fim.

			case GRP_REGISTRATION_REQUEST:
				if($this->participants->isSubscriber($ilUser->getId()))
				{
					$this->form->clearCommandButtons();
					$this->form->addCommandButton('updateSubscriptionRequest', $this->lng->txt('grp_update_subscr_request'));				
					$this->form->addCommandButton('cancelSubscriptionRequest', $this->lng->txt('grp_cancel_subscr_request'));				
					// fim: [memad] add cancel button
					$this->form->addCommandButton('cancel', $this->lng->txt('cancel'));
					// fim.
				}
				else
				{
					if(!$this->isRegistrationPossible())
					{
						return false;
					}
					$this->form->clearCommandButtons();
					$this->form->addCommandButton('join', $this->lng->txt('grp_join_request'));
					$this->form->addCommandButton('cancel',$this->lng->txt('cancel'));
				}
				break;				
		}
		return true;		
	}
	
	
	/**
	 * validate join request
	 *
	 * @access protected
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
		
		if(!$this->isRegistrationPossible())
		{
			$this->join_error = $this->lng->txt('mem_error_preconditions');
			return false;
		}
		// fim: [memcond] check actual registration type
		if($this->registration_type == GRP_REGISTRATION_PASSWORD)
		// fim.
		{
			if(!strlen($pass = ilUtil::stripSlashes($_POST['grp_passw'])))
			{
				$this->join_error = $this->lng->txt('err_wrong_password');
				return false;
			}
			if(strcmp($pass,$this->container->getPassword()) !== 0)
			{
				$this->join_error = $this->lng->txt('err_wrong_password');
				return false;
			}
		}
		if(!$this->validateCustomFields())
		{
			$this->join_error = $this->lng->txt('fill_out_all_required_fields');
			return false;
		}
		if(!$this->validateAgreement())
		{
			$this->join_error = $this->lng->txt($this->type.'_agreement_required');
			return false;
		}
		
		return true;
	}
	
	// fim: [memcond] use condition based subscription type
	// fim: [memlot] add support for lot list
	// fim: [meminf] add subject to waiting list
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

		// set aggreement accepted
		$this->setAccepted(true);		

		// get the membership role id
	    $mem_rol_id = $this->participants->getRoleId(IL_GRP_MEMBER);

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


	    /////
		// first decide what to do
		// the sequence and nesting of checks is important!
		/////
		if ($this->participants->isAssigned($ilUser->getId()))
		{
	        // user is already a participant
	        $action = 'showAlreadyMember';
	    }
		elseif ($this->container->isMembershipLimited())
		{
			$max = $this->container->getMaxMembers();
			$free = max(0, $max - $this->participants->getCountMembers());

	        if ($this->container->enabledLotList())
			{
	            if ($this->registration_type == GRP_REGISTRATION_REQUEST)
				{
	                // let admin decide to add a user to the lot list
	                $action = 'addToSubscribers';
	            }
				else
				{
	                // directly add to lot list
	                $action = 'addToLotList';
	            }
	        }
			elseif($this->container->isWaitingListEnabled())
			{
				include_once('./Modules/Group/classes/class.ilGroupWaitingList.php');
				$waiting_list = new ilGroupWaitingList($this->container->getId());

	           	if ($this->registration_type == GRP_REGISTRATION_REQUEST)
				{
					// add subscription requests to waiting list (to keep them in the order)
					$action = 'addToWaitingList';
				}
				else
				{
	 				$waiting = $waiting_list->getCountUsers();
	 				if ($waiting >= $free)
					{
						// add to waiting list if all free places have waiting candidates
						$action = 'addToWaitingList';
					}
					elseif ($this->participants->addLimited($ilUser->getId(),IL_GRP_MEMBER, $max - $waiting))
					{
						// try to add the users
						// free places are those without waiting candidates

		                // member could be added
	                    $action = 'notifyAdded';
	                }
					else
					{
		                //member could not be added, try waiting list
	                    $action = 'addToWaitingList';
	                }
				}
			}
            elseif ($this->registration_type == GRP_REGISTRATION_REQUEST)
			{
	            // add a registration request
	        	$action = 'addToSubscribers';
	        }
			elseif ($this->participants->addLimited($ilUser->getId(),IL_GRP_MEMBER, $max))
			{
	            // member could be added
            	$action = 'notifyAdded';
	        }
			elseif ($rbacreview->isAssigned($ilUser->getId(), $mem_rol_id))
			{
				// may have been added by aparallel request
				$action = 'showAlreadyMember';
			}
	        else
			{
	            // maximum members reached and no list active
            	$action = 'showLimitReached';
	        }
	    }
		elseif ($this->registration_type == GRP_REGISTRATION_REQUEST)
		{
	            // add a registration request
	    	$action = 'addToSubscribers';
		}
		elseif ($this->participants->addLimited($ilUser->getId(),IL_GRP_MEMBER, 0))
		{
	        // member could be added
           	$action = 'notifyAdded';
	    }
		elseif ($rbacreview->isAssigned($ilUser->getId(), $mem_rol_id))
		{
			// may have been added by aparallel request
			$action = 'showAlreadyMember';
		}
	    else
	    {
			// show an unspecified error
			$action = 'showGenericFailure';
	    }

	    /////
	    // second perform an adding to waiting list (may set a new action)
	    ////
	    if ($action == 'addToWaitingList')
	    {
	    	$to_confirm = ($this->registration_type == GRP_REGISTRATION_REQUEST);

	    	if ($waiting_list->addWithChecks($ilUser->getId(), $mem_rol_id, $_POST['subject'], $to_confirm))
 			{
				// maximum members reached
				$action = 'notifyAddedToWaitingList';
			}
			elseif ($rbacreview->isAssigned($ilUser->getId(), $mem_rol_id))
			{
				$action = 'showAlreadyMember';
			}
			elseif ($waiting_list->_isOnList($ilUser->getId(), $this->container->getId()))
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
		// then perform the other actions
		////

		// get the link to the upper container
		$ilCtrl->setParameterByClass("ilrepositorygui", "ref_id",
				$tree->getParentId($this->container->getRefId()));

		switch($action)
		{
			case 'addToLotList':
				include_once('./Services/Membership/classes/class.ilSubscribersLot.php');
				$lot_list = new ilSubscribersLot($this->container->getId());
				$lot_list->addToList($ilUser->getId());
				ilUtil::sendInfo($this->lng->txt('mem_added_to_lot_list'), true);
				$ilCtrl->redirectByClass("ilrepositorygui");
				break;

			case 'addToSubscribers':
				$this->participants->addSubscriber($ilUser->getId());
				$this->participants->updateSubscriptionTime($ilUser->getId(),time());
				$this->participants->updateSubject($ilUser->getId(),ilUtil::stripSlashes($_POST['subject']));
				$this->participants->sendNotification(
					ilGroupMembershipMailNotification::TYPE_NOTIFICATION_REGISTRATION_REQUEST,
					$ilUser->getId()
				);
				ilUtil::sendSuccess($this->lng->txt("application_completed"),true);
				$ilCtrl->redirectByClass("ilrepositorygui");
				break;
			
			case 'notifyAdded':
				$this->participants->sendNotification(
					ilGroupMembershipMailNotification::TYPE_NOTIFICATION_REGISTRATION,
					$ilUser->getId()
				);
				$this->participants->sendNotification(
					ilGroupMembershipMailNotification::TYPE_SUBSCRIBE_MEMBER,
					$ilUser->getId()
				);

				include_once './Modules/Forum/classes/class.ilForumNotification.php';
				ilForumNotification::checkForumsExistsInsert($this->container->getRefId(), $ilUser->getId());

				if(!$_SESSION["pending_goto"])
				{
					ilUtil::sendSuccess($this->lng->txt("grp_registration_completed"),true);
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
				$info = sprintf($this->lng->txt('grp_added_to_list'),
					$this->container->getTitle(),
					$waiting_list->getPosition($ilUser->getId()));
				$this->participants->sendNotification(
					ilGroupMembershipMailNotification::TYPE_WAITING_LIST_MEMBER,
					$ilUser->getId());
				ilUtil::sendInfo($info, true);
				$ilCtrl->redirectByClass("ilrepositorygui");
				break;

			case 'showLimitReached':
				ilUtil::sendFailure($this->lng->txt("grp_reg_limit_reached"),true);
				$ilCtrl->redirectByClass("ilrepositorygui");
				break;

			case 'showAlreadyMember':
				ilUtil::sendFailure($this->lng->txt("grp_reg_user_already_assigned"),true);
				$this->ctrl->returnToParent($this);
				break;

			case 'showAlreadyOnWaitingList':
				ilUtil::sendFailure($this->lng->txt("grp_reg_user_on_waiting_list"),true);
				$ilCtrl->redirectByClass("ilrepositorygui");
				break;

			case 'showGenericFailure':
				ilUtil::sendFailure($this->lng->txt("grp_reg_user_generic_failure"),true);
				$ilCtrl->redirectByClass("ilrepositorygui");
				break;

			default:
				break;
		}
	}
	// fim.

	
	/**
	 * Init course participants
	 *
	 * @access protected
	 */
	protected function initParticipants()
	{
		include_once('./Modules/Group/classes/class.ilGroupParticipants.php');
		$this->participants = ilGroupParticipants::_getInstanceByObjId($this->obj_id);
	}
	
    /**
     * @see ilRegistrationGUI::initWaitingList()
     * @access protected
     */
    protected function initWaitingList()
    {
		include_once './Modules/Group/classes/class.ilGroupWaitingList.php';
		$this->waiting_list = new ilGroupWaitingList($this->container->getId());
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
		if(!$this->container->getMaxMembers())
		{
			return $active = false;
		}
		if(
				!$this->container->isWaitingListEnabled() or
				!$this->container->isMembershipLimited())
		{
			return $active = false;
		}

		$free = max(0,$this->container->getMaxMembers() - $this->participants->getCountMembers());
		return $active = (!$free or $this->getWaitingList()->getCountUsers());
    }
}
?>
