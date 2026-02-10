<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Divider\Horizontal;

/**
 * ---
 * description: >
 *   Example for rendering a horizontal dividing line with a label
 *
 * expected output: >
 *   ILIAS shows a bulky white dividing line within a light grey box. The text "Label" is positioned within the line.
 * ---
 */
function with_label()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    return $renderer->render($f->divider()->horizontal()->withLabel("Label"));
}
