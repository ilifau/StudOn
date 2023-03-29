<?php

namespace FAU\Study;

use DateTimeImmutable;
use ILIAS\DI\Container;

class Persons
{
    protected Container $dic;
    protected Service $service;
    protected Repository $repo;


    /**
     * Constructor
     */
    public function __construct(Container $dic)
    {
        $this->dic = $dic;
        $this->service = $dic->fau()->study();
        $this->repo = $dic->fau()->study()->repo();
    }

    /**
     * Get the list of event and course responsibles
     * return string[]
     */
    public function getResponsiblesList(?int $event_id, ?int $course_id, bool $with_profile = true) : array
    {
        $event_resps = $this->repo->getEventResponsibles((int) $event_id);
        $course_resps = $this->repo->getCourseResponsibles((int) $course_id);
        return $this->getUserListOfPersons(array_unique(array_merge($event_resps, $course_resps)), $with_profile);
    }

    /**
     * Get the list of instructors for a planned date
     * return string[]
     */
    public function getInstructorsList(int $planned_dates_id, bool $with_profile = true) : array
    {
        $person_ids = $this->repo->getInstructors($planned_dates_id);
        return $this->getUserListOfPersons($person_ids, $with_profile);
    }

    /**
     * Get the list of instructors for an individual date
     * return string[]
     */
    public function getIndividualInstructorsList(int $individual_dates_id, bool $with_profile = true) : array
    {
        $person_ids = $this->repo->getIndividualInstructors($individual_dates_id);
        return $this->getUserListOfPersons($person_ids, $with_profile);
    }

    /**
     * Get the list of users for person_ids
     * @param int[] $person_ids
     * return string[]
     */
    public function getUserListOfPersons(array $person_ids, bool $with_profile = true) : array
    {
        $list = [];
        foreach ($this->dic->fau()->user()->getShortUserDataOfPersons($person_ids) as $userData) {
            $list[] = $this->dic->fau()->user()->getUserText($userData, $with_profile);
        }
        if (!$with_profile) {
            $list = array_unique($list);
        }
        return $list;
    }
}