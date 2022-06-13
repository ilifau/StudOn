<?php  declare(strict_types=1);

namespace FAU\Org\Data;

use FAU\RecordData;

class Orgunit extends RecordData
{
    protected const tableName = 'fau_org_orgunits';
    protected const hasSequence = false;
    protected const keyTypes = [
        'id' => 'integer',
    ];
    protected const otherTypes = [
        'path' => 'text',
        'parent_id' => 'integer',
        'assignable' => 'integer',
        'fauOrgKey' => 'text',
        'valid_from' => 'date',
        'valid_to' => 'date',
        'shorttext' => 'text',
        'defaulttext' => 'text',
        'longtext' => 'text',
        'ilias_ref_id' => 'integer',
        'no_manager' => 'integer',
        'collect_courses' => 'integer'
    ];

    protected int $id;
    protected string $path;
    protected ?int $parent_id;
    protected ?int $assignable;
    protected ?string $fauOrgKey;
    protected ?string $valid_from;
    protected ?string $valid_to;
    protected ?string $shorttext;
    protected string $defaulttext;
    protected ?string $longtext;
    protected ?string $ilias_ref_id;        // ref id of assigned ILIAS category
    protected ?int $no_manager;             // prevent automated manager role assignment in this category
    protected ?int $collect_courses;        // automatically create the courses of child organisations here


    public function __construct(
        int $id,
        string $path,
        ?int $parent_id,
        ?int $assignable,
        ?string $fauOrgKey,
        ?string $valid_from,
        ?string $valid_to,
        ?string $shorttext,
        string $defaulttext,
        ?string $longtext,
        ?string $ilias_ref_id,
        ?int $no_manager,
        ?int $collect_courses
    )
    {
        $this->id = $id;
        $this->path = $path;
        $this->parent_id = $parent_id;
        $this->assignable = $assignable;
        $this->fauOrgKey = $fauOrgKey;
        $this->valid_from = $valid_from;
        $this->valid_to = $valid_to;
        $this->shorttext = $shorttext;
        $this->defaulttext = $defaulttext;
        $this->longtext = $longtext;
        $this->ilias_ref_id = $ilias_ref_id;
        $this->no_manager = $no_manager;
        $this->collect_courses = $collect_courses;
    }

    public static function model(): self
    {
        return new self(0,'',null,null,null,null,null,null,
        '',null,null, null, null);
    }

    /**
     * @return int
     */
    public function getId() : int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getPath() : string
    {
        return $this->path;
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
     * @return int|null
     */
    public function getNoManager() : ?int
    {
        return $this->no_manager;
    }

    /**
     * @return int|null
     */
    public function getCollectCourses() : ?int
    {
        return $this->collect_courses;
    }

    /**
     * @param int $id
     * @return self
     */
    public function withId(int $id) : self
    {
        $clone = clone $this;
        $clone->id = $id;
        return $clone;
    }

    /**
     * @param string $path
     * @return self
     */
    public function withPath(string $path) : self
    {
        $clone = clone $this;
        $clone->path = $path;
        return $clone;
    }


    /**
     * @param int|null $parent_id
     * @return self
     */
    public function withParentId(?int $parent_id) : self
    {
        $clone = clone $this;
        $clone->parent_id = $parent_id;
        return $clone;
    }


    /**
     * @param int|null $assignable
     * @return self
     */
    public function withAssignable(?int $assignable) : self
    {
        $clone = clone $this;
        $clone->assignable = $assignable;
        return $clone;
    }

    /**
     * @param string|null $fauOrgKey
     * @return self
     */
    public function withFauOrgKey(?string $fauOrgKey) : self
    {
        $clone = clone $this;
        $clone->fauOrgKey = $fauOrgKey;
        return $clone;
    }

    /**
     * @param string|null $valid_from
     * @return self
     */
    public function withValidFrom(?string $valid_from) : self
    {
        $clone = clone $this;
        $clone->valid_from = $valid_from;
        return $clone;
    }

    /**
     * @param string|null $valid_to
     * @return self
     */
    public function withValidTo(?string $valid_to) : self
    {
        $clone = clone $this;
        $clone->valid_to = $valid_to;
        return $clone;
    }

    /**
     * @param string|null $shorttext
     * @return self
     */
    public function withShorttext(?string $shorttext) : self
    {
        $clone = clone $this;
        $clone->shorttext = $shorttext;
        return $clone;
    }

    /**
     * @param string $defaulttext
     * @return self
     */
    public function withDefaulttext(string $defaulttext) : self
    {
        $clone = clone $this;
        $clone->defaulttext = $defaulttext;
        return $clone;
    }

    /**
     * @param string|null $longtext
     * @return self
     */
    public function withLongtext(?string $longtext) : self
    {
        $clone = clone $this;
        $clone->longtext = $longtext;
        return $clone;
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

    /**
     * @param int|null $no_manager
     * @return Orgunit
     */
    public function withNoManager(?int $no_manager) : self
    {
        $clone = clone $this;
        $clone->no_manager = $no_manager;
        return $clone;
    }

    /**
     * @param int|null $collect_courses
     * @return Orgunit
     */
    public function withCollectCourses(?int $collect_courses) : self
    {
        $clone = clone $this;
        $clone->collect_courses = $collect_courses;
        return $clone;
    }
}