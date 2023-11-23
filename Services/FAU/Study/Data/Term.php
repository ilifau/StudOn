<?php

namespace FAU\Study\Data;

use FAU\RecordData;

/**
 * Data defining a study term (i.e. semester)
 *
 * Terms are stored at different places in StudOn either with a string representation or with separate year and type_id
 * These term objects are created on the fly when they are needed and don't have a period_id
 *
 * Terms with a period_id are stored in the table fau_study_terms
 * These terms are synced from the table study_terms in the staging database
 * The period_id is only used to generate course links from studon to campo
 */
class Term extends RecordData
{
    public const TYPE_ID_SUMMER = 1;
    public const TYPE_ID_WINTER = 2;

    protected const tableName = 'fau_study_terms';
    protected const hasSequence = false;
    protected const keyTypes = [
        'period_id' => 'integer'
    ];
    protected const otherTypes = [
        'year' => 'integer',
        'type_id' => 'integer'
    ];

    protected ?int $year;
    protected ?int $type_id;
    protected ?int $period_id;

    public function __construct(
        ?int $year,
        ?int $type_id,
        ?int $period_id = null
    )
    {
        $this->year = $year;
        $this->type_id = $type_id;
        $this->period_id = $period_id;
    }

    public static function model(): self
    {
        return new self(0, 0, null);
    }


    public function getYear() : ?int
    {
        return $this->year;
    }

    public function getTypeId() : ?int
    {
        return $this->type_id;
    }

    /**
     * @return int|null
     */
    public function getPeriodId() : ?int
    {
        return $this->period_id;
    }


    public function isValid() : bool
    {
        return $this->year >=2000 && ($this->type_id ==1 || $this->type_id == 2);
    }

    public function toString() : ?string
    {
        return $this->isValid() ? sprintf("%04d%01d", $this->year, $this->type_id) : null;
    }


    public static function fromString(?string $string) : self
    {
        $year = (int) substr($string, 0, 4);
        $type_id = (int) substr($string, 4, 1);

        return new self($year, $type_id, null);
    }

}