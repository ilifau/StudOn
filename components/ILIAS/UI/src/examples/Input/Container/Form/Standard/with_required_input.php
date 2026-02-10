<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Input\Container\Form\Standard;

/**
 * ---
 * description: >
 *   Example showing a Form with required fields. An explaining hint is displayed below the Form.
 *
 * expected output: >
 *   ILIAS shows a form with a mandatory text input.
 *   Above and below the form, the legend sports an asterisk and the word 'Required'
 *   The input's label is also marked with an asterisk.
 *   Submitting the form without giving any value in the field will result in an error.
 * ---
 */
function with_required_input()
{
    global $DIC;
    $ui = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();
    $request = $DIC->http()->request();

    $text_input = $ui->input()->field()
        ->text("Required Input", "User needs to fill this field")
        ->withRequired(true);

    $section = $ui->input()->field()->section(
        [$text_input],
        "Section with required field",
        "The Form should show an explaining hint at the bottom"
    );

    $DIC->ctrl()->setParameterByClass(
        'ilsystemstyledocumentationgui',
        'example_name',
        'required'
    );
    $form_action = $DIC->ctrl()->getFormActionByClass('ilsystemstyledocumentationgui');

    $form = $ui->input()->container()->form()->standard($form_action, [$section]);

    if ($request->getMethod() == "POST"
        && array_key_exists('example_name', $request->getQueryParams())
            && $request->getQueryParams()['example_name'] == 'required') {
        $form = $form->withRequest($request);
        $result = $form->getData();
    } else {
        $result = "No result yet.";
    }

    return
        "<pre>" . print_r($result, true) . "</pre><br/>" .
        $renderer->render($form);
}
