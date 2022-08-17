<?php  declare(strict_types=1);

namespace FAU\Staging\Data;

use FAU\RecordData;

class StudonChange extends RecordData
{
    const TYPE_ATTENDEE_MAXIMUM_CHANGED = 'attendee_maximum_changed';
    const TYPE_REGISTERED = 'registered';
    const TYPE_NOT_REGISTERED = 'not_registered';
    const TYPE_PASSED = 'passed';
    const TYPE_FAILED = 'failed';


    protected const tableName = 'studon_changes';
    protected const hasSequence = false;
    protected const keyTypes = [
        'id' => 'integer',
    ];
    protected const otherTypes = [
        'person_id' => 'integer',
        'course_id' => 'integer',
        'module_id' => 'integer',
        'change_type' => 'text',
        'attendee_maximum' => 'integer',
        'ts_change' => 'text',
        'ts_logged' => 'text',
        'ts_processed' => 'text',
    ];
    
    protected ?int $id;
    protected ?int $person_id;
    protected int $course_id;
    protected ?int $module_id;
    protected string $change_type;
    protected ?string $attendee_maximum;
    protected string $ts_change;
    protected string $ts_logged;
    protected ?string $ts_processed;

    public function __construct(
        ?int $id,
        ?int $person_id,
        int $course_id,
        ?int $module_id,
        string $change_type,
        ?string $attendee_maximum,
        string $ts_change,
        string $ts_logged,
        ?string $ts_processed
    )
    {
        $this->id = $id;
        $this->person_id = $person_id;
        $this->course_id = $course_id;
        $this->module_id = $module_id;
        $this->change_type = $change_type;
        $this->attendee_maximum = $attendee_maximum;
        $this->ts_change = $ts_change;
        $this->ts_logged = $ts_logged;
        $this->ts_processed = $ts_processed;
    }

    public static function model(): self
    {
        return new self(null,null,0,null,'',null,'','',null);
    }

    /**
     * @return int|null
     */
    public function getId() : ?int
    {
        return $this->id;
    }

    /**
     * @return int|null
     */
    public function getPersonId() : ?int
    {
        return $this->person_id;
    }

    /**
     * @return int
     */
    public function getCourseId() : int
    {
        return $this->course_id;
    }

    /**
     * @return int|null
     */
    public function getModuleId() : ?int
    {
        return $this->module_id;
    }

    /**
     * @return string
     */
    public function getChangeType() : string
    {
        return $this->change_type;
    }

    /**
     * @return string|null
     */
    public function getAttendeeMaximum() : ?string
    {
        return $this->attendee_maximum;
    }

    /**
     * @return string
     */
    public function getTsChange() : string
    {
        return $this->ts_change;
    }

    /**
     * @return string
     */
    public function getTsLogged() : string
    {
        return $this->ts_logged;
    }

    /**
     * @return string|null
     */
    public function getTsProcessed() : ?string
    {
        return $this->ts_processed;
    }

}