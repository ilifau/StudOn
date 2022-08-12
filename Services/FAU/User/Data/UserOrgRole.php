<?php

namespace FAU\User\Data;

use FAU\RecordData;

/**
 * Org role that is applied to a user
 */
class UserOrgRole extends RecordData
{

    protected const tableName = 'fau_user_org_roles';
    protected const hasSequence = false;
    protected const keyTypes = [
        'user_id' => 'integer',
        'ref_id' => 'integer',
        'type' => 'text'
    ];
    protected const otherTypes = [
    ];

    protected int $user_id;
    protected int $ref_id;
    protected string $orgunit;
    protected string $type;

    public function __construct(int $user_id, int $ref_id, string $type) {

        $this->user_id = $user_id;
        $this->ref_id = $ref_id;
        $this->type = $type;
    }

    public static function model() : self
    {
        return new self(0,0,'');
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
    public function getRefId() : int
    {
        return $this->ref_id;
    }

    /**
     * @return string
     */
    public function getType() : string
    {
        return $this->type;
    }

}