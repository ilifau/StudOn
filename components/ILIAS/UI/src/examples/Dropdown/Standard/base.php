<?php

declare(strict_types=1);

namespace ILIAS\UI\Examples\Dropdown\Standard;

/**
 * ---
 * description: >
 *   Example for rendering a dropdown.
 *
 * expected output: >
 *   ILIAS shows a dropdown button with a title and arrow symbol. Clicking the button will open a
 *   dropdown menu with entries rendered as shy buttons. Clicking the entries will open
 *   the appropriate website in the same browser window.
 * ---
 */
function base()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $items = array(
        $f->button()->shy("ILIAS", "https://www.ilias.de"),
        $f->button()->shy("GitHub", "https://www.github.com")
    );
    return $renderer->render($f->dropdown()->standard($items)->withLabel("Actions"));
}
