<?php declare(strict_types=1);

namespace FAU\Staging\Data;

/**
 * Record of the education table
 */
class Education extends DipData
{
    protected const tableName = 'campo_specific_educations';
    protected const hasSequence = false;
    protected const keyTypes = [
        'idm_uid' => 'text',
        'type' => 'text',
        'key' => 'text'
    ];
    protected const otherTypes = [
        'value' => 'text',
        'key_title' => 'text',
        'value_text' => 'text',
    ];

    protected string $idm_uid = '';
    protected string $type = '';
    protected string $key = '';
    protected string $value = '';
    protected ?string $key_title;
    protected ?string $value_text;

    public function __construct(
        string $idm_uid,
        string $type,
        string $key,
        string $value,
        ?string $key_title,
        ?string $value_text
    )
    {
        $this->idm_uid = $idm_uid;
        $this->type = $type;
        $this->key = $key;
        $this->value = $value;
        $this->key_title = $key_title;
        $this->value_text = $value_text;
    }

    public function info() : string
    {
        return ('idm_uid: ' . $this->idm_uid . ' | type: ' . $this->type . ' | key: ' . $this->key . ' | value: ' . $this->value);
    }

    public static function model(): self
    {
        return new self('', '', '', '', null, null);
    }

    public function row() : array {
        return array_merge([
            'idm_uid' => $this->idm_uid,
            'type' => $this->type,
            'key' => $this->key,
            'value' => $this->value,
            'key_title' => $this->key_title,
            'value_text' => $this->value_text
        ], $this->getDipData());
    }

    public static function from(array $row): self
    {
        return (new self(
            $row['idm_uid'] ?? '',
            $row['type'] ?? '',
            $row['key'] ?? '',
            $row['value'] ?? '',
            $row['key_title'] ?? null,
            $row['value_text'] ?? null
            )
        )->withDipData($row);
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