<?php

namespace FAU\User\Data;

class OrgRole
{
    const TYPE_AUTHOR = 'author';
    const TYPE_MANAGER = 'manager';

    private string $type;
    private string $orgunit;


    public function __construct(array $data)
    {
        $this->type = isset($data['type']) ? (string) $data['type'] : '';
        $this->orgunit = isset($data['orgunit']) ? (string) $data['orgunit'] : '';
    }

    /**
     * @return string
     */
    public function getType() : string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getOrgunit() : string
    {
        return $this->orgunit;
    }
}