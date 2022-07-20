<?php

namespace FAU\Study\Data;

use FAU\RecordData;

class SearchResultObject extends RecordData
{
    protected const otherTypes = [
        'obj_id' => 'integer',
        'ref_id' => 'integer'
    ];

    // from initial query
    protected ?int $obj_id;
    protected ?int $ref_id;

    public function __construct (
        ?int $obj_id,
        ?int $ref_id
    )
    {
        $this->obj_id = $obj_id;
        $this->ref_id = $ref_id;
    }

    public static function model() : self
    {
        return new self(null, null);
    }

    /**
     * @return int|null
     */
    public function getObjId() : ?int
    {
        return $this->obj_id;
    }

    /**
     * @return int|null
     */
    public function getRefId() : ?int
    {
        return $this->ref_id;
    }

    /**
     * Check if the data is valid
     */
    public function isValid()
    {
        return !empty($this->obj_id) && !empty($this->ref_id);
    }
}