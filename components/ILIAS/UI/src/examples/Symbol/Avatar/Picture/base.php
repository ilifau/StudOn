<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Symbol\Avatar\Picture;

/**
 * ---
 * desription: >
 *   Example for rendering an avatar picture.
 *
 * expected output: >
 *   ILIAS shows a round avatar including a profile picture.
 * ---
 */
function base()
{
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $picture_avatar = $f->symbol()->avatar()->picture('./assets/images/placeholder/no_photo_xsmall.jpg', 'demo.user');

    return $renderer->render($picture_avatar);
}
