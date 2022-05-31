<?php declare(strict_types=1);

namespace FAU\Staging\Data;

/**
 * Record of the campo_module_cos table
 */
class ModuleCos extends DipData
{
    protected int $module_id;
    protected int $cos_id;
    protected string $degree;
    protected string $subject;
    protected string $major;
    protected string $subject_indicator;
    protected string $version;

    public function info() : string
    {
        return ('module_id: ' . $this->module_id . 'cos_id:' . $this->cos_id .' | degree: ' . $this->degree . ' | subject: ' . $this->subject);
    }

    public static function model(): self
    {
        return new self;
    }

    public static function getTableName() : string
    {
        return 'campo_module_cos';
    }

    public static function getTableKeyTypes() : array
    {
        return [
            'module_id' => 'integer',
            'cos_id' => 'integer'
        ];
    }

    public static function getTableOtherTypes() : array
    {
        return array_merge(parent::getTableOtherTypes(), [
            'degree' => 'text',
            'subject' => 'text',
            'major' => 'text',
            'subject_indicator' => 'text',
            'version' => 'text'
        ]);
    }

    public function getTableRow() : array {
        return array_merge(parent::getTableRow(), [
            'module_id' => $this->module_id,
            'cos_id' => $this->cos_id,
            'degree' => $this->degree,
            'subject' => $this->subject,
            'major' => $this->major,
            'subject_indicator' => $this->subject_indicator,
            'version' => $this->version
        ]);
    }

    public function withTableRow(array $row) : self
    {
        $clone = parent::withTableRow($row);
        $clone->module_id = (int) $row['module_id'];
        $clone->cos_id = (int) $row['cos_id'];
        $clone->degree =  $row['degree'] ?? null;
        $clone->subject = $row['subject'] ?? null;
        $clone->major = $row['major'] ?? null;
        $clone->subject_indicator = $row['subject_indicator'] ?? null;
        $clone->version = $row['version'] ?? null;
        return $clone;
    }

    /**
     * @return int
     */
    public function getModuleId() : int
    {
        return $this->module_id;
    }

    /**
     * @return int
     */
    public function getCosId() : int
    {
        return $this->cos_id;
    }

    /**
     * @return string
     */
    public function getDegree() : string
    {
        return $this->degree;
    }

    /**
     * @return string
     */
    public function getSubject() : string
    {
        return $this->subject;
    }

    /**
     * @return string
     */
    public function getMajor() : string
    {
        return $this->major;
    }

    /**
     * @return string
     */
    public function getSubjectIndicator() : string
    {
        return $this->subject_indicator;
    }

    /**
     * @return string
     */
    public function getVersion() : string
    {
        return $this->version;
    }
}