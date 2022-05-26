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

    public function withTableRow(array $row) : self
    {
        $clone = parent::withTableRow($row);
        $clone->idm_uid = $row['idm_uid'] ?? '';
        $clone->type = $row['type'] ?? '';
        $clone->key =  $row['key'] ?? '';
        $clone->value = $row['value'] ?? '';
        $clone->key_title = $row['value'] ?? null;
        $clone->value_text = $row['value'] ?? null;
        return $clone;
    }

    /**
     * IDM user id to which this education is assigned
     */
    public function getIdmUid() : string
    {
        return $this->idm_uid;
    }

    /**
     * Type of the education, e.g. 'language'
     * This is used to filter the educations shown in courses of specific organizations
     */
    public function getType() : string
    {
        return $this->type;
    }

    /**
     * Key of the education, e.g. 'Spanish'
     */
    public function getKey() : string
    {
        return $this->key;
    }

    /**
     * Value of the education, e.g. "E2"
     */
    public function getValue() : string
    {
        return $this->value;
    }

    /**
     * Optional title of the key for better understanding
     */
    public function getKeyTitle() : ?string
    {
        return $this->key_title;
    }

    /**
     * Optional key of the value for better understanding
     */
    public function getValueText() : ?string
    {
        return $this->value_text;
    }

    /**
     * Get the title (label) of the property
     * Either the key title (if given) or the key
     */
    public function getTitle() : string
    {
        return $this->key_title ?? $this->key;
    }

    /**
     * Get the textual value
     * Either the value text (if given) or the value
     */
    public function getText() : string
    {
        return $this->value_text ?? $this->value;
    }
}