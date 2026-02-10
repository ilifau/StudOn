<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Button\Bulky;

/**
 * ---
 * description: >
 *   Example for rendering a bulky button with tooltips
 *
 * expected output: >
 *   Hovering over the rendered text will show you the following tooltips: "tooltip: ilias"
 *   and "tooltip: learning management system".
 * ---
 */
function with_tooltip()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();
    $glyph = $f->symbol()->glyph()->comment();

    $button = $f->button()
        ->bulky($glyph, "Goto ILIAS", "http://www.ilias.de")
        ->withHelpTopics(
            ...$f->helpTopics("ilias", "learning management system")
        );

    return $renderer->render($button);
}
