<?php  declare(strict_types=1);

namespace FAU\Study\Data;

use FAU\RecordData;

class StudyForm extends RecordData
{
    protected const tableName = 'fau_study_forms';
    protected const hasSequence = false;
    protected const keyTypes = [
        'form_id' => 'integer',
    ];
    protected const otherTypes = [
        'form_uniquename' => 'text',
        'form_title' => 'text',
        'form_title_en' => 'text',
    ];

    protected int $form_id;
    protected string $form_uniquename;
    protected string $form_title;
    protected ?string $form_title_en;

    public function __construct(
        int $form_id,
        string $form_uniquename,
        string $form_title,
        ?string $form_title_en
    )
    {
        $this->form_id = $form_id;
        $this->form_uniquename = $form_uniquename;
        $this->form_title = $form_title;
        $this->form_title_en = $form_title_en;
    }

    public static function model(): self
    {
        return new self(0,'','',null);
    }

    /**
     * @return int
     */
    public function getFormId() : int
    {
        return $this->form_id;
    }

    /**
     * @return string
     */
    public function getFormUniquename() : string
    {
        return $this->form_uniquename;
    }

    /**
     * @param string $lang language code ('en)
     * @return string
     */
    public function getFormTitle(string $lang = '') : string
    {
        if ($lang == 'en' && !empty($this->form_title_en)) {
            return $this->form_title_en;
        }
        return $this->form_title;
    }
}