<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Divider\Horizontal;

/**
 * ---
 * description: >
 *    Example for rendering a horizontal divider.
 *
 * expected output: >
 *   ILIAS shows a fine dark grey dividing line within a light grey box.
 * ---
 */
function base()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    return $renderer->render($f->divider()->horizontal());
}
