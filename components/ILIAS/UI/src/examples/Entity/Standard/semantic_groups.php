<?php

declare(strict_types=1);

namespace ILIAS\UI\Examples\Entity\Standard;

/**
 * ---
 * expected output: >
 *   This example shows/identifies the semantic groups of entites;
 *   from top to bottom, left to right, the order of groups is this:
 *   - blocking conditions (left) and actions in a dropdown (right)
 *   - secondary indentifier (it indents all the latter) and featured properties
 *   - primary identifier
 *   - personal status
 *   - main details
 *   - availability
 *   - details
 *   - reactions (the tag) and prioritized reactions (the 'like' glyph)
 * ---
 */
function semantic_groups()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $entity = $f->entity()->standard('Primary Identifier', 'Secondary Identifier')
        ->withBlockingAvailabilityConditions($f->legacy('Blocking Conditions'))
        ->withFeaturedProperties($f->legacy('Featured_properties'))
        ->withPersonalStatus($f->legacy('Personal Status'))
        ->withMainDetails($f->legacy('Main Details'))
        ->withAvailability($f->legacy('Availability'))
        ->withDetails($f->legacy('Details'))
        ->withReactions($f->button()->tag('reaction', '#'))
        ->withPrioritizedReactions($f->symbol()->glyph()->like())
        ->withActions($f->button()->shy('action', '#'))
    ;

    return $renderer->render($entity);
}
