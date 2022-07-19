<?php

/* Copyright (c) 2016 Timon Amstutz <timon.amstutz@ilub.unibe.ch> Extended GPL, see docs/LICENSE */

namespace ILIAS\UI\Component\Item;

use \ILIAS\UI\Component\Symbol\Icon\Icon;
use \ILIAS\UI\Component\Image\Image;
use \ILIAS\Data\Color;

/**
 * Interface Standard Item
 * @package ILIAS\UI\Component\Panel\Listing
 */
interface Standard extends Item
{
    /**
     * Set a color
     */
    public function withColor(Color $a_color) : Item;

    /**
     * Return the given color
     */
    public function getColor() : ?Color ;

    /**
     * Set image as lead
     */
    public function withLeadImage(Image $image) : Item;

    /**
     * Set icon as lead
     */
    public function withLeadIcon(Icon $icon) : Item;

    /**
     * Set image as lead
     */
    public function withLeadText(string $text) : Item;

    // fau: studySearch - define checkbox functions
    /**
     * Set value for a checkbox
     */
    public function withCheckbox(string $name, ?string $value = null) : Item;

    /**
     * Get the name for a checkbox
     */
    public function getCheckboxName() : ?string;

    /**
     * Get the value for a checkbox
     */
    public function getCheckboxValue() : ?string;
    // fau.

    /**
     * Reset lead to null
     */
    public function withNoLead() : Item;

    /**
     * @return null|string|\ILIAS\UI\Component\Image\Image|\ILIAS\UI\Component\Symbol\Icon\Icon
     */
    public function getLead();
}
