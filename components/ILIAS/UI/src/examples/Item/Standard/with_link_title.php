<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Item\Standard;

/**
 * ---
 * description: >
 *   Example for rendering a standard item with a link title.
 *
 * expected output: >
 *   ILIAS shows a box including the following informations: A title with a dummy text in small writings
 *   ("Lorem ipsum...") below. Beneath those you can see a fine line and more informations about "Code Repo" and
 *   "Location". The title is rendered as a link and redirects to ilias.de.
 * ---
 */
function with_link_title()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();
    $app_item = $f->item()->standard($f->link()->standard("ILIAS", "https://www.ilias.de"))
        ->withProperties(array(
            "Code Repo" => $f->button()->shy("ILIAS on GitHub", "https://www.github.com/ILIAS-eLearning/ILIAS"),
            "Location" => "Room 123, Main Street 44, 3012 Bern"))
        ->withDescription("Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.");
    return $renderer->render($app_item);
}
