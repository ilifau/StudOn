<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Item\Shy;

/**
 * ---
 * description: >
 *   Example for rendering a shy item with properties.
 *
 * expected output: >
 *   ILIAS shows a box highlighted white and including the text "Test shy Item". Additionally a fine dashed line is displayed
 *   below the text. Right below the line you can see the information "Property: Value".
 * ---
 */
function with_properties()
{
    global $DIC;

    return $DIC->ui()->renderer()->render(
        $DIC->ui()->factory()->item()->shy('Test shy Item')->withProperties(['Property' => 'Value'])
    );
}
