<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Legacy;

/**
 * ---
 * description: >
 *   Example for rendering a legacy box with an inside panel.
 *
 * expected output: >
 *   ILIAS shows a box including the text "Legacy Content".
 * ---
 */
function base()
{
    //Init Factory and Renderer
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    //Init Component
    $legacy = $f->legacy("Legacy Content");

    //Render
    return $renderer->render($legacy);
}
