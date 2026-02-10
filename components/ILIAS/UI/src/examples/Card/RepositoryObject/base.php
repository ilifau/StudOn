<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Card\RepositoryObject;

/**
 * ---
 * description: >
 *   Example for rendering a repository card. Note that those cards are used if more visual information about the
 *   repository object type is needed.
 *
 * expected output: >
 *   ILIAS shows a ILIAS-Logo with a title below. The logo's size will change accordingly to the size of the browser/desktop.
 * ---
 */
function base()
{
    //Init Factory and Renderer
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $image = $f->image()->responsive(
        "./assets/images/logo/HeaderIcon.svg",
        "Thumbnail Example"
    );

    $card = $f->card()->repositoryObject("RepositoryObject Card Title", $image);

    //Render
    return $renderer->render($card);
}
