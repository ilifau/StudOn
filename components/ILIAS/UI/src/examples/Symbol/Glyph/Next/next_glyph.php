<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Symbol\Glyph\Next;

/**
 * ---
 * description: >
 *   Example for rendring a next glyph.
 *
 * expected output: >
 *   Active:
 *   ILIAS shows a monochrome greater-than symbol on a grey background. Moving the cursor above the symbol will darken it's
 *   color slightly. Additionally the cursor's form will change and it indicates a linking.
 *
 *   Inactive:
 *   ILIAS shows the same symbol, but it's greyed out. Moving the cursor will not change the presentation.
 *
 *   Highlighted:
 *   ILIAS shows the same symbol but it's highlighted particularly. Moving the cursor above the symbol will darken it's
 *   color slightly. Additionally the cursor's form will change and it indicates a linking.
 * ---
 */
function next_glyph()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $glyph = $f->symbol()->glyph()->next("#");

    //Showcase the various states of this Glyph
    $list = $f->listing()->descriptive([
        "Active" => $glyph,
        "Inactive" => $glyph->withUnavailableAction(),
        "Highlighted" => $glyph->withHighlight()
    ]);

    return $renderer->render($list);
}
