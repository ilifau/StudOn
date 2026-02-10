<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Deck;

/**
 * ---
 * description: >
 *   Example for rendering a user card
 *
 * expected output: >
 *   ILIAS shows seven base cards with a title and text each. A button "Request
 *   Contact" is displayed below each card. Clicking the button won't activate any actions. According to the size of the
 *   browser window/desktop the number of cards displayed in each line will change.
 * ---
 */
function user()
{
    //Init Factory and Renderer
    global $DIC;
    $f = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $address = $f->listing()->descriptive(
        array(
            "Address" => "Hochschlustrasse 6",
            "" => "3006 Bern",
            "Contact" => "timon.amstutz@ilub.unibe.ch"
        )
    );

    //Define the some responsive image
    $image = $f->image()->responsive(
        "./assets/images/logo/HeaderIcon.svg",
        "Thumbnail Example"
    );

    //Define the card by using the image and add a new section with a button
    $card = $f->card()->standard(
        "Timon Amstutz",
        $image
    )->withSections(array($address,$f->button()->standard("Request Contact", "")));

    //Create a deck with large cards
    $deck = $f->deck(array($card,$card,$card,$card,$card,$card,$card))->withLargeCardsSize();

    //Render
    return $renderer->render($deck);
}
