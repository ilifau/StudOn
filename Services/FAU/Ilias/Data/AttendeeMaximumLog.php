<?php declare(strict_types=1);

namespace FAU\Ilias\Data;

use FAU\RecordData;

class AttendeeMaximumLog extends RecordData
{
    const ACTION_ATTENDEE_MAXIMUM_CHANGED = 'attendeeMaximumChanged';

    protected const tableName = 'fau_ilias_attmaxlog';
    protected const hasSequence = true;
    protected const keyTypes = [
        'id' => 'integer',
    ];
    protected const otherTypes = [
        'attendee_max' => 'integer',
        'timestamp' => 'integer',
        'action' => 'text',
        'obj_id' => 'integer',
        'user_id' => 'integer'
    ];
    protected int $id;
    protected int $attendee_max;
    protected int $timestamp;
    protected string $action;
    protected int $obj_id;
    protected int $user_id;

    public function __construct(
        int $id,
        int $attendee_max,
        int $timestamp,
        string $action,
        int $obj_id,
        int $user_id
    )
    {
        $this->id = $id;
        $this->attendee_max = $attendee_max;
        $this->timestamp = $timestamp;
        $this->action = $action;
        $this->obj_id = $obj_id;
        $this->user_id = $user_id;
    }

    public static function model(): self
    {
        return new self(0,0,0,'',0, 0);
    }

    /**
     * @return int
     */
    public function getId() : int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getTimestamp() : int
    {
        return $this->timestamp;
    }

    /**
     * @return string
     */
    public function getAction() : string
    {
        return $this->action;
    }

    /**
     * @return int
     */
    public function getObjId() : int
    {
        return $this->obj_id;
    }
    /**
     * @return int
     */
    public function getAttendeeMax() : int
    {
        return $this->attendee_max;
    }   
    
    /**
     * @return int
     */
    public function getUserId() : int
    {
        return $this->user_id;
    }     
}