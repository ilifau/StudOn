<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Chart\ScaleBar;

/**
 * ---
 * description: >
 *   Example for rendering a scale bar with ten items
 *
 * expected output: >
 *   ILIAS shows ten equal stripes with a number between 0-9 each.
 *   Stripe "6" is particularly highlighted. No stripe is clickable.
 * ---
 */
function ten_items()
{
    //Init Factory and Renderer
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $c = $f->chart()->scaleBar(
        array(
            "0" => false,
            "1" => false,
            "2" => false,
            "3" => false,
            "4" => false,
            "5" => false,
            "6" => true,
            "7" => false,
            "8" => false,
            "9" => false
        )
    );

    //Render
    return $renderer->render($c);
}
