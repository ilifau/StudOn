<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\MessageBox\Success;

/**
 * ---
 * description: >
 *   Example for rendering a success message box.
 *
 * expected output: >
 *   ILIAS shows a green box with a dummy text and two buttons.
 *   Clicking the buttons will not activate any actions.
 * ---
 */
function success()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $buttons = [$f->button()->standard("Action", "#"), $f->button()->standard("Cancel", "#")];

    return $renderer->render($f->messageBox()->success("Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.")->withButtons($buttons));
}
