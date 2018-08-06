<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once './Services/Mail/classes/class.ilMailNotification.php';

/**
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 * @version $Id$
 * 
 * @ingroup ServicesMembership
 */
class ilGroupMembershipMailNotification extends ilMailNotification
{
	// v Notifications affect members & co. v
	const TYPE_ADMISSION_MEMBER = 20;
	const TYPE_DISMISS_MEMBER 	= 21;
	
	const TYPE_ACCEPTED_SUBSCRIPTION_MEMBER = 22;
	const TYPE_REFUSED_SUBSCRIPTION_MEMBER = 23;
	
	const TYPE_STATUS_CHANGED = 24;
	
	const TYPE_BLOCKED_MEMBER = 25;
	const TYPE_UNBLOCKED_MEMBER = 26;
	
	const TYPE_UNSUBSCRIBE_MEMBER = 27;
	const TYPE_SUBSCRIBE_MEMBER = 28;
	const TYPE_WAITING_LIST_MEMBER = 29;

	// v Notifications affect admins v
	const TYPE_NOTIFICATION_REGISTRATION = 30;
	const TYPE_NOTIFICATION_REGISTRATION_REQUEST = 31;
	const TYPE_NOTIFICATION_UNSUBSCRIBE = 32;


// fau: fairSub - additional notification types
	const TYPE_ACCEPTED_STILL_WAITING = 51;				//member
	const TYPE_AUTOFILL_STILL_WAITING = 52;				//member
	const TYPE_AUTOFILL_STILL_TO_CONFIRM = 53;			//member
	const TYPE_NOTIFICATION_AUTOFILL_TO_CONFIRM = 63;	//admins
// fau.

	/**
	 * @var array $permanent_enabled_notifications
	 * Notifications which are not affected by "mail_grp_member_notification" setting
	 * because they addresses admins
	 */
	protected $permanent_enabled_notifications = array(
		self::TYPE_NOTIFICATION_REGISTRATION,
		self::TYPE_NOTIFICATION_REGISTRATION_REQUEST,
		self::TYPE_NOTIFICATION_UNSUBSCRIBE,
// fau: fairSub - added notification to permanently enabled
		self::TYPE_NOTIFICATION_AUTOFILL_TO_CONFIRM
// fau.
	);
	
	private $force_sending_mail = false;
	

	

	/**
	 *
	 */
	public function __construct()
	{
		parent::__construct();
	}

// fau: fairSub - get/set waiting list object for detailed information in the mails
	/**
	 * Set the waiting list
	 * @param ilGroupWaitingList $a_waiting_list
	 */
	public function setWaitingList($a_waiting_list = null)
	{
		$this->waiting_list = $a_waiting_list;
	}

	/**
	 * Get the waiting list
	 * @return ilGroupWaitingList
	 */
	public function getWaitingList()
	{
		if (!isset($this->waiting_list))
		{
			include_once('./Modules/Group/classes/class.ilGroupWaitingList.php');
			$this->waiting_list = new ilGroupWaitingList(ilObject::_lookupObjectId($this->getRefId()));
		}
		else
		{
			return $this->waiting_list;
		}
	}
// fau.

	/**
	 * Force sending mail independent from global setting
	 * @param type $a_status
	 */
	public function forceSendingMail($a_status)
	{
		$this->force_sending_mail = $a_status;
	}
	
	
	
	/**
	 * Send notifications
	 * @return 
	 */
	public function send()
	{
		if(!$this->isNotificationTypeEnabled($this->getType()))
		{
			$GLOBALS['DIC']->logger()->grp()->info('Membership mail disabled globally.');
			return false;
		}
		// parent::send();
		
		switch($this->getType())
		{
			case self::TYPE_ADMISSION_MEMBER:
				
				foreach($this->getRecipients() as $rcp)
				{
					$this->initLanguage($rcp);
					$this->initMail();
					$this->setSubject(
						sprintf($this->getLanguageText('grp_mail_admission_new_sub'),$this->getObjectTitle(true))
					);
					$this->setBody(ilMail::getSalutation($rcp,$this->getLanguage()));
					$this->appendBody("\n\n");
					$this->appendBody(
						sprintf($this->getLanguageText('grp_mail_admission_new_bod'),$this->getObjectTitle())
					);
					$this->appendBody("\n\n");
					$this->appendBody($this->getLanguageText('grp_mail_permanent_link'));
					$this->appendBody("\n\n");
					$this->appendBody($this->createPermanentLink());
					$this->getMail()->appendInstallationSignature(true);
										
					$this->sendMail(array($rcp),array('system'));
				}
				break;
				
			case self::TYPE_DISMISS_MEMBER:
				
				foreach($this->getRecipients() as $rcp)
				{
					$this->initLanguage($rcp);
					$this->initMail();
					$this->setSubject(
						sprintf($this->getLanguageText('grp_mail_dismiss_sub'),$this->getObjectTitle(true))
					);
					$this->setBody(ilMail::getSalutation($rcp,$this->getLanguage()));
					$this->appendBody("\n\n");
					$this->appendBody(
						sprintf($this->getLanguageText('grp_mail_dismiss_bod'),$this->getObjectTitle())
					);
					$this->getMail()->appendInstallationSignature(true);
					$this->sendMail(array($rcp),array('system'));
				}
				break;
				
				
			case self::TYPE_NOTIFICATION_REGISTRATION:
				
				foreach($this->getRecipients() as $rcp)
				{
					$this->initLanguage($rcp);
					$this->initMail();
					$this->setSubject(
						sprintf($this->getLanguageText('grp_mail_notification_reg_sub'),$this->getObjectTitle(true))
					);
					$this->setBody(ilMail::getSalutation($rcp,$this->getLanguage()));
					$this->appendBody("\n\n");
					
					$info = $this->getAdditionalInformation();
					$this->appendBody(
						sprintf($this->getLanguageText('grp_mail_notification_reg_bod'),
							$this->userToString($info['usr_id']),
							$this->getObjectTitle()
						)
					);
					$this->appendBody("\n\n");
					$this->appendBody($this->getLanguageText('grp_mail_permanent_link'));
					$this->appendBody("\n\n");
					$this->appendBody($this->createPermanentLink(array(),'_mem'));
					
					$this->appendBody("\n\n");
					$this->appendBody($this->getLanguageText('grp_notification_explanation_admin'));
					
					$this->getMail()->appendInstallationSignature(true);
					$this->sendMail(array($rcp),array('system'));
				}
				break;
				
			case self::TYPE_UNSUBSCRIBE_MEMBER:
				
				foreach($this->getRecipients() as $rcp)
				{
					$this->initLanguage($rcp);
					$this->initMail();
					$this->setSubject(
						sprintf($this->getLanguageText('grp_mail_unsubscribe_member_sub'),$this->getObjectTitle(true))
					);
					$this->setBody(ilMail::getSalutation($rcp,$this->getLanguage()));
					$this->appendBody("\n\n");
					$this->appendBody(
						sprintf($this->getLanguageText('grp_mail_unsubscribe_member_bod'),$this->getObjectTitle())
					);
					$this->getMail()->appendInstallationSignature(true);
					$this->sendMail(array($rcp),array('system'));
				}
				break;
				
			case self::TYPE_NOTIFICATION_UNSUBSCRIBE:

				foreach($this->getRecipients() as $rcp)
				{
					$this->initLanguage($rcp);
					$this->initMail();
					$this->setSubject(
						sprintf($this->getLanguageText('grp_mail_notification_unsub_sub'),$this->getObjectTitle(true))
					);
					$this->setBody(ilMail::getSalutation($rcp,$this->getLanguage()));
					$this->appendBody("\n\n");
					
					$info = $this->getAdditionalInformation();
					$this->appendBody(
						sprintf($this->getLanguageText('grp_mail_notification_unsub_bod'),
							$this->userToString($info['usr_id']),
							$this->getObjectTitle()
						)
					);
					$this->appendBody("\n\n");
					$this->appendBody($this->getLanguageText('grp_mail_notification_unsub_bod2'));
					$this->appendBody("\n\n");
					$this->appendBody($this->createPermanentLink(array(),'_mem'));
					
					$this->appendBody("\n\n");
					$this->appendBody($this->getLanguageText('grp_notification_explanation_admin'));
					
					$this->getMail()->appendInstallationSignature(true);
					$this->sendMail(array($rcp),array('system'));
				}
				break;
				
				
			case self::TYPE_SUBSCRIBE_MEMBER:

				foreach($this->getRecipients() as $rcp)
				{
					$this->initLanguage($rcp);
					$this->initMail();
					$this->setSubject(
						sprintf($this->getLanguageText('grp_mail_subscribe_member_sub'),$this->getObjectTitle(true))
					);
					$this->setBody(ilMail::getSalutation($rcp,$this->getLanguage()));
					$this->appendBody("\n\n");
					$this->appendBody(
						sprintf($this->getLanguageText('grp_mail_subscribe_member_bod'),$this->getObjectTitle())
					);
					
					$this->appendBody("\n\n");
					$this->appendBody($this->getLanguageText('grp_mail_permanent_link'));
					$this->appendBody("\n\n");
					$this->appendBody($this->createPermanentLink());
					$this->getMail()->appendInstallationSignature(true);

					$this->sendMail(array($rcp),array('system'));
				}
				break;
				
				
			case self::TYPE_NOTIFICATION_REGISTRATION_REQUEST:
				
				foreach($this->getRecipients() as $rcp)
				{
					$this->initLanguage($rcp);
					$this->initMail();
					$this->setSubject(
						sprintf($this->getLanguageText('grp_mail_notification_reg_req_sub'),$this->getObjectTitle(true))
					);
					$this->setBody(ilMail::getSalutation($rcp,$this->getLanguage()));
					$this->appendBody("\n\n");
					
					$info = $this->getAdditionalInformation();
					$this->appendBody(
						sprintf($this->getLanguageText('grp_mail_notification_reg_req_bod'),
							$this->userToString($info['usr_id']),
							$this->getObjectTitle()
						)
					);
					$this->appendBody("\n\n");
					$this->appendBody($this->getLanguageText('grp_mail_notification_reg_req_bod2'));
					$this->appendBody("\n");
					$this->appendBody($this->createPermanentLink(array(),'_mem'));
					
					$this->appendBody("\n\n");
					$this->appendBody($this->getLanguageText('grp_notification_explanation_admin'));
					
					$this->getMail()->appendInstallationSignature(true);
					$this->sendMail(array($rcp),array('system'));
				}
				break;

			case self::TYPE_REFUSED_SUBSCRIPTION_MEMBER:

				foreach($this->getRecipients() as $rcp)
				{
					$this->initLanguage($rcp);
					$this->initMail();
					$this->setSubject(
						sprintf($this->getLanguageText('grp_mail_sub_dec_sub'),$this->getObjectTitle(true))
					);
					$this->setBody(ilMail::getSalutation($rcp,$this->getLanguage()));
					$this->appendBody("\n\n");
					$this->appendBody(
						sprintf($this->getLanguageText('grp_mail_sub_dec_bod'),$this->getObjectTitle())
					);

					$this->getMail()->appendInstallationSignature(true);
										
					$this->sendMail(array($rcp),array('system'));
				}
				break;

			case self::TYPE_ACCEPTED_SUBSCRIPTION_MEMBER:

				foreach($this->getRecipients() as $rcp)
				{
					$this->initLanguage($rcp);
					$this->initMail();
					$this->setSubject(
						sprintf($this->getLanguageText('grp_mail_sub_acc_sub'),$this->getObjectTitle(true))
					);
					$this->setBody(ilMail::getSalutation($rcp,$this->getLanguage()));
					$this->appendBody("\n\n");
					$this->appendBody(
						sprintf($this->getLanguageText('grp_mail_sub_acc_bod'),$this->getObjectTitle())
					);
					$this->appendBody("\n\n");
					$this->appendBody($this->getLanguageText('grp_mail_permanent_link'));
					$this->appendBody("\n\n");
					$this->appendBody($this->createPermanentLink());
					$this->getMail()->appendInstallationSignature(true);
										
					$this->sendMail(array($rcp),array('system'));
				}
				break;

// fau: fairSub - mail to subscriber, if set on waiting list after fair time - distinct between request and subscriptions
			case self::TYPE_WAITING_LIST_MEMBER:
				$waiting_list = $this->getWaitingList();

				foreach($this->getRecipients() as $rcp)
				{
					$to_confirm = $this->getWaitingList()->isToConfirm($rcp);

					$this->initLanguage($rcp);
					$this->initMail();

					$this->setSubject(sprintf($this->getLanguageText($to_confirm ? 'sub_mail_request_grp' : 'sub_mail_waiting_grp'), $this->getObjectTitle(true)));

					
					$this->setBody(ilMail::getSalutation($rcp,$this->getLanguage()));

					$this->appendBody("\n\n");
					$this->appendBody($this->getLanguageText($to_confirm ? 'sub_mail_request_added' : 'sub_mail_waiting_added'));

					$this->appendBody("\n\n");
					$this->appendBody($this->getLanguageText('mem_waiting_list_position') . ' ' . $waiting_list->getPositionInfo($rcp, $this->getLanguage()));

					$this->appendBody("\n\n");
					$this->appendBody($this->getLanguageText('grp_mail_permanent_link'));
					$this->appendBody("\n\n");
					$this->appendBody($this->createPermanentLink());

					$this->getMail()->appendInstallationSignature(true);
					$this->sendMail(array($rcp),array('system'));
				}
				break;
// fau.

// fau: fairSub - mail to subscriber with request if confirmed but still on waiting list
			case self::TYPE_ACCEPTED_STILL_WAITING:
				$waiting_list = $this->getWaitingList();

				foreach($this->getRecipients() as $rcp)
				{
					$this->initLanguage($rcp);
					$this->initMail();

					$this->setSubject(sprintf($this->getLanguageText('sub_mail_request_grp'),$this->getObjectTitle(true)));

					$this->setBody(ilMail::getSalutation($rcp,$this->getLanguage()));

					$this->appendBody("\n\n");
					$this->appendBody(sprintf($this->getLanguageText('sub_mail_request_accepted'), $this->getObjectTitle()));

					$this->appendBody("\n\n");
					$this->appendBody($this->getLanguageText('mem_waiting_list_position') . ' ' . $waiting_list->getPositionInfo($rcp, $this->getLanguage()));

					$this->appendBody("\n\n");
					$this->appendBody($this->getLanguageText('grp_mail_permanent_link'));
					$this->appendBody("\n\n");
					$this->appendBody($this->createPermanentLink());


					$this->getMail()->appendInstallationSignature(true);
					$this->sendMail(array($rcp),array('system'));
				}
				break;
// fau.

// fau: fairSub - mail to users on waiting list after course is initially filled after fair time - distinct between request and subscriptions
			case self::TYPE_AUTOFILL_STILL_WAITING:
				$waiting_list = $this->getWaitingList();

				foreach($this->getRecipients() as $rcp)
				{
					$to_confirm = $this->getWaitingList()->isToConfirm($rcp);
					$this->initLanguage($rcp);
					$this->initMail();

					$this->setSubject(sprintf($this->getLanguageText($to_confirm ? 'sub_mail_request_grp' : 'sub_mail_waiting_grp'), $this->getObjectTitle(true)));

					$this->setBody(ilMail::getSalutation($rcp,$this->getLanguage()));

					$this->appendBody("\n\n");
					$this->appendBody($this->getLanguageText($to_confirm ? 'sub_mail_request_autofill' : 'sub_mail_waiting_autofill'));

					$this->appendBody("\n\n");
					$this->appendBody($this->getLanguageText('mem_waiting_list_position') . ' ' . $waiting_list->getPositionInfo($rcp, $this->getLanguage()));

					$this->appendBody("\n\n");
					$this->appendBody($this->getLanguageText('grp_mail_permanent_link'));
					$this->appendBody($this->createPermanentLink());


					$this->getMail()->appendInstallationSignature(true);
					$this->sendMail(array($rcp),array('system'));
				}
				break;
// fau.

// fau: fairSub - notification to admins about remaining requests on the waiting list after auto fill
			case self::TYPE_NOTIFICATION_AUTOFILL_TO_CONFIRM:

				foreach($this->getRecipients() as $rcp)
				{
					$this->initLanguage($rcp);
					$this->initMail();

					$this->setSubject(sprintf($this->getLanguageText('sub_mail_autofill_requests_grp'),$this->getObjectTitle(true)));

					$this->setBody(ilMail::getSalutation($rcp,$this->getLanguage()));

					$this->appendBody("\n\n");
					$this->appendBody($this->getLanguageText('sub_mail_autofill_requests_body'));

					$this->appendBody("\n\n");
					$this->appendBody($this->getLanguageText('grp_mail_permanent_link'));
					$this->appendBody("\n\n");
					$this->appendBody($this->createPermanentLink());

					$this->getMail()->appendInstallationSignature(true);
					$this->sendMail(array($rcp),array('system'));
				}
				break;
// fau.

				
			case self::TYPE_STATUS_CHANGED:
				foreach($this->getRecipients() as $rcp)
				{
					$this->initLanguage($rcp);
					$this->initMail();
					$this->setSubject(
						sprintf($this->getLanguageText('grp_mail_status_sub'),$this->getObjectTitle(true))
					);
					$this->setBody(ilMail::getSalutation($rcp,$this->getLanguage()));
					$this->appendBody("\n\n");
					$this->appendBody(
						sprintf($this->getLanguageText('grp_mail_status_bod'),$this->getObjectTitle())
					);
					
					$this->appendBody("\n\n");
					$this->appendBody($this->createGroupStatus($rcp));

					$this->appendBody("\n\n");
					$this->appendBody($this->getLanguageText('grp_mail_permanent_link'));
					$this->appendBody("\n\n");
					$this->appendBody($this->createPermanentLink());

					$this->getMail()->appendInstallationSignature(true);
										
					$this->sendMail(array($rcp),array('system'));
				}
				break;

		}
		return true;
	}
	
	/**
	 * Add language module crs
	 * @param object $a_usr_id
	 * @return 
	 */
	protected function initLanguage($a_usr_id)
	{
		parent::initLanguage($a_usr_id);
		$this->getLanguage()->loadLanguageModule('grp');
// fau: fairSub - use also the 'common' lang module for mail notifications
		$this->getLanguage()->loadLanguageModule('common');
// fau.
	}
	
	/**
	 * Get course status body
	 * @param int $a_usr_id
	 * @return string
	 */
	protected function createGroupStatus($a_usr_id)
	{
		$part = ilGroupParticipants::_getInstanceByObjId($this->getObjId());
		
		$body = $this->getLanguageText('grp_new_status')."\n";
		$body .= $this->getLanguageText('role').': ';
		
		
		if($part->isAdmin($a_usr_id))
		{
			$body .= $this->getLanguageText('il_grp_admin')."\n";
			
		}
		else
		{
			$body .= $this->getLanguageText('il_grp_member')."\n";
		}

		if($part->isAdmin($a_usr_id))
		{
			$body .= $this->getLanguageText('grp_notification').': ';
			
			if($part->isNotificationEnabled($a_usr_id))
			{
				$body .= $this->getLanguageText('grp_notify_on')."\n";
			}
			else
			{
				$body .= $this->getLanguageText('grp_notify_off')."\n";
			}
		}
		return $body;
	}

	/**
	 * get setting "mail_grp_member_notification" and excludes types which are not affected by this setting
	 * See description of $this->permanent_enabled_notifications
	 *
	 * @param int $a_type
	 * @return bool
	 */
	protected function isNotificationTypeEnabled($a_type)
	{
		global $ilSetting;

		return 
			$this->force_sending_mail ||
			$ilSetting->get('mail_grp_member_notification',true) || 
			in_array($a_type, $this->permanent_enabled_notifications);
	}
}
?>