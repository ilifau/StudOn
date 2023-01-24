<?php

namespace FAU\Tools;

use ILIAS\DI\Container;
use Throwable;

/**
 * Functions to display formatted data
 * All functions have a parameter to indicate whether HTML can be used or not
 */
class Format
{
    protected Container $dic;

    public function __construct(Container $dic)
    {
        $this->dic = $dic;
    }

    /**
     * Format a text as a label for a following list
     * @param string|null $prefix
     * @param string|null $text
     * @param string|null $addition
     * @param bool $html
     * @return string
     */
    public function label(?string $prefix, ?string $text, ?string $addition, bool $html = true)
    {
        if (!empty($prefix) && !empty($text)) {
            $prefix .= ' ';
        }
        if (!empty($addition)) {
            $addition = ' (' . $addition . ')';
        }

        if ($html) {
            return $prefix . "<strong>" . $text . "</strong>" . $addition . ": \n";
        }
        else {
            return $prefix . $text . $addition . ": \n";
        }
    }

    /**
     * Format a list of texts for display
     * @param string[] $texts
     * @param bool $html    use HTML to format
     * @param bool $wide    separate the elements with an additional newline if not formatted by html
     */
    public function list(array $texts, bool $html = true, bool $wide = false)
    {
        if (empty($texts)) {
            return '';
        }
        elseif ($html) {
            foreach ($texts as $index => $element) {
                $texts[$index] = '<li>' . $element . '</li>';
            }
            return '<ul>' . implode("\n", $texts) . '</ul>';
        }
        elseif ($wide) {
            return implode("\n\n", $texts);
        }
        else {
            return implode(";\n", $texts);
        }
    }

    /**
     * Format a satisfied / not satisfied result of a check
     * @param bool $result
     * @param bool $html
     */
    public function check(bool $result, bool $html)
    {
        if ($html) {
            return $result ?
                '<span style="font-weight: bold; color: green;">✓</span>' :
                '<span style="font-weight: bold; color: red;">✗</span>';
        }
        else {
            return $result ? '✓' : '✗';
        }
    }

    /**
     * Format a text with an optional highlight
     *
     * @param bool $result
     * @param bool $html
     */
    public function text(string $text, bool $highlight, bool $html)
    {
        if ($html) {
            return $highlight ?
                '<strong>' . $text . '</strong>' :
                $text;
        }
        else {
            return $text;
        }
    }
}