<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Button\Standard;

/**
 * ---
 * description: >
 *   Example for rendering a standard button.
 *
 * expected output: >
 *   ILIAS shows a colored, active button with a title. Clicking the button opens the website www.ilias.de
 *   in the same browser window.
 * ---
 */
function base()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    return $renderer->render($f->button()->standard("Goto ILIAS", "http://www.ilias.de"));
}
