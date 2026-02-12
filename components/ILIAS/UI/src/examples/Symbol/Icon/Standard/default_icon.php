<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Symbol\Icon\Standard;

/**
 * ---
 * description: >
 *   Example for rendering a standard icon.
 *
 * expected output: >
 *   ILIAS shows a standard icon in three different sizes.
 * ---
 */
function default_icon()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $buffer = array();

    $ico = $f->symbol()->icon()->standard('someExample', 'Example');
    $ico = $ico->withAbbreviation('E');

    $buffer[] = $renderer->render($ico)
        . ' Small Example with Short Abbreviation';

    $buffer[] = $renderer->render($ico->withSize('medium'))
        . ' Medium Example with Short Abbreviation';

    $buffer[] = $renderer->render($ico->withSize('large'))
        . ' Large Example with Short Abbreviation';


    $ico = $f->symbol()->icon()->standard('someOtherExample', 'Example');
    $ico = $ico->withAbbreviation('LA');

    $buffer[] = $renderer->render($ico->withSize('small'))
        . ' Small Example with Long Abbreviation';

    $buffer[] = $renderer->render($ico->withSize('medium'))
        . ' Medium Example with Long Abbreviation';

    $buffer[] = $renderer->render($ico->withSize('large'))
        . ' Large Example with Long Abbreviation';


    return implode('<br><br>', $buffer);
}
