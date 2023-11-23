<?php

namespace FAU\Cond\Data;

/**
 * Requirement that is checked by a restriction
 * The ids of requirements correspond to achievements of users
 */
class HardRequirement
{
    const COMPULSORY_PF = 'PF';
    const COMPULSORY_WP = 'WP';

    private int $id;
    private ?string $name;
    private ?string $compulsory;

    protected bool $satisfied = false;


    public function __construct(
        int $id,
        ?string $name,
        ?string $compulsory
    )
    {
        $this->id = $id;
        $this->name = $name;
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
     * @return string|null
     */
    public function getName() : ?string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getCompulsory() : ?string
    {
        return $this->compulsory;
    }

    /**
     * Requirement is satisfied by a user (later added in the check)
     * @return bool
     */
    public function isSatisfied() : bool
    {
        return $this->satisfied;
    }

    /**
     * Apply the satisfaction of this requirement by a user
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