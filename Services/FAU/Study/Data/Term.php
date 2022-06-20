<?php

namespace FAU\Study\Data;

class Term
{
    const TYPE_ID_SS = 1;
    const TYPE_ID_WS = 2;

    private int $year;
    private int $type_id;

    public function __construct(
        int $year,
        int $type_id
    )
    {
        $this->year = $year;
        $this->type_id = $type_id;
    }

    /**
     * @return int
     */
    public function getYear() : int
    {
        return $this->year;
    }

    /**
     * @return int
     */
    public function getTypeId() : int
    {
        return $this->type_id;
    }

    /**
     * @return string
     */
    public function getString() : string
    {
        return $this->getYear() . $this->getTypeId();
    }

    /**
     * @param string $string
     * @return ?self
     */
    public static function fromString(string $string) : ?self
    {
        $year = (int) substr($string, 0, 4);
        $type_id = (int) substr($string, 4, 1);

        if ($year < 2000 || $type_id < 1 || $type_id > 2) {
            return null;
        }

        return new self($year, $type_id);
    }

}