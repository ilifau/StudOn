<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Image\Standard;

/**
 * ---
 * description: >
 *   Example for rendering an Image with a string as action
 *
 * expected output: >
 *   Clicking onto the rendered image will open a new tab to ilias.de.
 * ---
 */
function with_string_action()
{
    //Loading factories
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    //Generating and rendering the image and modal
    $image = $f->image()->standard(
        "assets/ui-examples/images/Image/HeaderIconLarge.svg",
        "Thumbnail Example"
    )->withAction("https://www.ilias.de");

    $html = $renderer->render($image);

    return $html;
}
