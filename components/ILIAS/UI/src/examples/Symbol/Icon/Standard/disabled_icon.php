<?php

declare(strict_types=1);

namespace ILIAS\UI\Examples\Symbol\Icon\Standard;

/**
 * ---
 * description: >
 *   Example for rendering a disabled standard icon.
 *
 * expected output: >
 *   ILIAS shows a standard icon in an alternative version. It's design hints to the icon being disabled.
 * ---
 */
function disabled_icon()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $buffer = array();

    $ico = $f->symbol()->icon()->standard('grp', 'Group', 'large', false);

    $buffer[] = $renderer->render($ico) . ' Large Group Enabled';
    $buffer[] = $renderer->render($ico->withDisabled(true)) . ' Large Group Disabled';

    return implode('<br><br>', $buffer);
}
