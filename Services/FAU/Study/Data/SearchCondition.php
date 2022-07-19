<?php

namespace FAU\Study\Data;

class SearchCondition
{
    // search input
    private string $pattern;
    private string $term_id;
    private array $cos_ids;
    private array $module_ids;
    private int $ilias_ref_id;
    private bool $fitting;

    // calculated conditions
    private ?string $ilias_path;


    public function __construct(
        string $pattern,
        string $term_id,
        array $cos_ids,
        array $module_ids,
        int $ilias_ref_id,
        bool $fitting
    ) {
        $this->pattern = $pattern;
        $this->term_id = $term_id;
        $this->cos_ids = $cos_ids;
        $this->module_ids = $module_ids;
        $this->ilias_ref_id = $ilias_ref_id;
        $this->fitting = $fitting;
    }

    /**
     * @return string
     */
    public function getPattern() : string
    {
        return $this->pattern;
    }

    /**
     * @return string
     */
    public function getTermId() : string
    {
        return $this->term_id;
    }

    /**
     * @return int[]
     */
    public function getCosIds() : array
    {
        return $this->cos_ids;
    }

    /**
     * @return int[]
     */
    public function getModuleIds() : array
    {
        return $this->module_ids;
    }

    /**
     * @return int
     */
    public function getIliasRefId() : int
    {
        return $this->ilias_ref_id;
    }

    /**
     * @return bool
     */
    public function getFitting() : bool
    {
        return $this->fitting;
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
     * @param string|null $ilias_path
     * @return SearchCondition
     */
    public function withIliasPath(?string $ilias_path) : SearchCondition
    {
        $clone = clone($this);
        $clone->ilias_path = $ilias_path;
        return $clone;
    }

}