<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Legacy;

/**
 * ---
 * description: >
 *   Example for rendering a legacy box.
 *
 * expected output: >
 *   ILIAS shows a box titled "Panel Title" and a grey background. In the lower part of the box the text "Legacy Content"
 *   on a white background is written.
 * ---
 */
function inside_panel()
{
    //Init Factory and Renderer
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    //Init Component
    $legacy = $f->legacy("Legacy Content");
    $panel = $f->panel()->standard("Panel Title", $legacy);

    //Render
    return $renderer->render($panel);
}
