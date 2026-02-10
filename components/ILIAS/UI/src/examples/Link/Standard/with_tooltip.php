<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Link\Standard;

/**
 * ---
 * description: >
 *   Example for rendering a standard link with tooltips
 *
 * expected output: >
 *   ILIAS shows a link. Hovering over the link will show you a tooltip with the following lines: "tooltip: ilias" and
 *   "tooltip: learning management system".
 * ---
 */
function with_tooltip()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $link = $f->link()->standard("Goto ILIAS", "http://www.ilias.de")
        ->withHelpTopics(
            ...$f->helpTopics("ilias", "learning management system")
        );
    return $renderer->render($link);
}
