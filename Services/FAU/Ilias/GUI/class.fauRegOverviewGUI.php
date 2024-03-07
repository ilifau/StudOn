<?php

use FAU\BaseGUI;
use ILIAS\UI\Component\Input\Container\Filter\Standard;
use ILIAS\UI\Component\Item\Group;
use FAU\Study\Data\ImportId;

/**
 * Overview of course and group registrations
 *
 * @ilCtrl_Calls fauRegOverviewGUI: ilPropertyFormGUI, ilObjRootFolderGUI
 */
class fauRegOverviewGUI extends BaseGUI
{
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute a command
     */
    public function executeCommand()
    {
        $this->tpl->loadStandardTemplate();
        $this->tpl->setTitle($this->lng->txt('fau_reg_overview'));
        $this->tpl->setTitleIcon(ilUtil::getImagePath('icon_cal.svg'));

        $cmd = $this->ctrl->getCmd('show');
        $next_class = $this->ctrl->getNextClass();

        switch ($next_class) {
            default:
                switch ($cmd)
                {
                    case 'show':
                    case 'applyFilter':
                        $this->$cmd();
                        break;

                    default:
                        $this->tpl->setContent('unknown command: ' . $cmd);
                }
        }

        $this->tpl->printToStdout();
    }
    
    
    protected function show()
    {
        $tpl = new ilTemplate("tpl.reg_overview.html",true,true,"Services/FAU/Ilias/GUI");

        $tpl->setVariable('INFO', $this->lng->txt('fau_reg_overview_info'));
        $tpl->setVariable('LIST_HTML', $this->dic->ui()->renderer()->render($this->getLists(false)));
        $this->tpl->setContent($tpl->get());
    }
    
    
    /**
     * Get the list of objects to be shown, grouped by today and weeks
     * 
     * @return Group[]
     */
    protected function getLists(bool $by_end) : array
    {
        // $this->lng->loadLanguageModule('trac');
        ilDatePresentation::setUseRelativeDates(false);

        /** @var  ilCalendarUserSettings $setting */
        $settings = ilCalendarUserSettings::_getInstanceByUserId($this->dic->user()->getId());
        $tz = $settings->getTimeZone();
        $start = (new ilDateTime(time(), IL_CAL_UNIX))->get(IL_CAL_DATE, $tz) . ' 00:00:00';;
        
        $day_start = new ilDateTime($start, IL_CAL_DATETIME, $tz);
        
        $day_end = clone $day_start;
        $day_end->increment(ilDateTime::DAY, 1);
        $day_end->increment(ilDateTime::SECOND, -1);

        $start_info = $day_start->get(IL_CAL_FKT_GETDATE, '', $tz);
        $day_diff = ($settings->getWeekStart()) - $start_info['isoday'];
        $day_diff = ($day_diff == 7) ? 0 : $day_diff;
 
        $week_start = clone $day_start;
        $week_start->increment(IL_CAL_DAY, $day_diff);

        $next_start = clone($week_start);
        $next_start->increment(IL_CAL_WEEK, 1);
        
        $week_end = clone $next_start;
        $week_end->increment(ilDateTime::SECOND, -1);

        $later = clone($next_start);
        $later->increment(IL_CAL_WEEK, 1);
        
        $next_end = clone $later;
        $next_end->increment(ilDateTime::SECOND, -1);

//        echo '<br>$day_start: ' . $day_start->get(IL_CAL_DATETIME, ' ', $tz);
//        echo '<br>$day_end: ' . $day_end->get(IL_CAL_DATETIME, ' ', $tz);
//        echo '<br>$week_start: ' . $week_start->get(IL_CAL_DATETIME, ' ', $tz);
//        echo '<br>$week_end: ' . $week_end->get(IL_CAL_DATETIME, ' ', $tz);
//        echo '<br>$next_start: ' . $next_start->get(IL_CAL_DATETIME, ' ', $tz);
//        echo '<br>$next_end: ' . $next_end->get(IL_CAL_DATETIME, ' ', $tz);
//        exit;
        
        $icon_crs = $this->factory->symbol()->icon()->standard('crs', $this->lng->txt('obj_crs'), 'medium');
        $icon_grp = $this->factory->symbol()->icon()->standard('grp', $this->lng->txt('obj_grp'), 'medium');
        $info_gui = $this->dic->fau()->study()->info();
        
        $groups = [
          'reg_overview_today' => [],
          'reg_overview_this_week' => [],
          'reg_overview_next_week' => [],
          'reg_overview_later' => [],
          'reg_overview_other' => []
        ];
        
        foreach ($this->dic->fau()->ilias()->objects()->getRegistrationsOverviewItems(false) as $info) {
            
            switch ($info->getWaitingStatus()) {
                case ilWaitingList::REQUEST_NOT_TO_CONFIRM:
                    $status = $this->lng->txt('on_waiting_list');
                    break;
                case ilWaitingList::REQUEST_TO_CONFIRM:
                    $status = $this->lng->txt('sub_status_pending');
                    break;
                case ilWaitingList::REQUEST_CONFIRMED:
                    $status = $this->lng->txt('sub_status_confirmed');
                    break;
                default:
                    $status =  $this->lng->txt('sub_status_not_registered');
            }
            
            $props = [
              $this->lng->txt('crs_list_reg_period') => ilDatePresentation::formatPeriod($info->getRegStart(), $info->getRegEnd()),
              $this->lng->txt('status') => $status
            ];

            $link = ilLink::_getStaticLink($info->getRefId(), $info->getType());
            $item = $this->factory->item()->standard('<a href="' . $link . '">'.$info->getTitle().'</a>')
                ->withDescription($info->getDescription() 
                    . $info_gui->getLinksLine(ImportId::fromString($info->getImportId()), $info->getRefId()))
                ->withLeadIcon($info->getType() == 'crs' ? $icon_crs : $icon_grp)
                ->withProperties($props);
            
            $date = $by_end ? $info->getRegEnd() : $info->getRegStart();
            if (ilDate::_within($date, $day_start, $day_end)) {
                $groups['reg_overview_today'][] = $item;
            }
            elseif (ilDate::_within($date, $week_start, $week_end)) {
                $groups['reg_overview_this_week'][] = $item;
            }
            elseif (ilDate::_within($date, $next_start, $next_end)) {
                $groups['reg_overview_next_week'][] = $item;
            }
            elseif (ilDate::_after($date, $next_end)) {
                $groups['reg_overview_later'][] = $item;
            }
            else {
                $groups['reg_overview_other'][] = $item;
            }
        }

        $lists = [];
        foreach ($groups as $langvar => $items) {
            if (!empty($items)) {
                $lists[] =  $this->factory->item()->group($this->lng->txt($langvar), $items);
            }
        }
        return $lists;
    }
}