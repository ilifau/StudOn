<?php

/**
 * fim
 * Institut für Lern-Innovation
 * Friedrich-Alexander-Universität
 * Erlangen-Nürnberg
 * Germany
 * Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE
 */

/**
 * Registration period limiter GUI class.
 *
 * @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
 * @author Jesus Copado <jesus.copado@fim.uni-erlangen.de>
 *
 * @version $Id$
 * 
 */
class ilRegistrationPeriodLimiterGUI
{

	public function __construct()
	{
		global $lng;
		$lng->loadLanguageModule('dateplaner');
	}

	/**
	 * Execute Command
	 * @access public
	 */
	public function executeCommand()
	{
		global $ilCtrl, $ilUser;

		if ($ilUser->getId() == ANONYMOUS_USER_ID)
		{
			ilUtil::redirect("login.php?target=studon_regstarts&cmd=force_login&lang=" . $ilUser->getCurrentLanguage());
		}

		$cmd = $ilCtrl->getCmd();
		switch ($cmd)
		{
			case "showPeriod":
				$this->showPeriod();
				break;
			default:
				$this->showCalendar();
				break;
		}
	}

	private function showCalendar()
	{
		global $ilCtrl, $lng, $tpl;

		// set the starting time 
		$time = $_GET['time'] ? (int) $_GET['time'] : time();

		//echo date("Y-m-d H:i:s", $time);
		if (date('w', $time) == 1)
		{
			$time = strtotime('today', $time);
		}
		else
		{
			$time = strtotime('last Monday', $time);
		}
		//echo "<br />". date("Y-m-d H:i:s", $time);
		                           
		// generate the table
		require_once './Services/Membership/classes/class.ilRegistrationPeriodLimiterTableGUI.php';
		require_once './Services/Membership/classes/class.ilRegistrationPeriodLimiter.php';
		$table = new ilRegistrationPeriodLimiterTableGUI($this, "show");
		$table->init($time, false);
		$table->getContent($time, $this);

		// add navigation buttons
		require_once "Services/UIComponent/Toolbar/classes/class.ilToolbarGUI.php";
		$toolbar = new ilToolbarGUI;
		$ilCtrl->setParameter($this, 'time', strtotime('-1 week', $time));
		$toolbar->addButton($lng->txt('rpl_previous_week'), $ilCtrl->getLinkTarget($this));
		$ilCtrl->setParameter($this, 'time', strtotime('+1 week', $time));
		$toolbar->addButton($lng->txt('rpl_next_week'), $ilCtrl->getLinkTarget($this));

		// fill the main template
		$ilCtrl->setParameter($this, 'time', $time);
		$tpl->getStandardTemplate();
		$tpl->setTitle($lng->txt('rpl_overview'));
		$tpl->setTitleIcon(ilUtil::getImagePath('icon_cal.svg'));
		$tpl->setDescription($lng->txt('rpl_overview_desc'));
		$tpl->setContent($toolbar->getHtml() . $table->getHTML());
		$tpl->show();
	}

	private function showPeriod()
	{
		global $ilCtrl, $lng, $tpl;
		
		$period = (int) $_GET['period'];
		$time = (int) $_GET['time'];
		$ilCtrl->setParameter($this, 'time', $time);

		// generate the period table
		require_once './Services/Membership/classes/class.ilRegistrationPeriodLimiterTableGUI.php';
		require_once './Services/Membership/classes/class.ilRegistrationPeriodLimiter.php';
		$table = new ilRegistrationPeriodLimiterTableGUI($this, "showPeriod");
		//Second parameter is true, to show just a period
		$table->init($period, true);
		$table->getContent($period, $this);

		// add navigation buttons
		require_once "Services/UIComponent/Toolbar/classes/class.ilToolbarGUI.php";
		$toolbar = new ilToolbarGUI;
		$toolbar->addButton($lng->txt('btn_back'), $ilCtrl->getLinkTarget($this));

		// fill the main template
		$tpl->getStandardTemplate();
		$tpl->setTitle($lng->txt('rpl_overview'));
		$tpl->setTitleIcon(ilUtil::getImagePath('icon_cal.svg'));
		$tpl->setDescription($lng->txt('rpl_overview_desc'));
		$tpl->setContent($toolbar->getHtml() . $table->getHTML());
		$tpl->show();
	}
}

?>
