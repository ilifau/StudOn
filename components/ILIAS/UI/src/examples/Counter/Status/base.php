<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Counter\Status;

/**
 * ---
 * description: >
 *   Base example for rendering a status counter.
 *
 * expected output: >
 *   ILIAS shows a rendered glyph with a counter. The counter consists of a white colored number within a grey circle.
 * ---
 */
function base()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    return $renderer->render($f->symbol()->glyph()->mail("#")
        ->withCounter($f->counter()->status(3)));
}
