<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Item\Standard;

/**
 * ---
 * description: >
 *   Example for rendering a standard item with an image and displaying the progress.
 *
 * expected output: >
 *   ILIAS shows a box including the following informations: A heading "Item Title" with a dummy text in small writings
 *   ("Lorem ipsum...") below. On the left side a ILIAS icon is displayed, on the right side you can see a pictorial representation
 *   and also a text (75%) about the progress.
 * ---
 */
function with_image_and_progress()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();
    $chart = $f->chart()->progressMeter()->standard(100, 75);
    $app_item = $f->item()->standard("Item Title")
                  ->withDescription("Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.")
                  ->withProgress($chart)
                  ->withLeadImage($f->image()->responsive(
                      "assets/ui-examples/images/Image/HeaderIconLarge.svg",
                      "Thumbnail Example"
                  ));
    return $renderer->render($app_item);
}
