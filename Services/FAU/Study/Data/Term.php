<?php

namespace FAU\Study\Data;

class Term
{
    const TYPE_ID_SUMMER = 1;
    const TYPE_ID_WINTER = 2;

    private bool $valid = false;

    private ?int $year;
    private ?int $type_id;

    public function __construct(
        ?int $year,
        ?int $type_id
    )
    {
        if ($year == null || $year < 2000 || $type_id == null || $type_id < 1 || $type_id > 2) {
            $this->year = null;
            $this->type_id = null;
            $this->valid = false;
        }
        $this->year = $year;
        $this->type_id = $type_id;
        $this->valid = true;
    }

     public function getYear() : ?int
    {
        return $this->year;
    }

    public function getTypeId() : ?int
    {
        return $this->type_id;
    }


     public function isValid() : bool
    {
        return $this->valid;
    }

    public function toString() : ?string
    {
        return $this->isValid() ? sprintf("%04d%01d", $this->year, $this->type_id) : null;
    }


    public static function fromString(?string $string) : self
    {
        $year = (int) substr($string, 0, 4);
        $type_id = (int) substr($string, 4, 1);

        return new self($year, $type_id);
    }

}