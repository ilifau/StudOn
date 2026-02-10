<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Button\Shy;

/**
 * ---
 * description: >
 *   Example for rendering an engaged shy button
 *
 * expected output: >
 *   ILIAS shows a small button with a title. Clicking the button won't activate any actions.
 * ---
 */
function engaged()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $button = $f->button()->shy("Engaged Button", "#")
                                  ->withEngagedState(true);
    return $renderer->render($button);
}
