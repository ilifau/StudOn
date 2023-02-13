<?php  declare(strict_types=1);

namespace FAU\Staging\Data;

use FAU\RecordData;

class StudyTerm extends RecordData
{
    protected const tableName = 'study_terms';
    protected const hasSequence = false;
    protected const keyTypes = [
        'period_id' => 'integer',
    ];
    protected const otherTypes = [
        'term_year' => 'integer',
        'term_type_id' => 'integer',
    ];

    protected int $period_id;
    protected int $term_year;
    protected int $term_type_id;

    public function __construct(
        int $term_year,
        int $term_type_id,
        int $period_id,
    )
    {
        $this->period_id = $period_id;
        $this->term_year = $term_year;
        $this->term_type_id = $term_type_id;
    }

    public static function model(): self
    {
        return new self(0, 0, 0);
    }

    /**
     * @return int
     */
    public function getPeriodId() : int
    {
        return $this->period_id;
    }

    /**
     * @return int
     */
    public function getTermYear() : int
    {
        return $this->term_year;
    }

    /**
     * @return int
     */
    public function getTermTypeId() : int
    {
        return $this->term_type_id;
    }

}