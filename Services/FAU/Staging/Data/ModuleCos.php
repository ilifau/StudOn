<?php declare(strict_types=1);

namespace FAU\Staging\Data;

/**
 * Record of the campo_module_cos table
 */
class ModuleCos extends DipData
{
    protected const tableName = 'campo_module_cos';
    protected const hasSequence = false;
    protected const keyTypes =  [
        'module_id' => 'integer',
        'cos_id' => 'integer'
    ];
    protected const otherTypes = [
        'degree' => 'text',
        'subject' => 'text',
        'major' => 'text',
        'subject_indicator' => 'text',
        'version' => 'text'
    ];

    protected int $module_id;
    protected int $cos_id;
    protected string $degree;
    protected string $subject;
    protected string $major;
    protected string $subject_indicator;
    protected string $version;

    public function __construct(
        int $module_id,
        int $cos_id,
        string $degree,
        string $subject,
        string $major,
        string $subject_indicator,
        string $version
    ) {

        $this->module_id = $module_id;
        $this->cos_id = $cos_id;
        $this->degree = $degree;
        $this->subject = $subject;
        $this->major = $major;
        $this->subject_indicator = $subject_indicator;
        $this->version = $version;
    }

    public static function model(): self
    {
        return new self(0,0,'','','','','');
    }

    public static function from(array $row) : self
    {
        return (new self (
            (int) $row['module_id'],
            (int) $row['cos_id'],
            $row['degree'] ?? null,
            $row['subject'] ?? null,
            $row['major'] ?? null,
            $row['subject_indicator'] ?? null,
            $row['version'] ?? null
            )
        )->withDipData($row);
    }

    public function row() : array {
        return array_merge([
            'module_id' => $this->module_id,
            'cos_id' => $this->cos_id,
            'degree' => $this->degree,
            'subject' => $this->subject,
            'major' => $this->major,
            'subject_indicator' => $this->subject_indicator,
            'version' => $this->version
        ], $this->getDipData());
    }

    public function info() : string
    {
        return ('module_id: ' . $this->module_id . 'cos_id:' . $this->cos_id .' | degree: ' . $this->degree . ' | subject: ' . $this->subject);
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