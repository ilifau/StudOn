<?php  declare(strict_types=1);

namespace FAU\Study\Data;

use FAU\RecordData;

class StudyDegree extends RecordData
{
    protected const tableName = 'fau_study_degrees';
    protected const hasSequence = false;
    protected const keyTypes = [
        'degree_his_id' => 'integer',       // this ID corresponds with fau_studydata
    ];
    protected const otherTypes = [
        'degree_uniquename' => 'text',
        'degree_title' => 'text',
        'degree_title_en' => 'text',
    ];
    
    protected int $degree_his_id;
    protected string $degree_uniquename;
    protected string $degree_title;
    protected ?string $degree_title_en;

    public function __construct(
        int $degree_his_id,
        string $degree_uniquename,
        string $degree_title,
        ?string $degree_title_en
    )
    {
        $this->degree_his_id = $degree_his_id;
        $this->degree_uniquename = $degree_uniquename;
        $this->degree_title = $degree_title;
        $this->degree_title_en = $degree_title_en;
    }

    public static function model(): self
    {
        return new self(0, '', '', null);
    }

    /**
     * @return int
     */
    public function getDegreeHisId() : int
    {
        return $this->degree_his_id;
    }

    /**
     * @return string
     */
    public function getDegreeTitle() : string
    {
        return $this->degree_title;
    }

    /**
     * @return string|null
     */
    public function getDegreeTitleEn() : ?string
    {
        return $this->degree_title_en;
    }

    /**
     * @return string
     */
    public function getDegreeUniquename() : string
    {
        return $this->degree_uniquename;
    }

}