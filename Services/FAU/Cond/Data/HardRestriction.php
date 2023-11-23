<?php

namespace FAU\Cond\Data;

class HardRestriction
{
    const TYPE_REQUIREMENT = 'Vorleistung';
    const TYPE_SUBJECT_SEMESTER = 'Fachsemester';
    const TYPE_CLINICAL_SEMESTER = 'KlinischesSemester';

    private string $restriction;
    private string $type;

    /** @var HardExpression[]  */
    private array $expressions = [];

    /** @var HardRequirement[] */
    private array $requirements = [];

    /**
     * Course of study ids for which this restriction is valid
     * @var int[]
     */
    protected array $regarding_cos_ids = [];

    /**
     * Course of study ids for which this restriction is not valid
     * @var int[]
     */
    protected array $exception_cos_ids = [];


    /**
     * Course of study ids that fit a user's study (later added in the check)
     * @var int[]
     */
    protected array $fitting_cos_ids = [];

    /**
     * Restriction is satisfied by a user (later added in the check)
     * @var bool
     */
    protected bool $satisfied = false;


    public function __construct(
        string $restriction,
        string $type
    ) {
        $this->restriction = $restriction;
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getRestriction() : string
    {
        return $this->restriction;
    }

    /**
     * @return string
     */
    public function getType() : string
    {
        return $this->type;
    }

    /**
     * Get the expressions defined for the restrictions
     * These expressions are OR-combined, only one needs to be satisfied
     * @return HardExpression[] indexed by the expression id (field id of the table fau_cond_restrictions)
     */
    public function getExpressions() : array
    {
        return $this->expressions;
    }

    /**
     * Check if the restriction has an expression added with an id
     */
    public function hasExpression(int $id) : bool
    {
        return isset($this->expressions[$id]);
    }


    /**
     * @param HardExpression $expression
     * @return HardRestriction
     */
    public function withExpression(HardExpression $expression) : HardRestriction
    {
        $clone = clone $this;
        $clone->expressions[$expression->getId()] = $expression;
        return $clone;
    }

    /**
     * Clear the expressions in the restriction
     * @return HardRestriction
     */
    public function withoutExpressions() : HardRestriction
    {
        $clone = clone $this;
        $clone->expressions = [];
        return $clone;
    }

    /**
     * @return HardRequirement[]
     */
    public function getRequirements() : array
    {
        return $this->requirements;
    }

    /**
     * @param HardRequirement $requirement
     * @return HardRestriction indexed by the requirement id
     */
    public function withRequirement(HardRequirement $requirement) : HardRestriction
    {
        $clone = clone $this;
        $clone->requirements[$requirement->getId()] = $requirement;
        return $clone;
    }


    /**
     * Clear the requirements in the restriction
     * @return HardRestriction
     */
    public function withoutRequirements() : HardRestriction
    {
        $clone = clone $this;
        $clone->requirements = [];
        return $clone;
    }


    /**
     * Check if the restriction as a requirement added with an id
     */
    public function hasRequirement(int $id) : bool
    {
        return isset($this->requirements[$id]);
    }


    /**
     * Get the course of study ids for which this restriction should be tested
     * If the list is empty then the restriction is valid for all
     * @return int[]
     */
    public function getRegardingCosIds(): array
    {
        return $this->regarding_cos_ids;
    }

    /**
     * Get the course of study ids for which this restriction should not be tested
     * If the list is empty then the restriction is valid for all
     * @return int[]
     */
    public function getExceptionCosIds(): array
    {
        return $this->exception_cos_ids;
    }

    /**
     * Get the course of study ids for this restriction that fit the user's study
     * @return int[]
     */
    public function getFittingCosIds() : array
    {
        return $this->fitting_cos_ids;
    }

    /**
     * Restriction is satisfied by a user (later added in the check)
     */
    public function isSatisfied() : bool
    {
        return $this->satisfied;
    }

    /**
     * Add a course of study id for which this restriction should be tested
     */
    public function withRegardingCosId(int $id): HardRestriction
    {
        $clone = clone $this;
        $clone->regarding_cos_ids[] = $id;
        return $clone;
    }

    /**
     * Add a course of study id for which this restriction should not be tested
     */
    public function withExceptionCosId(int $id): HardRestriction
    {
        $clone = clone $this;
        $clone->exception_cos_ids[] = $id;
        return $clone;
    }

    /**
     * Add a course of study ids for this restriction that fit the user's study
     */
    public function withFittingCosId(int $id) : HardRestriction
    {
        $clone = clone $this;
        $clone->fitting_cos_ids[] = $id;
        return $clone;
    }


    /**
     * Apply the satisfaction of this restriction by a user
     * @param bool $satisfied
     * @return HardRestriction
     */
    public function withSatisfied(bool $satisfied) : HardRestriction
    {
        $clone = clone $this;
        $clone->satisfied = $satisfied;
        return $clone;
    }

}