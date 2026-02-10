<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Item\Shy;

/**
 * ---
 * description: >
 *   Example for rendering a shy item with a description.
 *
 * expected output: >
 *   ILIAS shows a box highlighted white and including the text "Test shy Item". Additionally a short description is displayed
 *   below the text.
 * ---
 */
function with_description()
{
    global $DIC;

    return $DIC->ui()->renderer()->render(
        $DIC->ui()->factory()->item()->shy('Test shy Item')->withDescription('This is a test shy.')
    );
}
