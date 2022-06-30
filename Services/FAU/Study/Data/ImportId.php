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
    private ?int $event_id;
    private ?int $course_id;
    private ?string $term_id;

    public function __construct(?int $event_id, ?int $course_id, ?string $term_id)
    {
        $this->event_id = $event_id;
        $this->course_id = $course_id;
        $this->term_id = $term_id;
    }

    public static function fromObjects(?Event $event, ?Course $course, ?Term $term) : self
    {
        return new self(
            isset($event) ? $event->getEventId() : null,
            isset($course) ? $course->getCourseId() : null,
            isset($term) ? $term->toString() : null
        );
    }

    public static function fromString(?string $id) : self
    {
        $event_id = null;
        $course_id = null;
        $term_id = null;

        $parts = (array) explode('/', $id);
        if (isset($parts[0]) && $parts[0] == 'FAU') {
            foreach ($parts as $part) {
                list($key, $value) = array_pad(explode( '=', $part), 2, '');
                if (!empty($value)) {
                    switch ($key) {
                        case 'Event':
                            $event_id = (int) $value;
                            break;
                        case 'Course':
                            $course_id = (int) $value;
                            break;
                        case 'Term':
                            $term_id = (string) $value;
                            break;
                    }
                }
            }
        }
        return new self($event_id, $course_id, $term_id);
    }

    public function toString() : ?string
    {
        $id = "";
        if (isset($this->event_id)) {
            $id .= '/Event=' . $this->event_id;
        }
        if (isset($this->course_id)) {
            $id .= '/Course=' . $this->course_id;
        }
        if (isset($this->term_id)) {
            $id .= '/Term=' . $this->term_id;
        }
        if (!empty($id)) {
            return 'FAU' . $id;
        }
        return null;
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

    /**
     * @return string|null
     */
    public function getTermId() : ?string
    {
        return $this->term_id;
    }
}