<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Link\Standard;

use ILIAS\UI\Component\Link\Relationship;

/**
 * ---
 * description: >
 *   Example for rendering a standard link including relationships
 *
 * expected output: >
 *   ILIAS shows a link with the title "Goto ILIAS". Clicking the link opens the website www.ilias.de in the same
 *   browser window.
 * ---
 */
function with_relationships()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $link = $f->link()->standard("Goto ILIAS", "http://www.ilias.de")
        ->withAdditionalRelationshipToReferencedResource(Relationship::EXTERNAL)
        ->withAdditionalRelationshipToReferencedResource(Relationship::BOOKMARK);

    return $renderer->render($link);
}
