<?php

namespace FAU\Cond\Data;

use FAU\RecordData;
use FAU\Study\Data\Term;

class CosCondition extends RecordData
{
    protected const tableName = 'fau_cond_cos';
    protected const hasSequence = false;
    protected const keyTypes = [
        'id' => 'integer',
    ];
    protected const otherTypes = [
        'ilias_obj_id' => 'integer',
        'subject_his_id' => 'integer',
        'degree_his_id' => 'integer',
        'school_his_id' => 'integer',
        'enrolment_id' => 'integer',
        'min_semester' => 'integer',
        'max_semester' => 'integer',
        'ref_term_year' => 'integer',
        'ref_term_type_id' => 'integer',
    ];

    protected int $id;
    protected int $ilias_obj_id;
    protected ?int $subject_his_id;
    protected ?int $degree_his_id;
    protected ?int $school_his_id;
    protected ?int $enrolment_id;
    protected ?int $min_semester;
    protected ?int $max_semester;
    protected ?int $ref_term_year;
    protected ?int $ref_term_type_id;

    public function __construct(
        int $id,
        int $ilias_obj_id,
        ?int $subject_his_id,
        ?int $degree_his_id,
        ?int $school_his_id,
        ?int $enrolment_id,
        ?int $min_semester,
        ?int $max_semester,
        ?int $ref_term_year,
        ?int $ref_term_type_id
    )
    {
        $this->id = $id;
        $this->ilias_obj_id = $ilias_obj_id;
        $this->subject_his_id = $subject_his_id;
        $this->degree_his_id = $degree_his_id;
        $this->school_his_id = $school_his_id;
        $this->enrolment_id = $enrolment_id;
        $this->min_semester = $min_semester;
        $this->max_semester = $max_semester;
        $this->ref_term_year = $ref_term_year;
        $this->ref_term_type_id = $ref_term_type_id;
    }

    public static function model()
    {
        return new self(0,0,null,null,null,null,
            null,null,null,null);
    }

    /**
     * @return int
     */
    public function getId() : int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getIliasObjId() : int
    {
        return $this->ilias_obj_id;
    }

    /**
     * @return int|null
     */
    public function getSubjectHisId() : ?int
    {
        return $this->subject_his_id;
    }

    /**
     * @return int|null
     */
    public function getDegreeHisId() : ?int
    {
        return $this->degree_his_id;
    }

    /**
     * @return int|null
     */
    public function getSchoolHisId() : ?int
    {
        return $this->school_his_id;
    }

    /**
     * @return int|null
     */
    public function getEnrolmentId() : ?int
    {
        return $this->enrolment_id;
    }

    /**
     * @return int|null
     */
    public function getMinSemester() : ?int
    {
        return $this->min_semester;
    }

    /**
     * @return int|null
     */
    public function getMaxSemester() : ?int
    {
        return $this->max_semester;
    }

    /**
     * @return int|null
     */
    public function getRefTermYear() : ?int
    {
        return $this->ref_term_year;
    }

    /**
     * @return int|null
     */
    public function getRefTermTypeId() : ?int
    {
        return $this->ref_term_type_id;
    }

    /**
     * Get the reference term
     * @return Term|null
     */
    public function getRefTerm(): ?Term
    {
        if (isset($this->ref_term_year) && isset($this->ref_term_type_id)) {
            return new Term($this->ref_term_year, $this->ref_term_type_id);
        }
        return null;
    }

    /**
     * Get a clone for a new ilias object
     */
    public function cloneFor(int $obj_id) : self
    {
        $clone = clone $this;
        $clone->id = 0;
        $clone->ilias_obj_id = $obj_id;
        return $clone;
    }
}