<?php  declare(strict_types=1);

namespace FAU\Study\Data;

use FAU\RecordData;

class DocProgramme extends RecordData
{
    protected const tableName = 'fau_study_doc_progs';
    protected const hasSequence = false;
    protected const keyTypes = [
        'prog_code' => 'text',
    ];
    protected const otherTypes = [
        'prog_text' => 'text',
        'prog_end_date' => 'text',
    ];

    protected string $prog_code;
    protected string $prog_text;
    protected string $prog_end_date;

    public function __construct(
        string $prog_code,
        string $prog_text,
        string $prog_end_date
    )
    {
        $this->prog_code = $prog_code;
        $this->prog_text = $prog_text;
        $this->prog_end_date = $prog_end_date;
    }

    public static function model(): self
    {
        return new self('','','');
    }

    /**
     * @return string
     */
    public function getProgCode() : string
    {
        return $this->prog_code;
    }

    /**
     * @return string
     */
    public function getProgText() : string
    {
        return $this->prog_text;
    }

    /**
     * @return string
     */
    public function getProgEndDate() : string
    {
        return $this->prog_end_date;
    }
}