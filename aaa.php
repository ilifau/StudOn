<?php
require_once("Services/Init/classes/class.ilInitialisation.php");
ilInitialisation::initILIAS();

global $DIC;

/** @var  ilCalendarUserSettings $setting */
$settings = ilCalendarUserSettings::_getInstanceByUserId($DIC->user()->getId());
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


echo '<br>$day_start: ' . $day_start->get(IL_CAL_DATETIME, ' ', $tz);
echo '<br>$day_end: ' . $day_end->get(IL_CAL_DATETIME, ' ', $tz);

echo '<br>$week_start: ' . $week_start->get(IL_CAL_DATETIME, ' ', $tz);
echo '<br>$week_end: ' . $week_end->get(IL_CAL_DATETIME, ' ', $tz);

echo '<br>$next_start: ' . $next_start->get(IL_CAL_DATETIME, ' ', $tz);
echo '<br>$next_end: ' . $next_end->get(IL_CAL_DATETIME, ' ', $tz);