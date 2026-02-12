<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Deck;

/**
 * ---
 * description: >
 *   Example for rendering a base deck.
 *
 * expected output: >
 *   ILIAS shows nine "Cards" with a title and text each. According to the browser window/desktop size a different
 *   number of cards will be displayed per line.
 * ---
 */
function base()
{
    //Init Factory and Renderer
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    //Generate some content
    $content = $f->listing()->descriptive(
        array(
            "Entry 1" => "Some text",
            "Entry 2" => "Some more text",
        )
    );

    //Define the some responsive image
    $image = $f->image()->responsive(
        "./assets/images/logo/HeaderIcon.svg",
        "Thumbnail Example"
    );

    //Define the card by using the content and the image
    $card = $f->card()->standard(
        "Title",
        $image
    )->withSections(array(
        $content,
    ));

    //Define the deck
    $deck = $f->deck(array($card,$card,$card,$card,$card,
        $card,$card,$card,$card));

    //Render
    return $renderer->render($deck);
}
