<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Input\Field\Text;

/**
 */
/**
 * ---
 * description: >
 *   The example shows how to create and render a disabled text input field and attach it to a
 *   form. This example does not contain any data processing.
 *
 * expected output: >
 *   ILIAS shows a text field titled "Disabled Input". You cannot activate and type anything into the field.
 * ---
 */
function disabled()
{
    //Step 0: Declare dependencies
    global $DIC;
    $ui = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    //Step 1: Define the text input field
    $text_input = $ui->input()->field()->text("Disabled Input", "Just some disabled input")->withDisabled(true);

    //Step 2: Define the form and attach the section.
    $form = $ui->input()->container()->form()->standard("#", [$text_input]);

    //Step 4: Render the form with the text input field
    return $renderer->render($form);
}
