<?php

namespace FAU\Cond\Data;

/**
 * Expression used in restrictions
 *
 * The expression defines details on how the related requirements should be compared
 * A restriction may have more requirements related expressions
 * these are OR-combined, i.e. only one needs to be passed
 */
class HardExpression
{
    const COMPARE_MIN = 'M';
    const COMPARE_MAX = 'H';

    const COMPULSORY_PF = 'PF';
    const COMPULSORY_WP = 'PF,WP';

    private int $id;
    private string $compare;
    private int $number;
    private ?string $compulsory;

    protected bool $satisfied = false;

    public function __construct(
        int $id,
        string $compare,
        int $number,
        ?string $compulsory
    )
    {
        $this->id = $id;
        $this->compare = $compare;
        $this->number = $number;
        $this->compulsory = $compulsory;
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
    public function getCompare() : string
    {
        return $this->compare;
    }

    /**
     * @return int
     */
    public function getNumber() : int
    {
        return $this->number;
    }

    /**
     * @return string|null
     */
    public function getCompulsory() : ?string
    {
        return $this->compulsory;
    }

    /**
     * Expression is satisfied by a user (later added in the check)
     * @return bool
     */
    public function isSatisfied() : bool
    {
        return $this->satisfied;
    }

    /**
     * Apply the satisfaction of this expression by a user
     * @param bool $satisfied
     * @return self
     */
    public function withSatisfied(bool $satisfied) : self
    {
        $clone = clone $this;
        $clone->satisfied = $satisfied;
        return $clone;
    }

}