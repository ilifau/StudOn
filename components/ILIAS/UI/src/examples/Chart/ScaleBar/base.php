<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Chart\ScaleBar;

/**
 * ---
 * description: >
 *   Example of rendering a base scale bar.
 *
 * expected output: >
 *   ILIAS shows four equal stripes with a label each: None, Low, Medium, High.
 *   The "Medium" stripe is particularly highlighted. No stripe is clickable.
 * ---
 */
function base()
{
    //Init Factory and Renderer
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $c = $f->chart()->scaleBar(
        array(
            "None" => false,
            "Low" => false,
            "Medium" => true,
            "High" => false
        )
    );

    //Render
    return $renderer->render($c);
}
