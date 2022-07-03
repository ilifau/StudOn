<?php

namespace FAU\Study\Data;

/**
 * Import Info stored in the import_id of an ILIAS object
 * This id gives references to the event, course and term
 *
 * Note: ILIAS import id has 50 chars, this works for event id and course id with max 10 digits
 */
class ImportId
{
    private ?string $term_id;
    private ?int $event_id;
    private ?int $course_id;

    public function __construct(?string $term_id = null, ?int $event_id = null, ?int $course_id = null)
    {
        $this->term_id = $term_id;
        $this->event_id = $event_id;
        $this->course_id = $course_id;
    }

    public static function fromObjects(?Term $term = null, ?Event $event = null, ?Course $course = null)  : self
    {
        return new self(
            isset($term) ? $term->toString() : null,
            isset($event) ? $event->getEventId() : null,
            isset($course) ? $course->getCourseId() : null,
        );
    }

    public static function fromString(?string $id = '') : self
    {
        $term_id = null;
        $event_id = null;
        $course_id = null;

        $parts = (array) explode('/', $id);
        if (isset($parts[0]) && $parts[0] == 'FAU') {
            foreach ($parts as $part) {
                list($key, $value) = array_pad(explode( '=', $part), 2, '');
                if (!empty($value)) {
                    switch ($key) {
                        case 'Term':
                            $term_id = (string) $value;
                            break;
                        case 'Event':
                            $event_id = (int) $value;
                            break;
                        case 'Course':
                            $course_id = (int) $value;
                            break;
                    }
                }
            }
        }
        return new self($term_id, $event_id, $course_id);
    }

    public function toString() : ?string
    {
        $id = "";
        // ad the term first - this allows a "LIKE" search by Term using an index on object_data.import_id
        if (isset($this->term_id)) {
            $id .= '/Term=' . $this->term_id;
        }
        if (isset($this->event_id)) {
            $id .= '/Event=' . $this->event_id;
        }
        if (isset($this->course_id)) {
            $id .= '/Course=' . $this->course_id;
        }
        if (!empty($id)) {
            return 'FAU' . $id;
        }
        return null;
    }

    /**
     * @return string|null
     */
    public function getTermId() : ?string
    {
        return $this->term_id;
    }

    /**
     * @return int|null
     */
    public function getEventId() : ?int
    {
        return $this->event_id;
    }

    /**
     * @return int|null
     */
    public function getCourseId() : ?int
    {
        return $this->course_id;
    }
}