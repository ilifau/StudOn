<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Chart\ProgressMeter\FixedSize;

/**
 * ---
 * description: >
 *   Example for rendering a fixed size Progress Meter when a specific score was reached
 *
 * expected output: >
 *   ILIAS shows a base progress meter. The bar takes up 80% of the display. A triangle
 *   marks the needed value at 75%. The information "80%" and "75%" are positioned within the progress meter.
 *
 *   Changing the browser window's size will not change the size of the progress meter: the display stays the same!
 * ---
 */
function user_reached_required()
{
    //Loading factories
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    //Genarating and rendering the responsive progressmeter
    $progressmeter = $f->chart()->progressMeter()->fixedSize(100, 80, 75);

    // render
    return $renderer->render($progressmeter);
}
