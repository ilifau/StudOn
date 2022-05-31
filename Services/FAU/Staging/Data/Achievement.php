<?php declare(strict_types=1);

namespace FAU\Staging\Data;

class Achievement extends DipData
{
    protected const tableName = 'campo_achievements';
    protected const hasSequence = false;
    protected const keyTypes = [
        'requirement_id' => 'integer',
        'person_id' => 'text'
        // idm_uid is ignored
    ];
    protected const otherTypes = [
    ];

    protected int $requirement_id;
    protected int $person_id;

    public function __construct(
        int $requirement_id,
        int $person_id
    ) {
        $this->requirement_id = $requirement_id;
        $this->person_id = $person_id;
    }

    public static function model(): self
    {
        return new self(0,0);
    }

    public static function from(array $row): self
    {
        return (new self(
            (int) $row['requirement_id'],
            (int) $row['person_id'],
            )
        )->withDipData($row);
    }

    public function row() : array {
        return array_merge([
            'requirement_id' => $this->requirement_id,
            'person_id' => $this->person_id,
        ], $this->getDipData());
    }

    public function info() : string
    {
        return ('requirement_id: ' . $this->requirement_id . ' | person_id: ' . $this->person_id);
    }

    /**
     * @return int
     */
    public function getRequirementId() : int
    {
        return $this->requirement_id;
    }

    /**
     * @return int
     */
    public function getPersonId() : int
    {
        return $this->person_id;
    }

}