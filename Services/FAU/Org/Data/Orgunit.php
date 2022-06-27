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
        'fauorg_nr' => 'text',
        'valid_from' => 'date',
        'valid_to' => 'date',
        'shorttext' => 'text',
        'defaulttext' => 'text',
        'longtext' => 'text',
        'ilias_ref_id' => 'integer',
        'no_manager' => 'integer',
        'collect_courses' => 'integer',
        'problem' => 'text'
    ];

    protected int $id;
    protected string $path;
    protected ?int $parent_id;
    protected ?int $assignable;
    protected ?string $fauorg_nr;
    protected ?string $valid_from;
    protected ?string $valid_to;
    protected ?string $shorttext;
    protected string $defaulttext;
    protected ?string $longtext;
    protected ?int $ilias_ref_id;           // ref id of assigned ILIAS category
    protected ?int $no_manager;             // prevent automated manager role assignment in this category
    protected ?int $collect_courses;        // automatically create the courses of child organisations here
    protected ?string $problem;             // problem notice from the check function


    public function __construct(
        int $id,
        string $path,
        ?int $parent_id,
        ?int $assignable,
        ?string $fauorg_nr,
        ?string $valid_from,
        ?string $valid_to,
        ?string $shorttext,
        string $defaulttext,
        ?string $longtext,
        ?string $ilias_ref_id,
        ?int $no_manager,
        ?int $collect_courses,
        ?string $problem
    )
    {
        $this->id = $id;
        $this->path = $path;
        $this->parent_id = $parent_id;
        $this->assignable = $assignable;
        $this->fauorg_nr = $fauorg_nr;
        $this->valid_from = $valid_from;
        $this->valid_to = $valid_to;
        $this->shorttext = $shorttext;
        $this->defaulttext = $defaulttext;
        $this->longtext = $longtext;
        $this->ilias_ref_id = $ilias_ref_id;
        $this->no_manager = $no_manager;
        $this->collect_courses = $collect_courses;
        $this->problem = $problem;
    }

    public static function model(): self
    {
        return new self(0,'',null,null,null,null,null,null,
        '',null,null, null, null,null);
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
     * @return int[]
     */
    public function getPathIds(): array
    {
        $ids = [];
        foreach (explode('.', $this->path) as $id) {
            $ids[] = (int) $id;
        }
        return $ids;
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
    public function getFauorgNr() : ?string
    {
        return $this->fauorg_nr;
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
     * @return int|null
     */
    public function getIliasRefId() : ?int
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
     * @return string|null
     */
    public function getProblem() : ?string
    {
        return $this->problem;
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
    public function withFauorgNr(?string $fauorg_nr) : self
    {
        $clone = clone $this;
        $clone->fauorg_nr = $fauorg_nr;
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
     * @param int|null $ilias_ref_id
     * @return Orgunit
     */
    public function withIliasRefId(?int $ilias_ref_id) : self
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

    /**
     * @param string|null $problem
     * @return Orgunit
     */
    public function withProblem(?string $problem) : self
    {
        $clone = clone $this;
        $clone->problem = $problem;
        return $clone;
    }
}