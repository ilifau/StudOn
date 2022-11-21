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
        'term_type_id' => 'integer'
    ];

    public const STATUS_REGISTERED = 'registered';
    public const STATUS_NOT_REGISTERED = 'not_registered';
    public const STATUS_PASSED = 'passed';
    public const STATUS_FAILED = 'failed';

    protected int $course_id;
    protected int $person_id;
    protected ?int $module_id;
    protected string $status;
    protected int $term_year;
    protected int $term_type_id;


    public function __construct(
        int $course_id,
        int $person_id,
        ?int $module_id,
        string $status,
        int $term_year,
        int $term_type_id
    )
    {
        $this->course_id = $course_id;
        $this->person_id = $person_id;
        $this->module_id = $module_id;
        $this->status = $status;
        $this->term_year = $term_year;
        $this->term_type_id = $term_type_id;
    }

    public static function model(): self
    {
        return new self(0,0,0,'', 0,0);
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