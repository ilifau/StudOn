<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Link\Standard;

/**
 * ---
 * description: >
 *   Example for rendering a standard link.
 *
 * expected output: >
 *   ILIAS shows a link with the title "Goto ILIAS". Clicking the link opens the website www.ilias.de in the same
 *   browser window.
 * ---
 */
function base()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    return $renderer->render($f->link()->standard("Goto ILIAS", "http://www.ilias.de"));
}
