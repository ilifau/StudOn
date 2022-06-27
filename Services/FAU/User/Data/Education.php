<?php declare(strict_types=1);

namespace FAU\User\Data;

use FAU\RecordData;

class Education extends RecordData
{
    protected const tableName = 'fau_user_educations';
    protected const hasSequence = false;
    protected const keyTypes = [
        'user_id' => 'integer',
        'type' => 'text',
        'key' => 'text'
    ];
    protected const otherTypes = [
        'value' => 'text',
        'key_title' => 'text',
        'value_text' => 'text'
    ];

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

    public static function model(): self
    {
        return new self(0,'','','', null, null);
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