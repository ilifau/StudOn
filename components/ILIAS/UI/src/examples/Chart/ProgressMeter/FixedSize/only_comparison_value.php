<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Chart\ProgressMeter\FixedSize;

/**
 * ---
 * description: >
 *   Example for rendering a fixed size Progress Meter with a diagnostic score only
 *
 * expected output: >
 *   ILIAS shows a base rounded progress meter with two colored bars and different percentages. A triangle marks the
 *   needed value at 75%. The information "0%" and "75%" are displayed within the progress meter.
 *
 *   Changing the browser window's size will not change the size of the progress meter: the display stays the same!
 * ---
 */
function only_comparison_value()
{
    //Loading factories
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    //Genarating and rendering the responsive progressmeter
    $progressmeter = $f->chart()->progressMeter()->fixedSize(100, 0, 75, 50);

    // render
    return $renderer->render($progressmeter);
}
