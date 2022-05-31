<?php declare(strict_types=1);

namespace FAU\Staging\Data;

/**
 * Record of the education table
 */
class Education extends DipData
{
    protected string $idm_uid = '';
    protected string $type = '';
    protected string $key = '';
    protected string $value = '';
    protected ?string $key_title;
    protected ?string $value_text;


    public function info() : string
    {
        return ('idm_uid: ' . $this->idm_uid . ' | type: ' . $this->type . ' | key: ' . $this->key . ' | value: ' . $this->value);
    }

    public static function model(): self
    {
        return new self;
    }

    public static function getTableName() : string
    {
        return 'campo_specific_educations';
    }

    public static function getTableKeyTypes() : array
    {
        return [
            'idm_uid' => 'text',
            'type' => 'text',
            'key' => 'text'
        ];
    }

    public static function getTableOtherTypes() : array
    {
        return array_merge(parent::getTableOtherTypes(), [
            'value' => 'text',
            'key_title' => 'text',
            'value_text' => 'text',
        ]);
    }

    public function getTableRow() : array {
        return array_merge(parent::getTableRow(), [
            'idm_uid' => $this->idm_uid,
            'type' => $this->type,
            'key' => $this->key,
            'value' => $this->value,
            'key_title' => $this->key_title,
            'value_text' => $this->value_text
        ]);
    }

    public function withTableRow(array $row): self
    {
        $clone = parent::withTableRow($row);
        $clone->idm_uid = $row['idm_uid'] ?? '';
        $clone->type = $row['type'] ?? '';
        $clone->key =  $row['key'] ?? '';
        $clone->value = $row['value'] ?? '';
        $clone->key_title = $row['key_title'] ?? null;
        $clone->value_text = $row['value_text'] ?? null;
        return $clone;
    }

    /**
     * @return string
     */
    public function getIdmUid() : string
    {
        return $this->idm_uid;
    }

    /**
     * @return string
     */
    public function getType() : string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getKey() : string
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getValue() : string
    {
        return $this->value;
    }

    /**
     * @return string|null
     */
    public function getKeyTitle() : ?string
    {
        return $this->key_title;
    }

    /**
     * @return string|null
     */
    public function getValueText() : ?string
    {
        return $this->value_text;
    }
}