<?php  declare(strict_types=1);

namespace FAU\Study\Data;

use FAU\RecordData;

class StudyType extends RecordData
{
    protected const tableName = 'fau_study_types';
    protected const hasSequence = false;
    protected const keyTypes = [
        'type_uniquename' => 'text',
    ];
    protected const otherTypes = [
        'type_title' => 'text',
        'type_title_en' => 'text',
    ];

    protected string $type_uniquename;
    protected string $type_title;
    protected ?string $type_title_en;

    public function __construct(
        string $type_uniquename,
        string $type_title,
        ?string $type_title_en
    )
    {
        $this->type_uniquename = $type_uniquename;
        $this->type_title = $type_title;
        $this->type_title_en = $type_title_en;
    }

    public static function model(): self
    {
        return new self('','','',null);
    }

    /**
     * @return string
     */
    public function getTypeUniquename() : string
    {
        return $this->type_uniquename;
    }

    /**
     * @param string $lang language code ('en)
     * @return string
     */
    public function getTypeTitle(string $lang = '') : string
    {
        if ($lang == 'en' && !empty($this->type_title_en)) {
            return $this->type_title_en;
        }
        return $this->type_title;
    }
}