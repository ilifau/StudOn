<?php  declare(strict_types=1);

namespace FAU\Study\Data;

use FAU\RecordData;

class Instructor extends RecordData
{
    protected const tableName = 'fau_study_instructors';
    protected const hasSequence = false;
    protected const keyTypes = [
        'planned_dates_id' => 'integer',
        'person_id' => 'integer',
    ];
    protected const otherTypes = [
    ];

    protected int $planned_dates_id;
    protected int $person_id;

    public function __construct(
        int $planned_dates_id,
        int $person_id
    )
    {
        $this->planned_dates_id = $planned_dates_id;
        $this->person_id = $person_id;
    }

    public static function model(): self
    {
        return new self(0,0);
    }

    /**
     * @return int
     */
    public function getPlannedDatesId() : int
    {
        return $this->planned_dates_id;
    }

    /**
     * @return int
     */
    public function getPersonId() : int
    {
        return $this->person_id;
    }
}