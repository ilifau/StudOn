<?php  declare(strict_types=1);

namespace FAU\Staging\Data;

use FAU\RecordData;

class ExamExaminer extends RecordData
{
    protected const tableName = 'campo_exam_examiner';
    protected const hasSequence = false;
    protected const keyTypes = [
        'porgnr' => 'integer',
        'person_id' => 'integer',
    ];
    protected const otherTypes = [
    ];

    protected int $porgnr;
    protected int $person_id;

    public function __construct(
        int $porgnr,
        int $person_id
    )
    {
        $this->porgnr = $porgnr;
        $this->person_id = $person_id;
    }

    public static function model(): self
    {
        return new self(0,0);
    }

    /**
     * @return int
     */
    public function getPorgnr() : int
    {
        return $this->porgnr;
    }

    /**
     * @return int
     */
    public function getPersonId() : int
    {
        return $this->person_id;
    }
}