<?php

namespace FAU\Study\Data;

class SearchCondition
{
    // search input
    private string $pattern;
    private string $term_id;
    private int $cos_id;
    private int $module_id;
    private int $ilias_ref_id;
    private bool $fitting;

    // calculated conditions
    private ?string $ilias_path;


    public function __construct(
        string $pattern,
        string $term_id,
        int $cos_id,
        int $module_id,
        int $ilias_ref_id,
        bool $fitting
    ) {
        $this->pattern = $pattern;
        $this->term_id = $term_id;
        $this->cos_id = $cos_id;
        $this->module_id = $module_id;
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
     * @return int
     */
    public function getCosId() : int
    {
        return $this->cos_id;
    }

    /**
     * @return int
     */
    public function getModuleId() : int
    {
        return $this->module_id;
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
            && empty($this->cos_id)
            && empty($this->module_id)
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