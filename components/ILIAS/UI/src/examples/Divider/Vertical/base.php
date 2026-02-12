<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Divider\Vertical;

/**
 * ---
 * description: >
 *   Example for rendering a vertical dividing line.
 *
 * expected output: >
 *   ILIAS shows a dot centered between two text sections.
 * ---
 */
function base()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    return $renderer->render(array($f->legacy("Some content"),
        $f->divider()->vertical(),
        $f->legacy("More content")));
}
