<?php
/* fim: [memlot] new class. */

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once('./Services/Table/classes/class.ilTable2GUI.php');
include_once './Services/StudyData/classes/class.ilStudyData.php';
include_once('./Services/Membership/classes/class.ilSubscribersLot.php');

/**
* table for showing lit lost
* 
* @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
* @version $Id$
*
* @ingroup ServicesMembership 
*/
class ilSubscribersLotTableGUI extends ilTable2GUI
{
	protected $participants = null;
	protected $subscribers = array();
	
	protected $lot_obj = null;
	
	
	/**
	 * Constructor
	 *
	 * @access public
	 * @param
	 * @return
	 */
	public function __construct($a_parent_obj ,$participants, $show_content = true)
	{
	 	global $lng,$ilCtrl;
	 	
	 	$this->lng = $lng;
		$this->lng->loadLanguageModule('grp');
		$this->lng->loadLanguageModule('crs');
	 	$this->ctrl = $ilCtrl;
	 	
	 	$this->parent_gui = $a_parent_obj;
	 	
		parent::__construct($a_parent_obj,'members');

		$this->setFormName('subscribers_lot');
		$this->setFormAction($this->ctrl->getFormAction($a_parent_obj,'members'));

	 	$this->addColumn('','f',"1");
	 	$this->addColumn($this->lng->txt('lastname'),'name','20%');
	 	$this->addColumn($this->lng->txt('login'),'login','20%');
	 	$this->addColumn($this->lng->txt('studydata'),'studydata',"40%");
		$this->addColumn('','mail','20%');
		
		$this->addMultiCommand('removeSubscribersFromLot',$this->lng->txt('mem_remove_from_lot'));
		$this->addMultiCommand('sendMailToSelectedUsers',$this->lng->txt('crs_mem_send_mail'));
		

		$this->setPrefix('subscribers_lot');
		$this->setSelectAllCheckbox('subscribers_lot');
		$this->setRowTemplate("tpl.show_subscribers_lot_row.html","Services/Membership");
		
		if($show_content)
		{
			$this->enable('sort');
			$this->enable('header');
			$this->enable('numinfo');
			$this->enable('select_all');
		}
		else
		{
			$this->disable('content');
			$this->disable('header');
			$this->disable('footer');
			$this->disable('numinfo');
			$this->disable('select_all');
		}	
		
		$this->participants = $participants;
		
		$this->lot_obj = new ilSubscribersLot($a_parent_obj->object->getId());
		$this->lot_obj->setParticipantsObject($this->participants);
		$this->lot_obj->setEnabled($a_parent_obj->object->enabledLotList());
		
		switch ($a_parent_obj->object->getType())
		{
			case 'crs':
				$this->lot_obj->setMaxMembers($a_parent_obj->object->getSubscriptionMaxMembers());
				$this->lot_obj->setMinTime($a_parent_obj->object->getSubscriptionEnd());
				break;
				
			case 'grp':
				$this->lot_obj->setMaxMembers($a_parent_obj->object->getMaxMembers());
				$this->lot_obj->setMinTime($a_parent_obj->object->getRegistrationEnd()->get(IL_CAL_UNIX));
				break;
		}

		$this->addCommandButton("cleanLotList", $lng->txt("mem_lot_clean_button"));
		if ($this->lot_obj->checkStatus())
		{
			$this->addCommandButton("assignMembersByLot", $lng->txt("mem_lot_button"));
		}
		
		$this->setSubscribers($this->lot_obj->getUserIDs());
		$this->setTitle($this->lng->txt('mem_lot_candidates'),'icon_usr.gif',$this->lng->txt('mem_lot_candidates'));		
		$this->setDescription(sprintf($lng->txt('mem_lot_table_description'),
										$this->lot_obj->getFreePlaces(), 
										$this->lot_obj->getCountUsers(),
										$this->lot_obj->getStatusMessage()));	
	}
	
	/**
	 * set subscribers
	 *
	 * @access public
	 * @param
	 * @return
	 */
	public function setSubscribers($a_sub)
	{
		$this->subscribers = $a_sub;
		$this->readSubscriberData();
	}
	
	/**
	 * fill row 
	 *
	 * @access public
	 * @param
	 * @return
	 */
	public function fillRow($a_set)
	{
		global $ilUser;
		
				
		include_once './Modules/Course/classes/class.ilObjCourseGrouping.php';
		if(!ilObjCourseGrouping::_checkGroupingDependencies($this->getParentObject()->object,$a_set['id']) and
			($ids = ilObjCourseGrouping::getAssignedObjects()))
		{
			$prefix = $this->getParentObject()->object->getType();
			$this->tpl->setVariable('ALERT_MSG',
				sprintf($this->lng->txt($prefix.'_lim_assigned'),
				ilObject::_lookupTitle(current($ids))
				));
				
		}

		$this->tpl->setVariable('VAL_ID',$a_set['id']);
		$this->tpl->setVariable('VAL_NAME',$a_set['name']);
		$this->tpl->setVariable('VAL_LOGIN',$a_set['login']);
		
		$this->ctrl->setParameterByClass(get_class($this->getParentObject()),'member_id',$a_set['id']);
		$link = $this->ctrl->getLinkTargetByClass(get_class($this->getParentObject()),'sendMailToSelectedUsers');
		$this->tpl->setVariable('MAIL_LINK',$link);
		$this->tpl->setVariable('MAIL_TITLE',$this->lng->txt('crs_mem_send_mail'));
		
		$studydata .= ilStudyData::_getStudyDataVisibility() ?
			nl2br(ilStudyData::_getStudyDataText($a_set['id'])) : "";
		$this->tpl->setVariable('STUDYDATA',$studydata);
	}
	
	/**
	 * read data
	 *
	 * @access protected
	 * @param
	 * @return
	 */
	public function readSubscriberData()
	{
		foreach($this->subscribers as $usr_id)
		{
			
			$data = $this->participants->getSubscriberData($usr_id);
			
			$tmp_arr['id'] = $usr_id;
			$tmp_arr['sub_time'] = $data['time'];
			$tmp_arr['subject'] = $data['subject'];
			
			$name = ilObjUser::_lookupName($usr_id);
			$tmp_arr['name'] = $name['lastname'].', '.$name['firstname'];
			$tmp_arr['login'] = ''.ilObjUser::_lookupLogin($usr_id).'';
			
			$subscribers[] = $tmp_arr;
		}
		$this->setData($subscribers ? $subscribers : array());
	}
	
}
?>
