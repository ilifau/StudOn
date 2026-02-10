<?php  declare(strict_types=1);

namespace FAU\Staging\Data;

use FAU\RecordData;

class StudyField extends RecordData
{
    protected const tableName = 'study_fields';
    protected const hasSequence = false;
    protected const keyTypes = [
        'field_id' => 'integer',
    ];
    protected const otherTypes = [
        'field_uniquename' => 'text',
        'field_title' => 'text',
        'field_title_en' => 'text',
    ];

    protected int $field_id;
    protected string $field_uniquename;
    protected string $field_title;
    protected ?string $field_title_en;

    public function __construct(
        int $field_id,
        string $field_uniquename,
        string $field_title,
        ?string $field_title_en
    )
    {
        $this->field_id = $field_id;
        $this->field_uniquename = $field_uniquename;
        $this->field_title = $field_title;
        $this->field_title_en = $field_title_en;
    }

    public static function model(): self
    {
        return new self(0,'','',null);
    }

    /**
     * @return int
     */
    public function getFieldId() : int
    {
        return $this->field_id;
    }

    /**
     * @return string
     */
    public function getFieldUniquename() : string
    {
        return $this->field_uniquename;
    }

    /**
     * @return string
     */
    public function getFieldTitle() : string
    {
        return $this->field_title;
    }

    /**
     * @return string|null
     */
    public function getFieldTitleEn() : ?string
    {
        return $this->field_title_en;
    }
}