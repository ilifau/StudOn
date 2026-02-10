<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Dropdown\Standard;

/**
 * ---
 * description: >
 *   Example for rendering a dropdown with aria labels
 *
 * expected output: >
 *   ILIAS shows base dropdown button without a title. Clicking the button will open a
 *   dropdown menu with two entries rendered as shy buttons. Clicking the entries will open the
 *   appropriate website in the same browser window.
 * ---
 */
function with_aria_label()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $items = array(
        $f->button()->shy("GitHub", "https://www.github.com"),
        $f->button()->shy("Bugs", "https://mantis.ilias.de"),
    );
    return $renderer->render($f->dropdown()->standard($items)->withAriaLabel("MyLabel"));
}
