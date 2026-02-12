<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Counter\Novelty;

/**
 * ---
 * description: >
 *   Base example for rendering a novelty counter.
 *
 * expected output: >
 *   ILIAS shows a glyph with a counter. The counter consists of a white colored number within a red/orange circle.
 * ---
 */
function base()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    return $renderer->render($f->symbol()->glyph()->mail("#")
        ->withCounter($f->counter()->novelty(3)));
}
