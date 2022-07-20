<?php

namespace FAU\Study\Data;

use FAU\RecordData;

class SearchCondition extends RecordData
{
    protected const otherTypes = [
        'pattern' => 'text',
        'term_id' => 'text',
        'cos_ids' => 'text',
        'module_ids' => 'text',
        'ilias_ref_id' => 'integer',
        'fitting' => 'integer',
        'limit' => 'integer',
        'offset' => 'integer',
    ];

    // search input
    protected ?string $pattern;
    protected ?string $term_id;
    protected ?string $cos_ids;
    protected ?string $module_ids;
    protected ?int $ilias_ref_id;
    protected ?int $fitting;

    // paging
    protected ?int $limit = null;
    protected ?int $offset = null;

    // calculated conditions
    protected ?string $ilias_path = null;

    public function __construct(
        string $pattern,
        string $term_id,
        string $cos_ids,
        string $module_ids,
        int $ilias_ref_id,
        bool $fitting
    ) {
        $this->pattern = $pattern;
        $this->term_id = $term_id;
        $this->cos_ids = $cos_ids;
        $this->module_ids = $module_ids;
        $this->ilias_ref_id = $ilias_ref_id;
        $this->fitting = (int) $fitting;
    }

    public static function model() : self
    {
        return new self('', '', '', '',0, false);
    }

    /**
     * @return string
     */
    public function getPattern() : string
    {
        return (string) $this->pattern;
    }

    /**
     * @return string
     */
    public function getTermId() : string
    {
        return (string) $this->term_id;
    }

    public function getTerm() : Term
    {
        return Term::fromString($this->term_id);
    }

    /**
     * @return string
     */
    public function getCosIds() : string
    {
        return (string) $this->cos_ids;
    }

    /**
     * @return int[]
     */
    public function getCosIdsArray() : array
    {
        $ids = [];
        foreach (explode(',', $this->cos_ids) as $id) {
            $ids[] = (int) trim($id);
        }
        return $ids;
    }

    /**
     * @return string
     */
    public function getModuleIds() : string
    {
        return (string) $this->module_ids;
    }

    /**
     * @return int[]
     */
    public function getModuleIdsArray() : array
    {
        $ids = [];
        foreach (explode(',', $this->module_ids) as $id) {
            $ids[] = (int) trim($id);
        }
        return $ids;
    }

    /**
     * @return int
     */
    public function getIliasRefId() : int
    {
        return (int) $this->ilias_ref_id;
    }

    /**
     * @return bool
     */
    public function getFitting() : bool
    {
        return (bool) $this->fitting;
    }

    /**
     * check if condition is empty
     */
    public function isEmpty() : bool
    {
        return empty($this->pattern)
            && empty($this->term_id)
            && empty($this->cos_ids)
            && empty($this->module_ids)
            && empty($this->ilias_ref_id)
            && empty($this->fitting);
    }

    /**
     * @return string|null
     */
    public function getIliasPath() : ?string
    {
        return $this->ilias_path;
    }

    /**
     * @return int|null
     */
    public function getLimit() : ?int
    {
        return $this->limit;
    }

    /**
     * @return int|null
     */
    public function getOffset() : ?int
    {
        return $this->offset;
    }

    /**
     * @param string|null $ilias_path
     * @return SearchCondition
     */
    public function withIliasPath(?string $ilias_path) : SearchCondition
    {
        $clone = clone($this);
        $clone->ilias_path = $ilias_path;
        return $clone;
    }

    /**
     * @param int|null $limit
     * @return SearchCondition
     */
    public function withLimit(?int $limit) : SearchCondition
    {
        $clone = clone($this);
        $clone->limit = $limit;
        return $clone;
    }

    /**
     * @param int|null $offset
     * @return SearchCondition
     */
    public function withOffset(?int $offset) : SearchCondition
    {
        $clone = clone($this);
        $clone->offset = $offset;
        return $clone;
    }

}