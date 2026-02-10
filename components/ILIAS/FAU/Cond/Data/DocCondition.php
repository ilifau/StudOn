<?php

namespace FAU\Cond\Data;

use FAU\RecordData;

class DocCondition extends RecordData
{
    protected const tableName = 'fau_cond_doc_prog';
    protected const hasSequence = true;
    protected const keyTypes = [
        'id' => 'integer',
    ];
    protected const otherTypes = [
        'ilias_obj_id' => 'integer',
        'prog_code' => 'text',
        'min_approval_date' => 'date',
        'max_approval_date' => 'date',
    ];
    
    protected int $id;
    protected int $ilias_obj_id;
    protected ?string $prog_code;
    protected ?string $min_approval_date;
    protected ?string $max_approval_date;

    public function __construct(
        int $id,
        int $ilias_obj_id,
        ?string $prog_code,
        ?string $min_approval_date,
        ?string $max_approval_date
    )
    {
        $this->id = $id;
        $this->ilias_obj_id = $ilias_obj_id;
        $this->prog_code = $prog_code;
        $this->min_approval_date = $min_approval_date;
        $this->max_approval_date = $max_approval_date;
    }

    public static function model()
    {
        return new self(0,0, null, null, null);
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
    public function getIliasObjId() : int
    {
        return $this->ilias_obj_id;
    }

    /**
     * @return string|null
     */
    public function getProgCode() : ?string
    {
        return $this->prog_code;
    }

    /**
     * @return string|null
     */
    public function getMinApprovalDate() : ?string
    {
        return $this->min_approval_date;
    }

    /**
     * @return string|null
     */
    public function getMaxApprovalDate() : ?string
    {
        return $this->max_approval_date;
    }

    /**
     * Get a clone for a new ilias object
     */
    public function cloneFor(int $obj_id) : self
    {
        $clone = clone $this;
        $clone->id = 0;
        $clone->ilias_obj_id = $obj_id;
        return $clone;
    }

}