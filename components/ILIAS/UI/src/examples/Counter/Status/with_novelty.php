<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Counter\Status;

/**
 * ---
 * description: >
 *   Base example for rendering a status counter with novelty
 *
 * expected output: >
 *   ILIAS shows a glyph with two counters. The counter's numbers are white and highlighted red resp. grey.
 * ---
 */
function with_novelty()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    return $renderer->render(
        $f->symbol()->glyph()->mail("#")
        ->withCounter($f->counter()->novelty(1))
        ->withCounter($f->counter()->status(8))
    );
}
