<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Button\Shy;

/**
 * ---
 * description: >
 *   Example for rendering a shy button.
 *
 * expected output: >
 *   The shown button lacks a background, but shows a text. Clicking the button will
 *   open the website www.ilias.de in the same browser window.
 * ---
 */
function base()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    return $renderer->render($f->button()->shy("ILIAS", "http://www.ilias.de"));
}
