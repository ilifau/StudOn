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
    protected ?string $degree;
    protected ?string $subject;
    protected ?string $major;
    protected ?string $subject_indicator;
    protected ?string $version;

    public function __construct(
        int $module_id,
        int $cos_id,
        ?string $degree,
        ?string $subject,
        ?string $major,
        ?string $subject_indicator,
        ?string $version
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
        return new self(0,0,null, null, null, null, null);
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
     * @return string|null
     */
    public function
    getDegree() : ?string
    {
        return $this->degree;
    }

    /**
     * @return string|null
     */
    public function getSubject() : ?string
    {
        return $this->subject;
    }

    /**
     * @return string|null
     */
    public function getMajor() : ?string
    {
        return $this->major;
    }

    /**
     * @return string|null
     */
    public function getSubjectIndicator() : ?string
    {
        return $this->subject_indicator;
    }

    /**
     * @return string|null
     */
    public function getVersion() : ?string
    {
        return $this->version;
    }

}