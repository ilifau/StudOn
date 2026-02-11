<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Card\Standard;

/**
 * ---
 * expected output: >
 *   ILIAS shows a base ILIAS-Logo. A clickable title, linked to ilias.de, is displayed below the logo.
 * ---
 */
function with_title_action()
{
    //Init Factory and Renderer
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $image = $f->image()->responsive(
        "./assets/images/logo/HeaderIcon.svg",
        "Thumbnail Example"
    );

    $url = "http://www.ilias.de";

    $card = $f->card()->standard("Title", $image)->withTitleAction($url);

    //Render
    return $renderer->render($card);
}
