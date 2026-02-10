<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\MessageBox\Confirmation;

/**
 * ---
 * description: >
 *   Example for rendering a confirmation message box.
 *
 * expected output: >
 *   ILIAS shows a yellow box with a dummy text and two buttons.
 *   Clicking the buttons does not do anything.
 * ---
 */
function confirmation()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $buttons = [$f->button()->standard("Confirm", "#"), $f->button()->standard("Cancel", "#")];

    return $renderer->render($f->messageBox()->confirmation("Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.")->withButtons($buttons));
}
