<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Button\Primary;

/**
 * ---
 * description: >
 *   This example provides buttons with a Glyph in (and as) the label.
 *
 * expected output: >
 *   ILIAS shows buttons with the Search Gylph and in some cases a label in different states.
 * ---
 */
function with_glyph()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $glyph = $f->symbol()->glyph()->search();
    $button = $f->button()->primary('search', '#')
        ->withSymbol($glyph);
    $button2 = $button->withLabel('');

    return $renderer->render([
        $button,
        $button->withEngagedState(true),
        $button->withUnavailableAction(true),
        $f->divider()->vertical(),
        $button2,
        $button2->withEngagedState(true),
        $button2->withUnavailableAction(true),

    ]);
}
