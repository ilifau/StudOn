<?php  declare(strict_types=1);

namespace FAU\Study\Data;


/**
 * Enhancement of an event with data for a listing in the search result
 */
class EventFromSearch extends Event
{
    /** @var Course[] */
    protected array $courses;

    /**
     * Add a course to the list of courses
     */
    public function addCourse(Course $course)
    {
        $this->course[] = $course;
    }

    /**
     * Get the list of added courses
     * @return Course[]
     */
    public function getCourses() : array
    {
        return $this->courses;
    }
}