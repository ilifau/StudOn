<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Image\Standard;

/**
 * ---
 * description: >
 *   Base example for rendering an Image.
 *
 * expected output: >
 *   ILIAS shows a rendered image with a grey background. While changing the size of the browser window the
 *   image won't decrease in size. If the element gets analyzed a text entry for "alt" is shown within the HTML.
 * ---
 */
function base()
{
    //Loading factories
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    //Generating and rendering the image
    $image = $f->image()->standard(
        "assets/ui-examples/images/Image/HeaderIconLarge.svg",
        "Thumbnail Example"
    );
    $html = $renderer->render($image);

    return $html;
}
