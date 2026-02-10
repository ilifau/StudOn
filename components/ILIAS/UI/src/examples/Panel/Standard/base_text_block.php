<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Panel\Standard;

/**
 * ---
 * description: >
 *   Example for rendering a standard panel.
 *
 * expected output: >
 *   ILIAS shows a panel with a large title "Panel Title" and a text "Some Content".
 * ---
 */
function base_text_block()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $panel = $f->panel()->standard(
        "Panel Title",
        $f->legacy("Some Content")
    );

    return $renderer->render($panel);
}
