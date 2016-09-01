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

/**
*
* @author Stefan Meyer <smeyer.ilias@gmx.de>
* @version $Id$
*
* @ingroup ModulesSession
*/

class ilSessionOverviewGUI
{
	protected $course_ref_id = null;
	protected $course_id = null;

	protected $lng;
	protected $tpl;
	protected $ctrl;

	/**
	 * constructor
	 *
	 * @access public
	 * @param
	 * @return
	 */
	public function __construct($a_crs_ref_id, ilParticipants $a_members)
	{
		global $tpl, $ilCtrl, $lng;
		
		$this->ctrl = $ilCtrl;
		$this->tpl = $tpl;
		$this->lng = $lng;
		$this->lng->loadLanguageModule('event');
		$this->lng->loadLanguageModule('crs');
		
		$this->course_ref_id = $a_crs_ref_id;
		$this->course_id = ilObject::_lookupObjId($this->course_ref_id);
		$this->members_obj = $a_members;
	}
	
	/**
	 * ecxecute command
	 *
	 * @access public
	 * @param
	 * @return
	 */
	public function executeCommand()
	{
		$next_class = $this->ctrl->getNextClass($this);
		$cmd = $this->ctrl->getCmd();
		
  		switch($next_class)
		{
			default:
				if(!$cmd)
				{
					$cmd = "listSessions";
				}
				$this->$cmd();
				break;
		}
	}
	/**
	 * list sessions of all user
	 *
	 * @access public
	 * @param
	 * @return
	 */
	public function listSessions()
	{
		global $ilErr,$ilAccess, $ilUser,$tree;

		if(!$ilAccess->checkAccess('write','',$this->course_ref_id))
		{
			$ilErr->raiseError($this->lng->txt('msg_no_perm_read'),$ilErr->MESSAGE);
		}
		
		$this->tpl->addBlockFile('ADM_CONTENT','adm_content','tpl.sess_list.html','Modules/Session');
		$this->tpl->addBlockfile("BUTTONS", "buttons", "tpl.buttons.html");

		// display button
		$this->tpl->setCurrentBlock("btn_cell");
		$this->tpl->setVariable("BTN_LINK",$this->ctrl->getLinkTarget($this,'exportCSV'));
		$this->tpl->setVariable("BTN_TXT",$this->lng->txt('event_csv_export'));
		$this->tpl->parseCurrentBlock();
				
		include_once 'Modules/Session/classes/class.ilEventParticipants.php';
		
		$this->tpl->addBlockfile("EVENTS_TABLE","events_table", "tpl.table.html");
		$this->tpl->addBlockfile('TBL_CONTENT','tbl_content','tpl.sess_list_row.html','Modules/Session');
		
		$members = $this->members_obj->getParticipants();
		$members = ilUtil::_sortIds($members,'usr_data','lastname','usr_id');		
		
		// Table
		//TODO: Use ilTable2GUI
		$tbl = new ilTableGUI();
		$tbl->setTitle($this->lng->txt("event_overview"),
					   '',
					   $this->lng->txt('obj_usr'));
		$this->ctrl->setParameter($this,'offset',(int) $_GET['offset']);	
		
		$events = array();
		foreach($tree->getSubtree($tree->getNodeData($this->course_ref_id),false,'sess') as $event_id)
		{
			$tmp_event = ilObjectFactory::getInstanceByRefId($event_id,false);
			if(!is_object($tmp_event) or !$ilAccess->checkAccess('write','',$event_id)) 
			{
				continue;
			}
			
			// fim: [memsess] prepare sort key for events
			$sort = $tmp_event->getFirstAppointment()->getStart()->get(IL_CAL_DATETIME);
			$sort.= $tmp_event->getTitle();
			$events[$sort] = $tmp_event;
			// fim.
		}
		
		// fim: [memsess] sort events by start date and title
		ksort($events);
		$events = array_values($events);
		// fim.

		$headerNames = array();
		$headerVars = array();
		$colWidth = array();
		
		$headerNames[] = $this->lng->txt('name');		
		$headerVars[] = "name";
		$colWidth[] = '20%';
		$headerNames[] = $this->lng->txt('login');
		$headerVars[] = "login";
		$colWidth[] = '20%';
					
		for ($i = 1; $i <= count($events); $i++)
		{
			$headerNames[] = $i;
			$headerVars[] = "event_".$i;
			$colWidth[] = 80/count($events)."%";	
		}		
		
		$this->tpl->setVariable("FORMACTION",$this->ctrl->getFormAction($this));
		$tbl->setHeaderNames($headerNames);
		$tbl->setHeaderVars($headerVars, $this->ctrl->getParameterArray($this,'listSessions'));
		$tbl->setColumnWidth($colWidth);		

		$tbl->setOrderColumn($_GET["sort_by"]);
		$tbl->setOrderDirection($_GET["sort_order"]);
		$tbl->setOffset($_GET["offset"]);				
		$tbl->setLimit($ilUser->getPref("hits_per_page"));
		$tbl->setMaxCount(count($members));
		$tbl->setFooter("tblfooter",$this->lng->txt("previous"),$this->lng->txt("next"));
		
		$sliced_users = array_slice($members,$_GET['offset'],$_SESSION['tbl_limit']);
		$tbl->disable('sort');
		$tbl->render();
		
		$counter = 0;
		foreach($sliced_users as $user_id)
		{			
			foreach($events as $event_obj)
			{								
				$this->tpl->setCurrentBlock("eventcols");
							
				$event_part = new ilEventParticipants($event_obj->getId());														
										
				// fim: [memsess] show different icons with additional icon for not registered
				if ($event_obj->enabledRegistration()
				and (!$event_part->hasParticipated($user_id))
				and (!$event_part->isRegistered($user_id)))
				{
					$this->tpl->setVariable("IMAGE_PARTICIPATED", ilUtil::getImagePath('scorm/not_attempted.svg'));
					$this->tpl->setVariable("PARTICIPATED", $this->lng->txt('event_not_registered'));
				}
				else
				{			
					$this->tpl->setVariable("IMAGE_PARTICIPATED", $event_part->hasParticipated($user_id) ? 
											ilUtil::getImagePath('scorm/passed.svg') :
											ilUtil::getImagePath('scorm/failed.svg'));
					
					$this->tpl->setVariable("PARTICIPATED", $event_part->hasParticipated($user_id) ?
										$this->lng->txt('event_participated') :
										$this->lng->txt('event_not_participated'));
				}						
				
				$this->tpl->parseCurrentBlock();				
			}			
			// fim.
			
			$this->tpl->setCurrentBlock("tbl_content");
			$name = ilObjUser::_lookupName($user_id);
			$this->tpl->setVariable("CSS_ROW",ilUtil::switchColor($counter++,'tblrow1','tblrow2'));
			$this->tpl->setVariable("LASTNAME",$name['lastname']);
			$this->tpl->setVariable("FIRSTNAME",$name['firstname']);
			$this->tpl->setVariable("LOGIN",ilObjUser::_lookupLogin($user_id));				
			$this->tpl->parseCurrentBlock();			
		}		
		
		$this->tpl->setVariable("HEAD_TXT_LEGEND", $this->lng->txt("legend"));		
		$this->tpl->setVariable("HEAD_TXT_DIGIT", $this->lng->txt("event_digit"));
		$this->tpl->setVariable("HEAD_TXT_EVENT", $this->lng->txt("event"));
		$this->tpl->setVariable("HEAD_TXT_LOCATION", $this->lng->txt("event_location"));
		$this->tpl->setVariable("HEAD_TXT_DATE_TIME",$this->lng->txt("event_date_time"));
		$i = 1;
		foreach($events as $event_obj)
		{
			$this->tpl->setCurrentBlock("legend_loop");
			$this->tpl->setVariable("LEGEND_CSS_ROW",ilUtil::switchColor($counter++,'tblrow1','tblrow2'));
			$this->tpl->setVariable("LEGEND_DIGIT", $i++);
			$this->tpl->setVariable("LEGEND_EVENT_TITLE", $event_obj->getTitle());
			$this->tpl->setVariable("LEGEND_EVENT_DESCRIPTION", $event_obj->getDescription());	
			$this->tpl->setVariable("LEGEND_EVENT_LOCATION", $event_obj->getLocation());
			$this->tpl->setVariable("LEGEND_EVENT_APPOINTMENT", $event_obj->getFirstAppointment()->appointmentToString());		
			$this->tpl->parseCurrentBlock();
		}
	
	    // fim: [memsess] add symbol legend
		$this->tpl->setCurrentBlock("symbol_legend");
		$this->tpl->setVariable("IMAGE_NOT_REGISTERED", ilUtil::getImagePath('scorm/not_attempted.svg'));
		$this->tpl->setVariable("IMAGE_NOT_PARTICIPATED", ilUtil::getImagePath('scorm/failed.svg'));
		$this->tpl->setVariable("IMAGE_PARTICIPATED", ilUtil::getImagePath('scorm/passed.svg'));

		$this->tpl->setVariable("NOT_REGISTERED", $this->lng->txt('event_not_registered'));
		$this->tpl->setVariable("NOT_PARTICIPATED", $this->lng->txt('event_not_participated'));
		$this->tpl->setVariable("PARTICIPATED", $this->lng->txt('event_participated'));
		$this->tpl->parseCurrentBlock();

	    // fim.
	}

	/**
	 * Events List CSV Export
	 *
	 * @access public
	 * @param
	 * 
	 */
	public function exportCSV()
	{
		global $tree,$ilAccess;
		
		include_once('Services/Utilities/classes/class.ilCSVWriter.php');
		include_once 'Modules/Session/classes/class.ilEventParticipants.php';
		
		$members = $this->members_obj->getParticipants();
		$members = ilUtil::_sortIds($members,'usr_data','lastname','usr_id');		

		$events = array();
		foreach($tree->getSubtree($tree->getNodeData($this->course_ref_id),false,'sess') as $event_id)
		{
			$tmp_event = ilObjectFactory::getInstanceByRefId($event_id,false);
			if(!is_object($tmp_event) or !$ilAccess->checkAccess('write','',$event_id)) 
			{
				continue;
			}
			// fim: [memsess] prepare sort key for events
			$sort = $tmp_event->getFirstAppointment()->getStart()->get(IL_CAL_DATETIME);
			$sort.= $tmp_event->getTitle();
			$events[$sort] = $tmp_event;
			// fim.
		}
		
		// fim: [memsess] sort events by start date and title
		ksort($events);
		$events = array_values($events);
		// fim.

		$this->csv = new ilCSVWriter();
		$this->csv->addColumn($this->lng->txt("lastname"));
		$this->csv->addColumn($this->lng->txt("firstname"));
		$this->csv->addColumn($this->lng->txt("login"));
		
		// fim: [memsess] temporary deactivate relative date presentation
		$relative = ilDatePresentation::useRelativeDates();
		ilDatePresentation::setUseRelativeDates(false);
		foreach($events as $event_obj)
		{			
			// TODO: do not export relative dates
			$this->csv->addColumn($event_obj->getTitle().' ('.$event_obj->getFirstAppointment()->appointmentToString().')');
		}
		ilDatePresentation::setUseRelativeDates($relative);
		// fim.

		
		$this->csv->addRow();
		
		foreach($members as $user_id)
		{
			$name = ilObjUser::_lookupName($user_id);
			
			$this->csv->addColumn($name['lastname']);
			$this->csv->addColumn($name['firstname']);
			$this->csv->addColumn(ilObjUser::_lookupLogin($user_id));
			
			foreach($events as $event_obj)
			{			
				$event_part = new ilEventParticipants((int) $event_obj->getId());
				
				// fim: [memsess] add registration info to CSV
				if ($event_obj->enabledRegistration()
				and (!$event_part->isRegistered($user_id))
				and (!$event_part->hasParticipated($user_id)))
				{
					$this->csv->addColumn($this->lng->txt('event_not_registered'));
				}
				else
				{
					$this->csv->addColumn($event_part->hasParticipated($user_id) ?
										$this->lng->txt('event_participated') :
										$this->lng->txt('event_not_participated'));
			}
				// fim.
			}
			
			$this->csv->addRow();
		}
		$date = new ilDate(time(),IL_CAL_UNIX);
		ilUtil::deliverData($this->csv->getCSVString(),$date->get(IL_CAL_FKT_DATE,'Y-m-d')."_course_events.csv", "text/csv");
	}
}
?>