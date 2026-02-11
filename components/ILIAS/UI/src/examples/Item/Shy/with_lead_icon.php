<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Item\Shy;

use ILIAS\UI\Component\Symbol\Icon\Standard;

/**
 * ---
 * description: >
 *   Example for rendering a shy item with an lead icon.
 *
 * expected output: >
 *   ILIAS shows a box highlighted white and including the text "Test shy Item". Additionally a small icon is displayed
 *   to the left of the text.
 * ---
 */
function with_lead_icon()
{
    global $DIC;

    return $DIC->ui()->renderer()->render(
        $DIC->ui()->factory()->item()->shy('Test shy Item')->withLeadIcon(
            $DIC->ui()->factory()->symbol()->icon()->standard(Standard::GRP, 'conversation')
        )
    );
}
