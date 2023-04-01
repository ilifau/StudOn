<?php

namespace FAU\Cond\Data;

use FAU\RecordData;

/**
 * Restictions text for an event or module
 * The content is the actual text
 * The additional flags can be used to filter the texts
 */
class RestrictionText extends RecordData
{
    /** only used for the hash() function */
    protected const otherTypes = [
        'content' => 'text',
        'is_module' => 'bool',
        'is_fitting' => 'bool',
        'is_passed' => 'bool',
        'is_selected' => 'bool',
    ];

    protected string $content;
    protected bool $is_module;
    protected bool $is_fitting;
    protected bool $is_passed;
    protected bool $is_selected;

    public function __construct(
        string $content,
        bool $is_module,
        bool $is_fitting,
        bool $is_passed,
        bool $is_selected
    ) {
        $this->content = $content;
        $this->is_module = $is_module;
        $this->is_fitting = $is_fitting;
        $this->is_passed = $is_passed;
        $this->is_selected = $is_selected;
    }

    public static function model()
    {
        return new self('', 0,0,0,0);
    }

    /**
     * Text for the event or module
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * True if the text is related to a module (not to an event)
     */
    public function isModule(): bool
    {
        return $this->is_module;
    }

    /**
     * True if the module or events fits for the user's courses of study
     */
    public function isFitting(): bool
    {
        return $this->is_fitting;
    }

    /**
     * True if related restrictions are passed
     */
    public function isPassed(): bool
    {
        return $this->is_passed;
    }

    /**
     * True if the text is related to the seleted module
     */
    public function isSelected(): bool
    {
        return $this->is_selected;
    }
}