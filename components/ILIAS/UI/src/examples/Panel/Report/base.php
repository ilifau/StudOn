<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Panel\Report;

/**
 * ---
 * description: >
 *   Example for rendering a report panel.
 *
 * expected output: >
 *   ILIAS shows a panel with a title and two sub panels as content.
 *   The first sub panel displays a title, a text and a card titled  "Card Heading" including it's content "Card Content".
 *   The second sub panel displays a title and some content.
 * ---
 */
function base()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $sub1 = $f->panel()->sub("Sub Panel Title 1", $f->legacy("Some Content"))
            ->withFurtherInformation($f->card()->standard("Card Heading")->withSections(array($f->legacy("Card Content"))));
    $sub2 = $f->panel()->sub("Sub Panel Title 2", $f->legacy("Some Content"));

    $block = $f->panel()->report("Report Title", array($sub1,$sub2));

    return $renderer->render($block);
}
