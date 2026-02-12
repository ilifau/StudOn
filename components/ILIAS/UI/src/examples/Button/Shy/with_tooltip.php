<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Button\Shy;

/**
 * ---
 * description: >
 *   Example for rendering a shy button with tooltips
 *
 * expected output: >
 *   Hovering over the rendered button will show a tooltip with the following contents:
 *   "tooltip: ilias" and "tooltip: learning management system".
 * ---
 */
function with_tooltip()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $button = $f->button()
        ->shy("Goto ILIAS", "http://www.ilias.de")
        ->withHelpTopics(
            ...$f->helpTopics("ilias", "learning management system")
        );

    return $renderer->render($button);
}
