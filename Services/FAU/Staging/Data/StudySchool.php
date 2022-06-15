<?php  declare(strict_types=1);

namespace FAU\Staging\Data;

use FAU\RecordData;

class StudySchool extends RecordData
{
    protected const tableName = 'study_schools';
    protected const hasSequence = false;
    protected const keyTypes = [
        'school_his_id' => 'integer',       // this ID corresponds with fau_studydata
    ];
    protected const otherTypes = [
        'school_uniquename' => 'text',
        'school_title' => 'text',
        'school_title_en' => 'text',
    ];

    protected int $school_his_id;
    protected string $school_uniquename;
    protected string $school_title;
    protected ?string $school_title_en;

    public function __construct(
        int $school_his_id,
        string $school_uniquename,
        string $school_title,
        ?string $school_title_en
    )
    {
        $this->school_his_id = $school_his_id;
        $this->school_uniquename = $school_uniquename;
        $this->school_title = $school_title;
        $this->school_title_en = $school_title_en;
    }

    public static function model(): self
    {
        return new self(0, '', '', null);
    }

    /**
     * @return int
     */
    public function getSchoolHisId() : int
    {
        return $this->school_his_id;
    }

    /**
     * @return string
     */
    public function getSchoolTitle() : string
    {
        return $this->school_title;
    }

    /**
     * @return string|null
     */
    public function getSchoolTitleEn() : ?string
    {
        return $this->school_title_en;
    }

    /**
     * @return string
     */
    public function getSchoolUniquename() : string
    {
        return $this->school_uniquename;
    }

}