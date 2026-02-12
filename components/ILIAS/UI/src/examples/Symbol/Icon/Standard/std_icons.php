<?php

declare(strict_types=1);

namespace ILIAS\UI\Examples\Symbol\Icon\Standard;

/**
 * ---
 * description: >
 *   Example for rendering standard icons.
 *
 * expected output: >
 *   ILIAS shows labels with an icon each. Please report missing icons which might get displayed as a black block or
 *   in a faulty prestentation as a bug including the identifier's name.
 * ---
 */
function std_icons()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();
    $lng = $DIC->language();

    $i = $f->symbol()->icon()->standard('NONE', 'DummyIcon');
    $default_icons = $i->getAllStandardHandles();
    $buffer = array();

    foreach ($default_icons as $icon) {
        $i = $f->symbol()->icon()->standard($icon, $icon, 'medium');
        $buffer[] = $renderer->render($i)
        . ' '
        . $icon
        . ' - '
        . $lng->txt("obj_$icon");
    }

    return implode('<br><br>', $buffer);
}
