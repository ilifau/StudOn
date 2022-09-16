<?php declare(strict_types=1);

namespace FAU\User\Data;

use FAU\RecordData;
use FAU\Staging\Data\DipData;

class Education extends RecordData
{
    protected const tableName = 'fau_user_educations';
    protected const hasSequence = false;
    protected const keyTypes = [
        'id' => 'integer',
    ];
    protected const otherTypes = [
        'semester' => 'text',
        'person_id' => 'integer',
        'examnr' => 'text',
        'date_of_work' => 'text',
        'examname' => 'text',
        'orgunit' => 'text',
        'additional_text' => 'text',
    ];
    protected int $id;
    protected ?string $semester;
    protected ?int $person_id;
    protected ?string $examnr;
    protected ?string $date_of_work;
    protected ?string $examname;
    protected ?string $orgunit;
    protected ?string $additional_text;

    public function __construct(
        int $id,
        ?string $semester,
        ?int $person_id,
        ?string $examnr,
        ?string $date_of_work,
        ?string $examname,
        ?string $orgunit,
        ?string $additional_text
    )
    {
        $this->id = $id;
        $this->semester = $semester;
        $this->person_id = $person_id;
        $this->examnr = $examnr;
        $this->date_of_work = $date_of_work;
        $this->examname = $examname;
        $this->orgunit = $orgunit;
        $this->additional_text = $additional_text;
    }

    public static function model(): self
    {
        return new self(0,null,null,null,null,null,null,null);
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getSemester(): ?string
    {
        return $this->semester;
    }

    /**
     * @return int|null
     */
    public function getPersonId(): ?int
    {
        return $this->person_id;
    }

    /**
     * @return string|null
     */
    public function getExamnr(): ?string
    {
        return $this->examnr;
    }

    /**
     * @return string|null
     */
    public function getDateOfWork(): ?string
    {
        return $this->date_of_work;
    }

    /**
     * @return string|null
     */
    public function getExamname(): ?string
    {
        return $this->examname;
    }

    /**
     * @return string|null
     */
    public function getOrgunit(): ?string
    {
        return $this->orgunit;
    }

    /**
     * @return string|null
     */
    public function getAdditionalText(): ?string
    {
        return $this->additional_text;
    }

}