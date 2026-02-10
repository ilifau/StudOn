<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Card\Standard;

/**
 * ---
 * description: >
 *   Example for rendering a base card.
 *
 * expected output: >
 *   ILIAS shows a ILIAS-Logo with a title below. The size of the logo depends on the browser/desktop size and will change accordingly.
 * ---
 */
function base()
{
    //Init Factory and Renderer
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $content = $f->listing()->descriptive(
        array(
            "Entry 1" => "Some text",
            "Entry 2" => "Some more text",
        )
    );

    $image = $f->image()->responsive(
        "./assets/images/logo/HeaderIcon.svg",
        "Thumbnail Example"
    );

    $card = $f->card()->standard("Title", $image);

    //Render
    return $renderer->render($card);
}
