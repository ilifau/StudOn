<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Item\Standard;

/**
 * ---
 * description: >
 *   Example for rendering a standard item with a progress meter.
 *
 * expected output: >
 *   ILIAS shows a box including the following informations: A heading with a dummy text in small writings
 *   ("Lorem ipsum...") below. Additionally a progress meter (75%) is rendered on the right top side of the box.
 * ---
 */
function with_progress()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();
    $chart = $f->chart()->progressMeter()->standard(100, 75);
    $app_item = $f->item()->standard("Item Title")
                  ->withDescription("Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.")
                  ->withProgress($chart);
    return $renderer->render($app_item);
}
