<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Card\RepositoryObject;

/**
 * ---
 * description: >
 *   Example for rendering a repository card with an icon and certificate
 *
 * expected output: >
 *   ILIAS shows a base logo. Additionally you can see the outlined icon "Certificate" next to the course icon.
 * ---
 */
function with_object_icon_and_certificate()
{
    //Init Factory and Renderer
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $icon = $f->symbol()->icon()->standard("crs", 'Course');

    $content = $f->listing()->descriptive(
        array(
            "Entry 1" => "Some text",
            "Entry 2" => "Some more text",
        )
    );

    $image = $f->image()->responsive(
        "./assets/images/logo/HeaderIcon.svg",
        "Thumbnail Example"
    );

    $card = $f->card()->repositoryObject(
        "Title",
        $image
    )->withObjectIcon(
        $icon
    )->withCertificateIcon(
        true
    )->withSections(
        array(
            $content,
            $content,
        )
    );

    //Render
    return $renderer->render($card);
}
