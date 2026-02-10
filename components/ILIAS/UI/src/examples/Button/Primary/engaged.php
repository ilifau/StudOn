<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Button\Primary;

/**
 * ---
 * description: >
 *   Example for rendering an engaged primary button
 *
 * expected output: >
 *   ILIAS shows a white button with a title. A click onto the button won't activate any actions.
 * ---
 */
function engaged()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $button = $f->button()->primary("Engaged Button", "#")
                                  ->withEngagedState(true);
    return $renderer->render($button);
}
