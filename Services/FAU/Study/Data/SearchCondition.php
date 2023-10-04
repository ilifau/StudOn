<?php

namespace FAU\Study\Data;

use FAU\RecordData;

/**
 * Condition for searching campo courses
 * This model is stored fpr users via \FAU\Tools\Preferences::setSearchCondition
 * It does not have an own database table
 */
class SearchCondition extends RecordData
{
    protected const otherTypes = [
        'pattern' => 'text',
        'term_id' => 'text',
        'event_type' => 'text',
        'cos_ids' => 'text',
        'module_ids' => 'text',
        'ilias_ref_id' => 'integer',
        'ilias_path' => 'text',
        'fitting' => 'integer',
        'found' => 'integer',
        'limit' => 'integer',
        'offset' => 'integer',
    ];

    // search input
    protected ?string $pattern;
    protected ?string $term_id;
    protected ?string $event_type;
    protected ?string $cos_ids;
    protected ?string $module_ids;
    protected ?int $ilias_ref_id;
    protected ?string $ilias_path = null;
    protected ?int $fitting;

    // paging
    protected ?int $limit = 100;
    protected ?int $offset = null;
    protected ?int $found = null;


    public function __construct(
        string $pattern,
        string $term_id,
        string $event_type, 
        string $cos_ids,
        string $module_ids,
        int $ilias_ref_id,
        bool $fitting
    ) {
        $this->pattern = $pattern;
        $this->term_id = $term_id;
        $this->event_type = $event_type;
        $this->cos_ids = $cos_ids;
        $this->module_ids = $module_ids;
        $this->ilias_ref_id = $ilias_ref_id;
        $this->fitting = (int) $fitting;
    }

    public static function model() : self
    {
        return new self('', '', '', '', '',0, false);
    }

    /**
     * Get the signature of the conditions (used as cache key)
     */
    public function getSignature()
    {
        return md5(json_encode([
            $this->pattern, $this->term_id, $this->cos_ids, $this->module_ids, $this->ilias_ref_id, $this->ilias_path, $this->fitting
        ]));
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
    public function getEventType(): string
    {
        return (string) $this->event_type;
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
            if (!empty($id)) {
                $ids[] = (int) trim($id);
            }
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
            if (!empty($id)) {
                $ids[] = (int) trim($id);
            }
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
     * @return int|null
     */
    public function getFound() : ?int
    {
        return $this->found;
    }

    /**
     * Get the page that should be displayed (starting with 0)
     */
    public function getPage() : int
    {
        if (empty($this->limit) || empty($this->offset)) {
            return 0;
        }
        return (int) ($this->offset / $this->limit);
    }

    public function needsPaging() : int
    {
        return !empty($this->limit) && (int) $this->found > (int) $this->limit;
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
     * Set the search limit
     */
    public function withLimit(?int $limit) : SearchCondition
    {
        $clone = clone($this);
        $clone->limit = $limit;
        $clone->offset = 0;
        return $clone;
    }

    /**
     * Set the number of found records
     */
    public function withFound(?int $found) : SearchCondition
    {
        $clone = clone($this);
        if ($found !== $clone->found) {
            $clone->offset = 0;
        }
        $clone->found = $found;
        return $clone;
    }

    /**
     * Set the page that should be displayed (starting with 0)
     */
    public function withPage(int $page) : self
    {
        $clone = clone ($this);
        if (empty($clone->limit)) {
            $clone->offset = 0;
            return $clone;
        }

        // ensure a valid page
        $page = max($page, 0);
        $page = min($page, (int) ((int) $clone->found / $clone->limit));

        // set the record offset according to the page
        $clone->offset = ($page) * $clone->limit;
        return $clone;
    }
}