<?php

use FAU\BaseGUI;
use ILIAS\UI\Component\Input\Container\Filter\Standard;
use ILIAS\UI\Component\Item\Group;
use FAU\Study\Data\ImportId;
use FAU\Ilias\Data\ListProperty;

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

        $target = $this->dic->http()->request()->getRequestTarget();
        $params = $this->dic->http()->request()->getQueryParams();
        if (($params['sort'] ?? 'start') == 'start') {
            $active = 'fau_reg_sort_by_start';
        } else {
            $active = 'fau_reg_sort_by_end';
        }
        
        $actions = [
            $this->lng->txt('fau_reg_sort_by_start') => "$target&sort=start",
            $this->lng->txt('fau_reg_sort_by_end') => "$target&sort=end",
        ];

        $aria_label = 'fau_reg_sort_label';
        $sort_control = $this->factory->viewControl()->mode($actions, $aria_label)->withActive($this->lng->txt($active));

        $tpl->setVariable('SORT_HTML', $this->dic->ui()->renderer()->render($sort_control));

        $tpl->setVariable('LIST_HTML', $this->dic->ui()->renderer()->render($this->getLists($active == 'fau_reg_sort_by_end')));
        $this->tpl->setContent($tpl->get());
    }
    
    
    /**
     * Get the list of objects to be shown, grouped by today and weeks
     * 
     * @return Group[]
     */
    protected function getLists(bool $by_end) : array
    {
        $this->lng->loadLanguageModule('crs');
        ilDatePresentation::setUseRelativeDates(false);

        /** @var  ilCalendarUserSettings $setting */
        $settings = ilCalendarUserSettings::_getInstanceByUserId($this->dic->user()->getId());
        $tz = $settings->getTimeZone();
        
        $start = (new ilDateTime(time(), IL_CAL_UNIX))->get(IL_CAL_DATE, $tz) . ' 00:00:00';
        $day_start = new ilDateTime($start, IL_CAL_DATETIME, $tz);

        $start_info = $day_start->get(IL_CAL_FKT_GETDATE, '', $tz);
        $day_diff = ($settings->getWeekStart()) - $start_info['isoday'];
        $day_diff = ($day_diff == 7) ? 0 : $day_diff;
        
        $week_start = clone $day_start;
        $week_start->increment(IL_CAL_DAY, $day_diff);
        $next_start = clone $week_start;
        $next_start->increment(IL_CAL_WEEK, 1);
        $later = clone $next_start;
        $later->increment(IL_CAL_WEEK, 1);
        
        $day_end = clone $day_start;
        $day_end->increment(ilDateTime::DAY, 1);
        $day_end->increment(ilDateTime::SECOND, -1);
        $week_end = clone $next_start;
        $week_end->increment(ilDateTime::SECOND, -1);
        $next_end = clone $later;
        $next_end->increment(ilDateTime::SECOND, -1);

//        echo '<br>$day_start: ' . $day_start->get(IL_CAL_DATETIME, ' ', $tz);
//        echo '<br>$day_end: ' . $day_end->get(IL_CAL_DATETIME, ' ', $tz);
//        echo '<br>$week_start: ' . $week_start->get(IL_CAL_DATETIME, ' ', $tz);
//        echo '<br>$week_end: ' . $week_end->get(IL_CAL_DATETIME, ' ', $tz);
//        echo '<br>$next_start: ' . $next_start->get(IL_CAL_DATETIME, ' ', $tz);
//        echo '<br>$next_end: ' . $next_end->get(IL_CAL_DATETIME, ' ', $tz);
//        exit;

        $groups = [
            'fau_reg_overview_today' => [],
            'fau_reg_overview_this_week' => [],
            'fau_reg_overview_next_week' => [],
            'fau_reg_overview_later' => [],
            'fau_reg_overview_other' => []
        ];

        $icon_crs = $this->factory->symbol()->icon()->standard('crs', $this->lng->txt('obj_crs'), 'medium');
        $icon_grp = $this->factory->symbol()->icon()->standard('grp', $this->lng->txt('obj_grp'), 'medium');
        
        foreach ($this->dic->fau()->ilias()->objects()->getRegistrationsOverviewInfos(false) as $info) {
            
            
            $props = [];
            // registration period
            if (!$info->getRegEnabled()) {
                $props[$this->lng->txt('crs_list_reg_period')] = $this->lng->txt('crs_list_reg_noreg');
            }
            elseif (!$info->hasTimeLimit()) {
                $props[$this->lng->txt('crs_list_reg_period')] = $this->lng->txt('crs_unlimited');
            }
            else {
                $props[$this->lng->txt('crs_list_reg_period')] = ilDatePresentation::formatPeriod($info->getRegStart(), $info->getRegEnd());
            }
            // own status            
            if (!empty($property = $info->getPropertyByKey(ListProperty::KEY_STATUS))) {
                $props[$this->lng->txt('status')] = $property->getValue();
            }
            else {
                $props[$this->lng->txt('status')] = $this->lng->txt('sub_status_not_registered');
            }
            // limits           
            if (!empty($property = $info->getPropertyByKey(ListProperty::KEY_LIMITS))) {
                $props[''] = $property->getValue();
            }
            
            $link = ilLink::_getStaticLink($info->getRefId(), $info->getType());
            $item = $this->factory->item()->standard('<a href="' . $link . '">'.$info->getTitle().'</a>')
                ->withLeadIcon($info->getType() == 'crs' ? $icon_crs : $icon_grp)
                ->withProperties($props);
            
            $date = $by_end ? $info->getRegEnd() : $info->getRegStart();
            if (!$info->hasTimeLimit()) {
                $groups['fau_reg_overview_other'][] = $item;
            }
            elseif (ilDate::_within($date, $day_start, $day_end)) {
                $groups['fau_reg_overview_today'][] = $item;
            }
            elseif (ilDate::_within($date, $week_start, $week_end)) {
                $groups['fau_reg_overview_this_week'][] = $item;
            }
            elseif (ilDate::_within($date, $next_start, $next_end)) {
                $groups['fau_reg_overview_next_week'][] = $item;
            }
            elseif (ilDate::_after($date, $next_end)) {
                $groups['fau_reg_overview_later'][] = $item;
            }
            elseif ($info->isOnWaitingList() || ilDate::_after($info->getRegEnd(), $week_start)) {
                $groups['fau_reg_overview_other'][] = $item;
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