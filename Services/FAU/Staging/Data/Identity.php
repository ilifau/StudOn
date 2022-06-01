<?php  declare(strict_types=1);

namespace FAU\Staging\Data;

use FAU\RecordData;

class Identity extends RecordData
{
    protected const tableName = '';
    protected const hasSequence = false;
    protected const keyTypes = [
        'pk_persistent_id' => 'text',
    ];
    protected const otherTypes = [
        'last_change' => 'timestamp',
        'sn' => 'text',
        'given_name' => 'text',
        'mail' => 'text',
        'schac_gender' => 'text',
        'schac_personal_unique_code' => 'text',     // enthält Matrikelnummer
        'user_password' => 'text',
        'fau_employee' => 'text',
        'fau_student' => 'text',
        'fau_guest' => 'text',
        'fau_doc_approval_date' => 'text',
        'fau_doc_programmes_text' => 'text',
        'fau_doc_programmes_code' => 'text',
        'fau_campo_person_id' => 'integer',
        'fau_studydata' => 'clob',
        'fau_studydata_next' => 'clob',
        'fau_orgdata' => 'clob',
    ];
    protected string $pk_persistent_id;
    protected ?string $last_change;
    protected ?string $sn;
    protected ?string $given_name;
    protected ?string $mail;
    protected ?string $schac_gender;
    protected ?string $schac_personal_unique_code;
    protected ?string $user_password;
    protected ?string $fau_employee;
    protected ?string $fau_student;
    protected ?string $fau_guest;
    protected ?string $fau_doc_approval_date;
    protected ?string $fau_doc_programmes_text;
    protected ?string $fau_doc_programmes_code;
    protected ?int $fau_campo_person_id;
    protected ?string $fau_studydata;
    protected ?string $fau_studydata_next;
    protected ?string $fau_orgdata;

    public function __construct(
        string $pk_persistent_id,
        ?string $last_change,
        ?string $sn,
        ?string $given_name,
        ?string $mail,
        ?string $schac_gender,
        ?string $schac_personal_unique_code,
        ?string $user_password,
        ?string $fau_employee,
        ?string $fau_student,
        ?string $fau_guest,
        ?string $fau_doc_approval_date,
        ?string $fau_doc_programmes_text,
        ?string $fau_doc_programmes_code,
        ?int $fau_campo_person_id,
        ?string $fau_studydata,
        ?string $fau_studydata_next,
        ?string $fau_orgdata
    )
    {
        $this->pk_persistent_id = $pk_persistent_id;
        $this->last_change = $last_change;
        $this->sn = $sn;
        $this->given_name = $given_name;
        $this->mail = $mail;
        $this->schac_gender = $schac_gender;
        $this->schac_personal_unique_code = $schac_personal_unique_code;
        $this->user_password = $user_password;
        $this->fau_employee = $fau_employee;
        $this->fau_student = $fau_student;
        $this->fau_guest = $fau_guest;
        $this->fau_doc_approval_date = $fau_doc_approval_date;
        $this->fau_doc_programmes_text = $fau_doc_programmes_text;
        $this->fau_doc_programmes_code = $fau_doc_programmes_code;
        $this->fau_campo_person_id = $fau_campo_person_id;
        $this->fau_studydata = $fau_studydata;
        $this->fau_studydata_next = $fau_studydata_next;
        $this->fau_orgdata = $fau_orgdata;
    }

    public static function model(): self
    {
        return new self(0,null,null,null,null,null,
            null,null,null,null,null,
            null,null,null,null,
            null,null,null,);
    }

    /**
     * @return string
     */
    public function getPkPersistentId() : string
    {
        return $this->pk_persistent_id;
    }

    /**
     * @return string|null
     */
    public function getLastChange() : ?string
    {
        return $this->last_change;
    }

    /**
     * @return string|null
     */
    public function getSn() : ?string
    {
        return $this->sn;
    }

    /**
     * @return string|null
     */
    public function getGivenName() : ?string
    {
        return $this->given_name;
    }

    /**
     * @return string|null
     */
    public function getMail() : ?string
    {
        return $this->mail;
    }

    /**
     * @return string|null
     */
    public function getSchacGender() : ?string
    {
        return $this->schac_gender;
    }

    /**
     * @return string|null
     */
    public function getSchacPersonalUniqueCode() : ?string
    {
        return $this->schac_personal_unique_code;
    }

    /**
     * @return string|null
     */
    public function getUserPassword() : ?string
    {
        return $this->user_password;
    }

    /**
     * @return string|null
     */
    public function getFauEmployee() : ?string
    {
        return $this->fau_employee;
    }

    /**
     * @return string|null
     */
    public function getFauStudent() : ?string
    {
        return $this->fau_student;
    }

    /**
     * @return string|null
     */
    public function getFauGuest() : ?string
    {
        return $this->fau_guest;
    }

    /**
     * @return string|null
     */
    public function getFauDocApprovalDate() : ?string
    {
        return $this->fau_doc_approval_date;
    }

    /**
     * @return string|null
     */
    public function getFauDocProgrammesText() : ?string
    {
        return $this->fau_doc_programmes_text;
    }

    /**
     * @return string|null
     */
    public function getFauDocProgrammesCode() : ?string
    {
        return $this->fau_doc_programmes_code;
    }

    /**
     * @return int|null
     */
    public function getFauCampoPersonId() : ?int
    {
        return $this->fau_campo_person_id;
    }

    /**
     * @return string|null
     */
    public function getFauStudydata() : ?string
    {
        return $this->fau_studydata;
    }

    /**
     * @return string|null
     */
    public function getFauStudydataNext() : ?string
    {
        return $this->fau_studydata_next;
    }

    /**
     * @return string|null
     */
    public function getFauOrgdata() : ?string
    {
        return $this->fau_orgdata;
    }
}