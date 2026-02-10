<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Button\Primary;

/**
 * ---
 * description: >
 *   Example for rendering a primary button.
 *
 * expected output: >
 *   ILIAS shows an active, very colorful button with a title. Clicking the button will open the website
 *   www.ilias.de in the same browser window.
 * ---
 */
function base()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    return $renderer->render($f->button()->primary("Goto ILIAS", "http://www.ilias.de"));
}
