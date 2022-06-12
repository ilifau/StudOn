<?php  declare(strict_types=1);

namespace FAU\Study\Data;

use FAU\RecordData;

class IndividualDate extends RecordData
{
    protected const tableName = 'fau_study_indi_dates';
    protected const hasSequence = false;
    protected const keyTypes = [
        'individual_dates_id' => 'integer',
    ];
    protected const otherTypes = [
        'planned_dates_id' => 'integer',
        'term_year' => 'integer',
        'term_type_id' => 'integer',
        'date' => 'date',
        'starttime' => 'time',
        'endtime' => 'time',
        'famos_code' => 'text',
        'comment' => 'text',
        'cancelled' => 'integer',
    ];

    protected int $individual_dates_id;
    protected ?int $planned_dates_id;
    protected ?int $term_year;
    protected ?int $term_type_id;
    protected ?string $date;
    protected ?string $starttime;
    protected ?string $endtime;
    protected ?string $famos_code;
    protected ?string $comment;
    protected ?int $cancelled;

    public function __construct(
        int $individual_dates_id,
        ?int $planned_dates_id,
        ?int $term_year,
        ?int $term_type_id,
        ?string $date,
        ?string $starttime,
        ?string $endtime,
        ?string $famos_code,
        ?string $comment,
        ?int $cancelled
    )
    {
        $this->planned_dates_id = $planned_dates_id;
        $this->term_year = $term_year;
        $this->term_type_id = $term_type_id;
        $this->date = $date;
        $this->starttime = $starttime;
        $this->endtime = $endtime;
        $this->famos_code = $famos_code;
        $this->comment = $comment;
        $this->cancelled = $cancelled;
    }

    public static function model(): self
    {
        return new self(0,null,null,null,null,null,
            null,null,null,null);
    }

    /**
     * @return int
     */
    public function getIndividualDatesId() : int
    {
        return $this->individual_dates_id;
    }

    /**
     * @return int|null
     */
    public function getPlannedDatesId() : ?int
    {
        return $this->planned_dates_id;
    }

    /**
     * @return int|null
     */
    public function getTermYear() : ?int
    {
        return $this->term_year;
    }

    /**
     * @return int|null
     */
    public function getTermTypeId() : ?int
    {
        return $this->term_type_id;
    }

    /**
     * @return string|null
     */
    public function getDate() : ?string
    {
        return $this->date;
    }

    /**
     * @return string|null
     */
    public function getStarttime() : ?string
    {
        return $this->starttime;
    }

    /**
     * @return string|null
     */
    public function getEndtime() : ?string
    {
        return $this->endtime;
    }

    /**
     * @return string|null
     */
    public function getFamosCode() : ?string
    {
        return $this->famos_code;
    }

    /**
     * @return string|null
     */
    public function getComment() : ?string
    {
        return $this->comment;
    }

    /**
     * @return int|null
     */
    public function getCancelled() : ?int
    {
        return $this->cancelled;
    }

}