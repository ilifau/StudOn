<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Chart\ProgressMeter\Mini;

/**
 * ---
 * description: >
 *   Example for rendering a mini Progress Meter with minimum configuration
 *
 * expected output: >
 *   ILIAS shows a rounded progress meter with a red colored bar. A grey bar marks the needed value.
 * ---
 */
function maximum_configuration()
{
    //Loading factories
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    //Generating and rendering the mini progressmeter
    $progressmeter = $f->chart()->progressMeter()->mini(100, 50, 75);

    // render
    return $renderer->render($progressmeter);
}
