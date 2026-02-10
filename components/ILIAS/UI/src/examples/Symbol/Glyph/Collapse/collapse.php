<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Symbol\Glyph\Collapse;

/**
 * ---
 * description: >
 *   Example for rendering a collapse glyph.
 *
 * expected output: >
 *   Active:
 *   ILIAS shows a monochrome arrow-pointing-down symbol on a grey background. Moving your cursor over the symbol will
 *   change the symbol's color to a slightly darker color. Additionally the cursor's form will change and the cursor
 *   indicates a linking.
 *
 *   Inactive:
 *   ILIAS shows the same symbol. But it's greyed out which indicates that it is deactivated. Moving the cursor above the
 *   symbol will change nothing.
 *
 *   Hightlighted:
 *   ILIAS shows the same symbol. But it is higlighted particularly. Moving your cursor over the symbol will darken the
 *   icon's color. Additionally the cursor's form will change and it indicates a linking.
 * ---
 */
function collapse()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $glyph = $f->symbol()->glyph()->collapse("#");

    //Showcase the various states of this Glyph
    $list = $f->listing()->descriptive([
        "Active" => $glyph,
        "Inactive" => $glyph->withUnavailableAction(),
        "Highlighted" => $glyph->withHighlight()
    ]);

    return $renderer->render($list);
}
