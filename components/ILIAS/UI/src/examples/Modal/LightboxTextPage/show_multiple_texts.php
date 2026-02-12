<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Modal\LightboxTextPage;

/**
 * ---
 * description: >
 *   Example for rendering a lightbox text page modal with multiple texts.
 *
 * expected output: >
 *   ILIAS shows a button titled "Show texts".
 *   A click onto the button greys out ILIAS and opens a modal with multiple contents which can be viewed each after another
 *   by clicking the arrow glyphs.
 *   All contents consist of a title each.
 *   In the lower part of the modal a circle glyph is centered about which you can see that you can look at two contents
 *   within the modal.
 * ---
 */
function show_multiple_texts()
{
    global $DIC;
    $factory = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();
    $page1 = $factory->modal()->lightboxTextPage('Some text content you have to agree on!', 'User Agreement');
    $page2 = $factory->modal()->lightboxTextPage(
        'Another text content you have to agree on!',
        'Data Privacy Statement'
    );
    $modal = $factory->modal()->lightbox([$page1, $page2]);
    $button = $factory->button()->standard('Show Texts', '')
        ->withOnClick($modal->getShowSignal());

    return $renderer->render([$button, $modal]);
}
