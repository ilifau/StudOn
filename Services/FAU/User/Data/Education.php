<?php declare(strict_types=1);

namespace FAU\User\Data;

use FAU\RecordData;

class Education extends RecordData
{
    protected int $user_id;
    protected string $type;
    protected string $key;
    protected string $value;
    protected ?string $key_title;
    protected ?string $value_text;


    public function __construct(
        int $user_id,
        string $type,
        string $key,
        string $value,
        ?string $key_title,
        ?string $value_text
    )
    {
        $this->user_id = $user_id;
        $this->type = $type;
        $this->key = $key;
        $this->value = $value;
        $this->key_title = $key_title;
        $this->value_text = $value_text;
    }
    
    public function info() : string
    {
        return ('user_id: ' . $this->user_id . ' | type: ' . $this->type . ' | key: ' . $this->key . ' | value: ' . $this->value);
    }

    public static function model(): self
    {
        return new self(0,'','','', null, null);
    }

    public static function getTableName() : string
    {
        return 'fau_user_educations';
    }

    public static function getTableKeyTypes() : array
    {
        return [
            'user_id' => 'integer',
            'type' => 'text',
            'key' => 'text'
        ];
    }

    public static function getTableOtherTypes() : array
    {
        return [
            'value' => 'text',
            'key_title' => 'text',
            'value_text' => 'text'
        ];
    }

    public function getTableRow() : array
    {
        return [
            'user_id' => $this->user_id,
            'type' => $this->type,
            'key' => $this->key,
            'value' => $this->value,
            'key_title' => $this->key_title,
            'value_text' => $this->value_text
        ];
    }

    public function withTableRow(array $row) : self
    {
        $clone = clone $this;
        $clone->user_id = (int) $row['user_id'];
        $clone->type = $row['type'] ?? '';
        $clone->key =  $row['key'] ?? '';
        $clone->value = $row['value'] ?? '';
        $clone->key_title = $row['key_title'] ?? null;
        $clone->value_text = $row['value_text'] ?? null;
        return $clone;
    }



    /**
     * User id to which this education is assigned
     */
    public function getUserId() : int
    {
        return $this->user_id;
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
     * Value of the edication, e.g. "E2"
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
     * Optional key of the value for better undestanding
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