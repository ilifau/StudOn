<?php declare(strict_types=1);

namespace FAU\Ilias\Data;

use FAU\RecordData;

class RegLog extends RecordData
{
    const ACTION_ADD_TO_WAITING_LIST = 'addToWaitingList';
    const ACTION_REMOVE_FROM_WAITING_LIST = 'removeFromWaitingList';
    const ACTION_UPDATE_WAITING_LIST = 'updateWaitingList';

    const ACTION_ADD_PARTICIPANT = 'addParticipant';
    const ACTION_DELETE_PARTICIPANT = 'deleteParticipant';

    const ACTION_ASSIGN_USER = 'assignUser';
    const ACTION_DEASSIGN_USER = 'deassignUser';


    protected const tableName = 'fau_ilias_reglog';
    protected const hasSequence = true;
    protected const keyTypes = [
        'id' => 'integer',
    ];
    protected const otherTypes = [
        'timestamp' => 'integer',
        'action' => 'text',
        'actor_id' => 'integer',
        'user_id' => 'integer',
        'obj_id' => 'integer',
        'to_confirm' => 'integer',
        'module_id' => 'integer',
        'subject' => 'text',
    ];
    protected int $id;
    protected int $timestamp;
    protected string $action;
    protected int $actor_id;
    protected int $user_id;
    protected int $obj_id;
    protected int $to_confirm;
    protected ?int $module_id;
    protected ?string $subject;

    public function __construct(
        int $id,
        int $timestamp,
        string $action,
        int $actor_id,
        int $user_id,
        int $obj_id,
        int $to_confirm,
        ?int $module_id = null,
        ?string $subject = null
    )
    {
        $this->id = $id;
        $this->timestamp = $timestamp;
        $this->action = $action;
        $this->actor_id = $actor_id;
        $this->user_id = $user_id;
        $this->obj_id = $obj_id;
        $this->to_confirm = $to_confirm;
        $this->module_id = $module_id;
        $this->subject = $subject;
    }

    public static function model(): self
    {
        return new self(0,0,'',0,0,0, 0,null,null);
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
    public function getActorId() : int
    {
        return $this->actor_id;
    }

    /**
     * @return int
     */
    public function getUserId() : int
    {
        return $this->user_id;
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
    public function getToConfirm() : int
    {
        return $this->to_confirm;
    }

    /**
     * @return int|null
     */
    public function getModuleId() : ?int
    {
        return $this->module_id;
    }

    /**
     * @return string|null
     */
    public function getSubject() : ?string
    {
        return $this->subject;
    }

}