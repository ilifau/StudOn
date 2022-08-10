<?php

namespace FAU\Cond\Data;

class HardRequirement
{
    const COMPULSORY_PF = 'PF';
    const COMPULSORY_WP = 'WP';

    private int $id;
    private ?string $name;
    private ?string $compulsory;

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

}