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
     * Add a course of study id for which this restriction should be tested
     * @param int $regarding_cos_id
     * @return EventRestriction
     */
    public function withRegardingCosId(int $id): HardRestriction
    {
        $clone = clone $this;
        $clone->regarding_cos_ids[] = $id;
        return $clone;
    }

    /**
     * Add a course of study id for which this restriction should not be tested
     * @param int[] $exception_cos_ids
     * @return EventRestriction
     */
    public function withExceptionCosId(int $id): HardRestriction
    {
        $clone = clone $this;
        $clone->exception_cos_ids[] = $id;
        return $clone;
    }

}