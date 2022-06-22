<?php  declare(strict_types=1);

namespace FAU\User\Data;

use FAU\RecordData;
use ILIAS\DI\Exceptions\Exception;
use FAU\Study\Data\Term;

class Person extends RecordData
{
    protected const tableName = 'fau_user_persons';
    protected const hasSequence = false;
    protected const keyTypes = [
        'user_id' => 'integer',
    ];
    protected const otherTypes = [
        'person_id' => 'integer',
        'employee' => 'text',
        'student' => 'text',
        'guest' => 'text',
        'doc_approval_date' => 'date',
        'doc_programmes_text' => 'text',
        'doc_programmes_code' => 'integer',
        'studydata' => 'clob',
        'orgdata' => 'clob'
    ];

    protected int $user_id;
    protected int $person_id;
    protected ?string $employee;
    protected ?string $student;
    protected ?string $guest;
    protected ?string $doc_approval_date;
    protected ?string $doc_programmes_text;
    protected ?string $doc_programmes_code;
    protected ?string $studydata;
    protected ?string $orgdata;

    protected array $studies = [];
    protected array $org_roles = [];


    public function __construct(
        int $user_id,
        int $person_id,
        ?string $employee,
        ?string $student,
        ?string $guest,
        ?string $doc_approval_date,
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
        $this->doc_approval_date = $doc_approval_date;
        $this->doc_programmes_text = $doc_programmes_text;
        $this->doc_programmes_code = $doc_programmes_code;
        $this->studydata = $studydata;
        $this->orgdata = $orgdata;

        if (isset($this->studydata)) {
            foreach ((array) json_decode($this->studydata, true) as $period => $studies) {
                foreach ((array) $studies as $index => $data) {
                    $study = new Study($data);
                    $this->studies[$period][sprintf('%02d.%02d', (int) $study->getStudynumber(), $index)] = $study;
                }
            }
        }

        if (isset($this->orgdata)) {
            foreach ((array) json_decode($this->orgdata, true) as $data) {
                $this->org_roles[] = new OrgRole($data);
            }
        }
    }

    public static function model(): self
    {
        return new self(0,0,null,null,null,
            null,null,null,null,null);
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
    public function getDocApprovalDate() : ?string
    {
        return $this->doc_approval_date;
    }

    public function getDocApprovalDateObject() : ?\ilDate
    {
        if (isset($this->doc_approval_date)) {
            try {
                return new \ilDate($this->doc_approval_date, IL_CAL_DATE);
            }
            catch (Exception $e) {}
        }
        return null;
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
     * Get the studies of a given term
     * @return Study[]
     */
    public function getStudiesOfTerm(?Term $term) : array
    {
        if (!isset($term) || !$term->isValid()) {
            return [];
        }
        return $this->studies[$term->toString()] ?? [];
    }

    /**
     * Get the maximum term that has study data for this person
     * @return Term|null
     */
    public function getMaxTerm() : ?Term
    {
        if (empty($this->studies)) {
            return null;
        }
        return Term::fromString(max(array_keys($this->studies)));
    }


    /**
     * @return OrgRole[]
     */
    public function getOrgRoles() : array
    {
        return $this->org_roles;
    }


    /**
     * @param int $user_id
    * @return Person
    */
    public function withUserId(int $user_id) : Person
    {
        $clone = clone $this;
        $clone->user_id = $user_id;
        return $clone;
    }

    /**
     * @param int $person_id
     * @return Person
     */
    public function withPersonId(int $person_id) : Person
    {
        $clone = clone $this;
        $clone->person_id = $person_id;
        return $clone;
    }

}