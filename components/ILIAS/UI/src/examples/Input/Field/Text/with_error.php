<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Input\Field\Text;

/**
 * ---
 * description: >
 *   Example shows how to create and render a basic text input field with an error
 *   attached to it. This example does not contain any data processing.
 *
 * expected output: >
 *   ILIAS shows a text field titled "Basic Input". You can enter numbers and letters into the field.
 *   Below the field, a color-coded error message "Some error" is displayed.
 *   The error is also marked by a colored line on the left of the field's label.
 * ---
 */
function with_error()
{
    //Declare dependencies
    global $DIC;
    $ui = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    //Define the text input field
    $text_input = $ui->input()->field()->text("Basic Input", "Just some basic input
    with some error attached.")
        ->withError("Some error");

    //Define the form and attach the section.
    $form = $ui->input()->container()->form()->standard("#", [$text_input]);

    //Render the form with the text input field
    return $renderer->render($form);
}
