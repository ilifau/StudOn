<?php

declare(strict_types=1);

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

namespace ILIAS\Refinery\String;

use ILIAS\Refinery\Transformation;
use ILIAS\Refinery\DeriveApplyToFromTransform;
use ILIAS\Refinery\DeriveInvokeFromTransform;
use InvalidArgumentException;

/**
 * Strip tags from a string.
 */
class HtmlSpecialChars implements Transformation
{
    use DeriveApplyToFromTransform;
    use DeriveInvokeFromTransform;

    private array $allowed_tags = [];

    public function __construct(array $allowed_tags)
    {
        $this->allowed_tags = $allowed_tags;
    }

    public function transform($from): string
    {
        if (!is_string($from)) {
            throw new InvalidArgumentException(__METHOD__ . " the argument is not a string.");
        }

        $allowed_tags = array_merge(
            array_map(
                fn ($tag) => '<' . $tag . '>',
                $this->allowed_tags
            ),
            array_map(
                fn ($tag) => '</' . $tag . '>',
                $this->allowed_tags
            )
        );

        $turned_tags = array_map(
            fn ($tag) => htmlspecialchars($tag, ENT_QUOTES),
            $allowed_tags
        );

        $from = htmlspecialchars($from, ENT_QUOTES);
        return str_replace($turned_tags, $allowed_tags, $from);
    }
}
