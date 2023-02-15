<?php  declare(strict_types=1);

namespace FAU\Study\Data;

use FAU\RecordData;

/**
 * Type of event (used for a value list)
 */
class EventType extends RecordData
{
    protected const tableName = 'fau_study_event_types';
    protected const hasSequence = false;
    protected const keyTypes = [
        'type_de' => 'text',
    ];
    protected const otherTypes = [
        'type_en' => 'text',
        'eval_id' => 'text'
    ];

    protected string $type_de;
    protected ?string $type_en;
    protected ?string $eval_id;

    public function __construct(
        string $type_de,
        ?string $type_en,
        ?string $eval_id
    )
    {
        $this->type_de = $type_de;
        $this->type_en = $type_en;
        $this->eval_id = $eval_id;
    }

    public static function model(): self
    {
        return new self('', null, null);
    }


    /**
     * @return string
     */
    public function getTypeDe() : string
    {
        return $this->type_de;
    }

    /**
     * @return string|null
     */
    public function getTypeEn() : ?string
    {
        return $this->type_en;
    }

    /**
     * @return string|null
     */
    public function getEvalId() : ?string
    {
        return $this->eval_id;
    }

    /**
     * Get the translated title
     * @param string $lang_key
     * @return string
     */
    public function getTrans(string $lang_key) : string
    {
        if ($lang_key == 'en' && !empty($this->type_en)) {
            return $this->type_en;
        }
        return $this->type_de;
    }
}