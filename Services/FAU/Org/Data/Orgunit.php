<?php  declare(strict_types=1);

namespace FAU\Orgunit\Data;

use FAU\RecordData;

class Orgunit extends RecordData
{
    protected const tableName = 'fau_org_orgunits';
    protected const hasSequence = false;
    protected const keyTypes = [
        'id' => 'integer',
    ];
    protected const otherTypes = [
        'parent_id' => 'integer',
        'assignable' => 'integer',
        'fauOrgKey' => 'text',
        'valid_from' => 'date',
        'valid_to' => 'date',
        'shorttext' => 'text',
        'defaulttext' => 'text',
        'longtext' => 'text',
        'ilias_ref_id' => 'integer'
    ];

    protected int $id;
    protected ?int $parent_id;
    protected ?int $assignable;
    protected ?string $fauOrgKey;
    protected ?string $valid_from;
    protected ?string $valid_to;
    protected ?string $shorttext;
    protected string $defaulttext;
    protected ?string $longtext;
    protected ?string $ilias_ref_id;


    public function __construct(
        int $id,
        ?int $parent_id,
        ?int $assignable,
        ?string $fauOrgKey,
        ?string $valid_from,
        ?string $valid_to,
        ?string $shorttext,
        string $defaulttext,
        ?string $longtext,
        ?string $ilias_ref_id
    )
    {
        $this->id = $id;
        $this->parent_id = $parent_id;
        $this->assignable = $assignable;
        $this->fauOrgKey = $fauOrgKey;
        $this->valid_from = $valid_from;
        $this->valid_to = $valid_to;
        $this->shorttext = $shorttext;
        $this->defaulttext = $defaulttext;
        $this->longtext = $longtext;
        $this->ilias_ref_id = $ilias_ref_id;
    }

    public static function model(): self
    {
        return new self(0,null,null,null,null,null,null,
        '',null,null);
    }

    /**
     * @return int
     */
    public function getId() : int
    {
        return $this->id;
    }

    /**
     * @return int|null
     */
    public function getParentId() : ?int
    {
        return $this->parent_id;
    }

    /**
     * @return int|null
     */
    public function getAssignable() : ?int
    {
        return $this->assignable;
    }

    /**
     * @return string|null
     */
    public function getFauOrgKey() : ?string
    {
        return $this->fauOrgKey;
    }

    /**
     * @return string|null
     */
    public function getValidFrom() : ?string
    {
        return $this->valid_from;
    }

    /**
     * @return string|null
     */
    public function getValidTo() : ?string
    {
        return $this->valid_to;
    }

    /**
     * @return string|null
     */
    public function getShorttext() : ?string
    {
        return $this->shorttext;
    }

    /**
     * @return string
     */
    public function getDefaulttext() : string
    {
        return $this->defaulttext;
    }

    /**
     * @return string|null
     */
    public function getLongtext() : ?string
    {
        return $this->longtext;
    }

    /**
     * @return string|null
     */
    public function getIliasRefId() : ?string
    {
        return $this->ilias_ref_id;
    }

    /**
     * @param string|null $ilias_ref_id
     * @return Orgunit
     */
    public function withIliasRefId(?string $ilias_ref_id) : Orgunit
    {
        $clone = clone $this;
        $clone->ilias_ref_id = $ilias_ref_id;
        return $clone;
    }
}