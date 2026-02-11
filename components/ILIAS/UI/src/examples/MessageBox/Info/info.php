<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\MessageBox\Info;

/**
 * ---
 * description: >
 *   Example for rendering a info message box.
 *
 * expected output: >
 *   ILIAS shows a blue box with a dummy text and two buttons.
 *   Clicking the buttons will not activate any actions.
 *   Below you can see a white box with two links which also do not have got any actions.
 * ---
 */
function info()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $buttons = [$f->button()->standard("Action", "#"), $f->button()->standard("Cancel", "#")];

    $links = [
        $f->link()->standard("Open Exercise Assignment", "#"),
        $f->link()->standard("Open other screen", "#")
    ];

    return $renderer->render($f->messageBox()->info("Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.")
        ->withButtons($buttons)
        ->withLinks($links));
}
