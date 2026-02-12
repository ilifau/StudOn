<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\MessageBox\Failure;

/**
 * ---
 * description: >
 *   Example for rendering a failure message box.
 *
 * expected output: >
 *   ILIAS shows a red box with a dummy text.
 * ---
 */
function failure()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    return $renderer->render($f->messageBox()->failure("Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua."));
}
