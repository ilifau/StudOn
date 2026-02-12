<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Image\Standard;

/**
 * ---
 * description: >
 *   Base example for rendering an Image with only decorative purpose
 *   (see accessibility rules in images)
 *
 * expected output: >
 *   ILIAS shows a rendered image with a grey background. While changing the size of the browser window the
 *   image won't decrease in size. If the element gets analyzed no text entry for "alt" is shown within the HTML.
 * ---
 */
function decorative()
{
    //Loading factories
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    //Generating and rendering the image
    $image = $f->image()->standard(
        "assets/ui-examples/images/Image/HeaderIconLarge.svg",
        ""
    );
    $html = $renderer->render($image);

    return $html;
}
