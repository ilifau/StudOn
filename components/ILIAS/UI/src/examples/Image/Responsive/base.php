<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Image\Responsive;

/**
 * ---
 * description: >
 *  Base example for rendering a responsive Image.
 *
 * expected output: >
 *   ILIAS shows a rendered image. While changing the size of the browser window the image will
 *   decrease in size bit by bit. If the element gets analyzed a text entry for "alt" is shown within the HTML.
 * ---
 */
function base()
{
    //Loading factories
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    //Genarating and rendering the image
    $image = $f->image()->responsive(
        "assets/ui-examples/images/Image/HeaderIconLarge.svg",
        "Thumbnail Example"
    );
    $html = $renderer->render($image);

    return $html;
}
