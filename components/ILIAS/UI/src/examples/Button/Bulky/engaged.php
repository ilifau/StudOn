<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Button\Bulky;

/**
 * ---
 * description: >
 *   Example for rendering an engaged bulky button
 *
 * expected output: >
 *   ILIAS shows a button with a glyph and the title "Engaged". The button's background looks different from the base bulky button.
 *   Clicking the button won't activate any actions.
 * ---
 */
function engaged()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $glyph = $f->symbol()->glyph()->briefcase();
    $button = $f->button()->bulky($glyph, 'Engaged Button', '#')
                          ->withEngagedState(true);

    return $renderer->render($button);
}
