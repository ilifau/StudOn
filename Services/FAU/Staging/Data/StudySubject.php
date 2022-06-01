<?php  declare(strict_types=1);

namespace FAU\Staging\Data;

use FAU\RecordData;

class StudySubject extends RecordData
{
    protected const tableName = 'study_subjects';
    protected const hasSequence = false;
    protected const keyTypes = [
        'subject_his_id' => 'integer',       // this ID corresponds with fau_studydata
    ];
    protected const otherTypes = [
        'subject_title' => 'text',
        'subject_title_en' => 'text',
        'subject_uniquename' => 'text',
    ];
    
    protected int $subject_his_id;
    protected string $subject_title;
    protected ?string $subject_title_en;
    protected string $subject_uniquename;

    public function __construct(
        int $subject_his_id,
        string $subject_title,
        ?string $subject_title_en,
        string $subject_uniquename
    )
    {
        $this->subject_his_id = $subject_his_id;
        $this->subject_title = $subject_title;
        $this->subject_title_en = $subject_title_en;
        $this->subject_uniquename = $subject_uniquename;
    }

    public static function model(): self
    {
        return new self(0, '', null, '');
    }

    /**
     * @return int
     */
    public function getSubjectHisId() : int
    {
        return $this->subject_his_id;
    }

    /**
     * @return string
     */
    public function getSubjectTitle() : string
    {
        return $this->subject_title;
    }

    /**
     * @return string|null
     */
    public function getSubjectTitleEn() : ?string
    {
        return $this->subject_title_en;
    }

    /**
     * @return string
     */
    public function getSubjectUniquename() : string
    {
        return $this->subject_uniquename;
    }

}