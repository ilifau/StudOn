<?php  declare(strict_types=1);

namespace FAU\Study\Data;

use FAU\RecordData;

class StudyStatus extends RecordData
{
    protected const tableName = 'fau_study_status';
    protected const hasSequence = false;
    protected const keyTypes = [
        'status_his_id' => 'integer',
    ];
    protected const otherTypes = [
        'status_uniquename' => 'text',
        'status_title' => 'text',
        'status_title_en' => 'text',
    ];

    protected int $status_his_id;
    protected string $status_uniquename;
    protected string $status_title;
    protected ?string $status_title_en;

    public function __construct(
        int $status_his_id,
        string $status_uniquename,
        string $status_title,
        ?string $status_title_en
    )
    {
        $this->status_his_id = $status_his_id;
        $this->status_uniquename = $status_uniquename;
        $this->status_title = $status_title;
        $this->status_title_en = $status_title_en;
    }

    public static function model(): self
    {
        return new self(0,'','',null);
    }

    /**
     * @return int
     */
    public function getStatusHisId() : int
    {
        return $this->status_his_id;
    }

    /**
     * @return string
     */
    public function getStatusUniquename() : string
    {
        return $this->status_uniquename;
    }

    /**
     * @return string
     */
    public function getStatusTitle() : string
    {
        return $this->status_title;
    }

    /**
     * @return string|null
     */
    public function getStatusTitleEn() : ?string
    {
        return $this->status_title_en;
    }
}