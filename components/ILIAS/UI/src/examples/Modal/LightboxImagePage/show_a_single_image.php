<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Modal\LightboxImagePage;

/**
 * ---
 * description: >
 *   Example for rendering a lightbox image page modal with a single image.
 *
 * expected output: >
 *   ILIAS shows a button titled "Show Image".
 *   A click onto the button greys out ILIAS, opens a modal titled "Mountains",
 *   an image and a Copyright note above the image. The modal's background is dark with a light font color.
 * ---
 */
function show_a_single_image()
{
    global $DIC;
    $factory = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();
    $image = $factory->image()->responsive("assets/ui-examples/images/Image/mountains.jpg", "Image source: https://stocksnap.io, Creative Commons CC0 license");
    $page = $factory->modal()->lightboxImagePage($image, 'Mountains');
    $modal = $factory->modal()->lightbox($page);
    $button = $factory->button()->standard('Show Image', '')
        ->withOnClick($modal->getShowSignal());

    return $renderer->render([$button, $modal]);
}
