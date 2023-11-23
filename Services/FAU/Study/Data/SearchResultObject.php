<?php

namespace FAU\Study\Data;

use FAU\RecordData;

class SearchResultObject extends RecordData
{
    protected const otherTypes = [
        'obj_id' => 'integer',
        'ref_id' => 'integer',
        'type' => 'string'
    ];

    // from initial query
    protected ?int $obj_id;
    protected ?int $ref_id;
    protected ?string $type;

    public function __construct (
        ?int $obj_id,
        ?int $ref_id,
        ?string $type
    )
    {
        $this->obj_id = $obj_id;
        $this->ref_id = $ref_id;
        $this->type = $type;
    }

    public static function model() : self
    {
        return new self(null, null, null);
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
     * @return string|null
     */
    public function getType() : ?string
    {
        return $this->type;
    }


    /**
     * Check if the data is valid
     */
    public function isValid()
    {
        return !empty($this->obj_id) && !empty($this->ref_id);
    }
}