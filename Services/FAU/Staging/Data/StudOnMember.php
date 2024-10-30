<?php  declare(strict_types=1);

namespace FAU\Staging\Data;

use FAU\RecordData;

class StudOnMember extends RecordData
{
    protected const tableName = 'studon_members';
    protected const hasSequence = false;
    protected const keyTypes = [
        'course_id' => 'integer',
        'person_id' => 'integer',
    ];
    protected const otherTypes = [
        'module_id' => 'integer',
        'status' => 'text',
        'term_year' => 'integer',
        'term_type_id' => 'integer', 
        'status_changed' => 'datetime'
    ];

    public const STATUS_REGISTERED = 'registered';
    public const STATUS_PASSED = 'passed';

    protected int $course_id;
    protected int $person_id;
    protected ?int $module_id;
    protected string $status;
    protected int $term_year;
    protected int $term_type_id;
    protected ?string $status_changed;


    public function __construct(
        int $course_id,
        int $person_id,
        ?int $module_id,
        string $status,
        int $term_year,
        int $term_type_id,
        ?string $status_changed
    )
    {
        $this->course_id = $course_id;
        $this->person_id = $person_id;
        $this->module_id = $module_id;
        $this->status = $status;
        $this->term_year = $term_year;
        $this->term_type_id = $term_type_id;
        $this->status_changed = $status_changed;
    }

    public static function model(): self
    {
        return new self(0,0,0,'', 0,0, null);
    }

    /**
     * @return int
     */
    public function getCourseId() : int
    {
        return $this->course_id;
    }

    /**
     * @return int
     */
    public function getPersonId() : int
    {
        return $this->person_id;
    }

    /**
     * @return int|null
     */
    public function getModuleId() : ?int
    {
        return $this->module_id;
    }

    /**
     * @return string
     */
    public function getStatus() : string
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getStatusChanged() : ?string
    {
        return $this->status_changed;
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

    /**
     * @param string $status
     * @return StudOnMember
     */
    public function withStatus(string $status): StudOnMember
    {
        $clone = clone $this;
        $clone->status = $status;
        return $clone;
    }

    /**
     * @param string $status
     * @return StudOnMember
     */
    public function withStatusChanged(?string $status_changed): StudOnMember
    {
        $clone = clone $this;
        $clone->status_changed = $status_changed;
        return $clone;
    }
}