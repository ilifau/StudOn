<?php

namespace FAU\Study;

use ILIAS\DI\Container;
use FAU\Study\Data\Term;

/**
 * Class to manage the creation and update of StudOn Courses and Groups
 *
 * The relation of campo events and courses to ilias and groups is given by the property ilias_ref_id
 * Campo events and courses that need an update of the related course or group are marked with ilias_dirty_since
 */
class CourseManager
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
     * Create the courses of a term
     * @return int number of created courses
     */
    public function createCourses(Term $term) : int
    {
        return 0;
    }


    /**
     * Update the courses of a term
     * This should also treat the event related courses
     * @return int number of updated courses
     */
    public function updateCourses(Term $term) : int
    {
        return 0;
    }


}