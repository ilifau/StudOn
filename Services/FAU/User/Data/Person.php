<?php  declare(strict_types=1);

namespace FAU\Staging\Data;

use FAU\RecordData;

class Person extends RecordData
{
    protected const tableName = '';
    protected const hasSequence = false;
    protected const keyTypes = [
        'user_id' => 'integer',
    ];
    protected const otherTypes = [
        'person_id' => 'integer',
        'employee' => 'text',
        'student' => 'text',
        'guest' => 'text',
        'doc_programmes_text' => 'text',
        'doc_programmes_code' => 'integer',
        'studydata' => 'clob',
        'orgdata' => 'clob'
    ];
    protected int $user_id;
    /**
     * @var int
     */
    protected int $person_id;
    protected ?string $employee;
    protected ?string $student;
    protected ?string $guest;
    protected ?string $doc_programmes_text;
    protected ?string $doc_programmes_code;
    protected ?string $studydata;
    protected ?string $orgdata;

    public function __construct(
        int $user_id,
        int $person_id,
        ?string $employee,
        ?string $student,
        ?string $guest,
        ?string $doc_programmes_text,
        ?string $doc_programmes_code,
        ?string $studydata,
        ?string $orgdata
    )
    {
        $this->user_id = $user_id;
        $this->person_id = $person_id;
        $this->employee = $employee;
        $this->student = $student;
        $this->guest = $guest;
        $this->doc_programmes_text = $doc_programmes_text;
        $this->doc_programmes_code = $doc_programmes_code;
        $this->studydata = $studydata;
        $this->orgdata = $orgdata;
    }

    public static function model(): self
    {
        return new self(0,0,null,null,null,
            null,null,null,null);
    }

    /**
     * @return int
     */
    public function getUserId() : int
    {
        return $this->user_id;
    }

    /**
     * @return int
     */
    public function getPersonId() : int
    {
        return $this->person_id;
    }

    /**
     * @return string|null
     */
    public function getEmployee() : ?string
    {
        return $this->employee;
    }

    /**
     * @return string|null
     */
    public function getStudent() : ?string
    {
        return $this->student;
    }

    /**
     * @return string|null
     */
    public function getGuest() : ?string
    {
        return $this->guest;
    }

    /**
     * @return string|null
     */
    public function getDocProgrammesText() : ?string
    {
        return $this->doc_programmes_text;
    }

    /**
     * @return string|null
     */
    public function getDocProgrammesCode() : ?string
    {
        return $this->doc_programmes_code;
    }

    /**
     * @return string|null
     */
    public function getStudydata() : ?string
    {
        return $this->studydata;
    }

    /**
     * @return string|null
     */
    public function getOrgdata() : ?string
    {
        return $this->orgdata;
    }

    /**
     * @param string|null $employee
     * @return Person
     */
    public function withEmployee(?string $employee) : Person
    {
        $clone = clone $this;
        $clone->employee = $employee;
        return $clone;
    }

    /**
     * @param string|null $student
     * @return Person
     */
    public function withStudent(?string $student) : Person
    {
        $clone = clone $this;
        $clone->student = $student;
        return $clone;
    }

    /**
     * @param string|null $guest
     * @return Person
     */
    public function withGuest(?string $guest) : Person
    {
        $clone = clone $this;
        $clone->guest = $guest;
        return $clone;
    }

    /**
     * @param string|null $doc_programmes_text
     * @return Person
     */
    public function withDocProgrammesText(?string $doc_programmes_text) : Person
    {
        $clone = clone $this;
        $clone->doc_programmes_text = $doc_programmes_text;
        return $clone;
    }

    /**
     * @param string|null $doc_programmes_code
     * @return Person
     */
    public function withDocProgrammesCode(?string $doc_programmes_code) : Person
    {
        $clone = clone $this;
        $clone->doc_programmes_code = $doc_programmes_code;
        return $clone;
    }

    /**
     * @param string|null $studydata
     * @return Person
     */
    public function withStudydata(?string $studydata) : Person
    {
        $clone = clone $this;
        $clone->studydata = $studydata;
        return $clone;
    }

    /**
     * @param string|null $orgdata
     * @return Person
     */
    public function withOrgdata(?string $orgdata) : Person
    {
        $clone = clone $this;
        $clone->orgdata = $orgdata;
        return $clone;
    }
}