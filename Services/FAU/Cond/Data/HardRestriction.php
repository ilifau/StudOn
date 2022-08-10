<?php

namespace FAU\Cond\Data;

class HardRestriction
{
    const TYPE_REQUIREMENT = 'Vorleistung';
    const TYPE_SUBJECT_SEMESTER = 'Fachsemester';
    const TYPE_CLINICAL_SEMESTER = 'KlinischesSemester';

    private int $module_id;
    private string $restriction;
    private string $type;

    /** @var HardExpression[]  */
    private array $expressions = [];

    /** @var HardRequirement[] */
    private array $requirements = [];


    public function __construct(
        int $module_id,
        string $restriction,
        string $type
    ) {

        $this->module_id = $module_id;
        $this->restriction = $restriction;
        $this->type = $type;
    }

    /**
     * @return int
     */
    public function getModuleId() : int
    {
        return $this->module_id;
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
}