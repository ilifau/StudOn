<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Panel\Secondary\Legacy;

/**
 * ---
 * description: >
 *   Example for rendering a secondary legacy listing panel with a footer.
 *
 * expected output: >
 *   ILIAS shows a panel titled "Panel Title". It includes five tag buttons and a link "Edit Keywords". Clicking the link
 *   will not activate any actions.
 * ---
 */
function with_footer()
{
    global $DIC;

    $factory = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $tags = ["PHP", "ILIAS", "Sofware", "SOLID", "Domain Driven"];

    $html = "";
    foreach ($tags as $tag) {
        $html .= $renderer->render($factory->button()->tag($tag, ""));
    }

    $legacy = $factory->legacy($html);
    $link = $factory->button()->Shy("Edit Keywords", "");

    $panel = $factory->panel()->secondary()->legacy("panel title", $legacy)->withFooter($link);

    return $renderer->render($panel);
}
