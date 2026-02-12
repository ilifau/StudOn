<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Panel\Standard;

/**
 * ---
 * description: >
 *   Example for rendering a standard panel with actions.
 *
 * expected output: >
 *   ILIAS shows a base panel but it also includes a menu displayed by
 *   an triangle symbol pointing down. You can open the menu which includes links to ilias.de and GitHub.
 * ---
 */
function with_actions()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $actions = $f->dropdown()->standard(array(
        $f->button()->shy("ILIAS", "https://www.ilias.de"),
        $f->button()->shy("GitHub", "https://www.github.com")
    ));

    $panel = $f->panel()->standard(
        "Panel Title",
        $f->legacy("Some Content")
    )->withActions($actions);

    return $renderer->render($panel);
}
