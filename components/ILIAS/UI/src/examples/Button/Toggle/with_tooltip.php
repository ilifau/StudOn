<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Button\Toggle;

/**
 * ---
 * description: >
 *   Example for rendering a Toggle Button with tooltips
 *
 * expected output: >
 *   Hovering over the rendered switch shows following tooltips: "tooltip: ilias" and "tooltip: learning management system".
 * ---
 */
function with_tooltip()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $button = $f->button()->toggle('', '#', '#')
                          ->withAriaLabel("Switch the State of XY")
                          ->withHelpTopics(
                              ...$f->helpTopics("ilias", "learning management system")
                          );

    return $renderer->render([$button]);
}
