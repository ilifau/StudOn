<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Chart\ProgressMeter\Standard;

/**
 * ---
 * description: >
 *   Example for rendering a standard Progress Meter when the required value was reached
 *
 * expected output: >
 *   ILIAS shows a base progress meter with a green colored bar. The bar takes up 80% of the progress meter. A triangle
 *   marks the needed value at 75%. The information "80%" and "75%" are positioned within the progress meter.
 *
 *   Changing the size of the browser window will change the size of the progress meter: it gets smaller or bigger.
 * ---
 */
function user_reached_required()
{
    //Loading factories
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    //Generating and rendering the standard progressmeter
    $progressmeter = $f->chart()->progressMeter()->standard(100, 80, 75);

    // render
    return $renderer->render($progressmeter);
}
