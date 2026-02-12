<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Button\Standard;

/**
 * ---
 * description: >
 *   Example for rendering an engaged standard button
 *
 * expected output: >
 *   ILIAS shows a white, active button with a title. Clicking the button won't activate any actions.
 * ---
 */
function engaged()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $button = $f->button()->standard("Engaged Button", "#")
                                  ->withEngagedState(true);
    return $renderer->render($button);
}
