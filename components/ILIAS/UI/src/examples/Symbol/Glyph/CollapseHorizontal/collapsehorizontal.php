<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Symbol\Glyph\CollapseHorizontal;

/**
 * ---
 * description: >
 *   Example for rendring a collapse horizontal glyph.
 *
 * expected output: >
 *   Active:
 *   ILIAS shows a box with three words listed among each other. Every word has got a "<" arrow functioning as a link but
 *   without any actions. The first arrow is active, the second and third is colored.
 * ---
 */
function collapsehorizontal()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $glyph = $f->symbol()->glyph()->collapseHorizontal("#");

    //Showcase the various states of this Glyph
    $list = $f->listing()->descriptive([
        "Active" => $glyph,
        "Inactive" => $glyph->withUnavailableAction(),
        "Highlighted" => $glyph->withHighlight()
    ]);

    return $renderer->render($list);
}
