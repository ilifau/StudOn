<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Button\Standard;

/**
 * ---
 * description: >
 *   Example for rendering a standard button with JS binding
 *
 * expected output: >
 *   ILIAS shows an active button with a title. Clicking the button opens a dialog with a click-ID.
 * ---
 */
function with_js_binding()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    return $renderer->render(
        $f->button()->standard("Goto ILIAS", "#")
            ->withOnLoadCode(function ($id) {
                return
                    "$(\"#$id\").click(function() { alert(\"Clicked: $id\"); return false;});";
            })
    );
}
