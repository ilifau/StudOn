<?php

namespace FAU\Cond\Data;

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

}