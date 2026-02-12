<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Symbol\Glyph\User;

/**
 * ---
 * description: >
 *   Example for rendering a user icon with JS binding.
 *
 * expected output: >
 *   ILIAS shows a monochrome user symbol on a grey background. Moving the cursor above the symbol will darken it's
 *   color slightly. Additionally the cursor's form will change and it indicates a linking.
 *   Clicking onto the icon will open a message with a confirmation about your click.
 * ---
 */
function with_js_binding()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    return $renderer->render(
        $f->symbol()->glyph()->user("#")
            ->withOnLoadCode(function ($id) {
                return
                    "$(\"#$id\").click(function() { alert(\"Clicked: $id\"); return false; });";
            })
    );
}
