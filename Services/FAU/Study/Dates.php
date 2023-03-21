<?php

namespace FAU\Study;

use DateTimeImmutable;
use ILIAS\DI\Container;

class Dates
{
    protected Container $dic;
    protected Service $service;
    protected Repository $repo;

    protected $timezone;

    protected static $weekdays = array(
        0 => "Su_short",
        1 => "Mo_short",
        2 => "Tu_short",
        3 => "We_short",
        4 => "Th_short",
        5 => "Fr_short",
        6 => "Sa_short"
    );


    /**
     * Constructor
     */
    public function __construct(Container $dic)
    {
        $this->dic = $dic;
        $this->service = $dic->fau()->study();
        $this->repo = $dic->fau()->study()->repo();

        $this->timezone = $this->dic->user()->getTimeZone();
        $this->dic->language()->loadLanguageModule('dateplaner');
    }


    /**
     * Get the list of planned datees for a course
     */
    public function getPlannedDatesList(int $course_id, bool $html = true) : string
    {
        $list = [];
        foreach ($this->repo->getPlannedDatesOfCourse($course_id) as $date) {
            $parts = [];
            if (!empty($text = $this->getRhythmText((string) $date->getRhythm()))) {
                $parts[] = $text;
            }
            $timeparts = [];
            if (!empty($date->getStartdate())) {
                $timeparts[] = $this->getWeekday($date->getStartdate());
            }
            if (!empty($date->getStarttime())) {
                $timeparts[] = $this->getTimespan($date->getStarttime(), $date->getEndtime());
            }
            if (!empty($date->getAcademicTime())) {
                $timeparts[] = $date->getAcademicTime();
            }
            $time = implode(' ', $timeparts);
            if (!empty($time)) {
                $parts[] = $time;
            }
            if (!empty($date->getStartdate())) {
                $parts[] = $this->getDatespan($date->getStartdate(), $date->getEnddate());
            }

            if (!empty($parts)) {
                $list[] = implode(', ', $parts);
            }
        }

        if ($html) {
            return $this->dic->fau()->tools()->format()->list($list, $html, true);
        }
        else {
            return implode(' | ', $list);
        }
    }


    /**
     * Get the list of individual dates of a course
     */
    public function getIndividualDatesList(int $course_id, bool $html = true) : string
    {
        $list = [];
        foreach($this->repo->getIndividualDatesOfCourse($course_id) as $date) {
            $parts = [];
            if (!empty($date->getDate())) {
                $parts[] = $this->getWeekday($date->getDate())
                . ' ' . $this->getDatespan($date->getDate(), null);
            }
            if (!empty($date->getStarttime())) {
                $parts[] = $this->getTimespan($date->getStarttime(), $date->getEndtime());
            }
            if (!empty($parts)) {
                $list[] = implode(', ', $parts);
            }
        }

        if ($html) {
            return $this->dic->fau()->tools()->format()->list($list, $html, true);
        }
        else {
            return implode(' | ', $list);
        }
    }


    /**
     * Get the (translated) text for a thythm
     */
    protected function getRhythmText(string $rhythm) : string
    {
        switch($rhythm) {
            case 'wöchentlich':
                return '';

            case '14-täglich':
            case 'Blockveranstaltung':
            case 'Blockveranstaltung+Sa':
            case 'Blockveranstaltung+SaundSo':
            case 'Einzeltermin':
            case 'nach Vereinbarung':
            case 'vierwöchentlich':
            default:
                return $rhythm;
        }
    }

    /**
     * Get the short weekday of a date like '2023-03-21'
     */
    protected function getWeekday(string $datestring) : string
    {
        $zone = new \DateTimeZone($this->timezone);
        $date = DateTimeImmutable::createFromFormat("Y-m-d", $datestring, $zone);
        return $this->dic->language()->txt(self::$weekdays[$date->format('w')]);
    }

    /**
     * Get the span of one or two times like '08:00:00'
     */
    protected function getTimespan(string $starttime, ?string $enddtime) : string
    {
        $span = substr($starttime, 0, 5);
        if (!empty($enddtime)) {
            $span .= ' - ' . substr($enddtime, 0, 5);
        }
        return $span;
    }

    /**
     * Get the span of one or two dates like '2023-03-21'
     */
    protected function getDatespan(string $startdate, ?string $enddate) : string
    {
        $relative = \ilDatePresentation::useRelativeDates();
        \ilDatePresentation::useRelativeDates(false);

        $start = new \ilDate($startdate, IL_CAL_DATE);
        if (!empty($enddate)) {
            $end = new \ilDate($enddate, IL_CAL_DATE);
            $span = \ilDatePresentation::formatPeriod($start, $end);
        }
        else {
            $span = \ilDatePresentation::formatDate($start);
        }

        \ilDatePresentation::useRelativeDates($relative);
        return $span;
    }


}