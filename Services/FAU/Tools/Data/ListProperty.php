<?php

namespace FAU\Tools\Data;

class ListProperty
{
    private ?string $label = null;
    private ?string $value = null;
    
    private bool $newline = true;
    private bool $alert = false;
    private ?bool $link = null;
    
    public function __construct(
        ?string $label,
        ?string $value
    ) {
        $this->label = $label;
        $this->value = $value;
    }

    /**
     * @return string|null
     */
    public function getLabel() : ?string
    {
        return $this->label;
    }

    /**
     * @return string|null
     */
    public function getValue() : ?string
    {
        return $this->value;
    }

    /**
     * Get the info as a string
     */
    public function getString() : string
    {
        if (empty($this->label)) {
            return (string) $this->value;
        }
        elseif (empty($this->value)) {
            return (string) $this->label;
        }
        else {
            return (string) $this->label . ': ' . (string) $this->value;
        }
    }

    /**
     * Get an array as being used by the Object List GUIs
     */
    public function getArray() : array
    {
        return [
            'alert' => $this->alert,
            'newline' => $this->newline,
            'property' => $this->label,
            'propertyNameVisible' => !empty($this->label),
            'value' => $this->value,
            'link' => $this->link
        ];
    }

    /**
     * @param bool $newline
     * @return ListProperty
     */
    public function withNewline(bool $newline) : ListProperty
    {
        $clone = clone $this;
        $clone->newline = $newline;
        return $clone;
    }

    /**
     * @param bool $alert
     * @return ListProperty
     */
    public function withAlert(bool $alert) : ListProperty
    {
        $clone = clone $this;
        $clone->alert = $alert;
        return $clone;
    }

    /**
     * @param bool|null $link
     * @return ListProperty
     */
    public function withLink(?bool $link) : ListProperty
    {
        $clone = clone $this;
        $clone->link = $link;
        return $clone;
    }
}