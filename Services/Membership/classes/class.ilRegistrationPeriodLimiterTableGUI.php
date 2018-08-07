<?php

/**
 * fim
 * Institut für Lern-Innovation
 * Friedrich-Alexander-Universität
 * Erlangen-Nürnberg
 * Germany
 * Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE
 */
require_once("./Services/Table/classes/class.ilTable2GUI.php");

/**
 * Table class for Registration period limiter.
 *
 * @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
 * @author Jesus Copado <jesus.copado@fim.uni-erlangen.de>
 *
 * $Id$
 */
class ilRegistrationPeriodLimiterTableGUI extends ilTable2GUI
{

	private $admin = false;
	private $show_period = false;

	/**
	 * contruct
	 */
	public function __construct($a_parent_obj, $a_parent_cmd)
	{
		parent::__construct($a_parent_obj, $a_parent_cmd);
	}

	public function init($timestamp, $show_period)
	{
		global $lng, $rbacsystem;

		//Set admin mode or not
		if ($rbacsystem->checkAccess("visible,read", SYSTEM_FOLDER_ID))
		{
			$this->admin = true;
			$this->show_period = $show_period;
		}

		if ($this->show_period == true AND $this->admin == true)
		{
			$lng->loadLanguageModule('crs');
			$this->addColumn($lng->txt('crs_start'), "", "15%");
			$this->addColumn($lng->txt('type'), "", "5%");
			$this->addColumn($lng->txt('title'), "", "30%");
			$this->addColumn($lng->txt('crs_subscription_type'), "", "15%");
			$this->addColumn($lng->txt('crs_members'), "", "5%");
			$this->addColumn($lng->txt('sub_fair_date'), "", "10%");
			$this->addColumn($lng->txt('crs_end'), "", "15%");

			$this->setNoEntriesText($lng->txt("msg_no_search_result"));
			$this->setEnableHeader(true);

			$this->setRowTemplate("tpl.rpl_period_row.html", "Services/Membership");
		}
		else
		{
			$this->addColumn($lng->txt('hours'), "", "10%");
			$this->addColumn($lng->txt('Mo_long') . "<br />" . date("d.m.Y", $timestamp), "", "10%");
			$this->addColumn($lng->txt('Tu_long') . "<br />" . date("d.m.Y", $timestamp + 86400), "", "10%");
			$this->addColumn($lng->txt('We_long') . "<br />" . date("d.m.Y", $timestamp + 172800), "", "10%");
			$this->addColumn($lng->txt('Th_long') . "<br />" . date("d.m.Y", $timestamp + 259200), "", "10%");
			$this->addColumn($lng->txt('Fr_long') . "<br />" . date("d.m.Y", $timestamp + 345600), "", "10%");
			$this->addColumn($lng->txt('Sa_long') . "<br />" . date("d.m.Y", $timestamp + 432000), "", "10%");
			$this->addColumn($lng->txt('Su_long') . "<br />" . date("d.m.Y", $timestamp + 581400), "", "10%");

			$this->setNoEntriesText($lng->txt("rpl_no_entries"));
			$this->setEnableHeader(true);

			//Show template if admin view with links or not
			if ($this->show_period == false AND $this->admin == true)
			{
				$this->setRowTemplate("tpl.rpl_links_row.html", "Services/Membership");
			}
			else
			{
				$this->setRowTemplate("tpl.rpl_numbers_row.html", "Services/Membership");
			}
		}

		$this->cat1 = (int) ilCust::get('rpl_warning_cat_1');
		$this->cat2 = (int) ilCust::get('rpl_warning_cat_2');
		$this->cat3 = (int) ilCust::get('rpl_warning_cat_3');
	}

	public function getContent($timestamp)
	{
		require_once './Services/Membership/classes/class.ilRegistrationPeriodLimiter.php';
		if ($this->show_period == true AND $this->admin == true)
		{
			$array_of_data = ilRegistrationPeriodLimiter::_getPeriodViewData($timestamp);
			$this->setDefaultOrderDirection("asc");
			$this->setData($array_of_data);
		}
		else
		{
			$array_of_data = ilRegistrationPeriodLimiter::_getTableViewData($timestamp);
			$this->setDefaultOrderDirection("asc");
			$this->setData($array_of_data);
		}
	}

	/**
	 * Fill a single data row.
	 */
	protected function fillRow($data)
	{
		global $ilCtrl;
		
		//Show period view
		if ($this->show_period == true AND $this->admin == true)
		{
			//Registration starting hour 
			$this->tpl->setVariable("START", $data["start"]);
			//Object Type (Course or group)
			$this->tpl->setVariable("TYPE", $data["type"]);
			//Title of the course or group
			$this->tpl->setVariable("TITLE", $data["title"]);
			//Link to the course or group
			$this->tpl->setVariable("LINK", $data["link"]);
			// Registration Type
			$this->tpl->setVariable("REG_TYPE", $data["reg_type"]);
			//Number of offered places
			$this->tpl->setVariable("PLACES", $data["places"]);
			//Policy for exceeded places
			$this->tpl->setVariable("POLICY", $data["policy"]);
			// Registration period end
			$this->tpl->setVariable("END", $data["end"]);


            $root_id = ilCust::get("ilias_repository_cat_id");
            $root_id = $root_id ? $root_id : ROOT_FOLDER_ID;
            require_once("Services/Tree/classes/class.ilPathGUI.php");
            $pathGUI = new ilPathGUI();
            $pathGUI->enableTextOnly(false);
            $this->tpl->setVariable("PATH", $pathGUI->getPath($root_id, $data["ref_id"]));
		}
		else
		{
			//Show admin view
			//Hour
			$this->tpl->setVariable("TXT_HOUR", $data[0]);
			
			for ($day = 1; $day <= 7; $day++)
			{
				// Number of places offered
				$this->tpl->setVariable("TXT_DAY_".$day, $data[$day]["members"] > 0 ? $data[$day]["members"] : '');

				// Background color
				$this->tpl->setVariable("BACKGROUND_".$day, $this->chooseBackground($data[$day]["members"]));

				if ($this->show_period == false AND $this->admin == true)
				{
					// Link to show courses and groups
					$ilCtrl->setParameter($this->parent_obj, 'period', $data[$day]['period']);
					$this->tpl->setVariable("TXT_LINK_".$day, $ilCtrl->getLinkTarget($this->parent_obj, 'showPeriod'));
				}	
			}
		}
	}

	protected function chooseBackground($users)
	{
		if ($users > $this->cat1)
		{
			return "#FF5252";
		}
		elseif ($users > $this->cat2)
		{
			return "#FFAD5C";
		}
		elseif ($users > $this->cat3)
		{
			return "#EDF983";
		}
		elseif ($users > 0)
		{
			return "#FFFFCC";
		}
		else
		{
			return "#D2F0CA";
		}
	}

}

?>
