<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Input\Field\SwitchableGroup;

/**
 * ---
 * description: >
 *   Example showing how a dependent group (aka sub form) might be attached to a radio while being disabled.
 *
 * expected output: >
 *   ILIAS shows a group of two radio buttons titled "Disabled Switchable Group". Selecting a radio button is not possible.
 * ---
 */
function with_disabled()
{
    global $DIC;
    $ui = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();
    $request = $DIC->http()->request();
    $data = new \ILIAS\Data\Factory();

    $group1 = $ui->input()->field()->group([$ui->input()->field()->text("Item 1", "Just some field")], "Group 1");
    $group2 = $ui->input()->field()->group([$ui->input()->field()->text("Item 2", "Just some field")], "Group 2");

    $sg = $ui->input()->field()->switchableGroup(
        [$group1,$group2],
        "Disabled Switchable Group",
        "nothing to pick here."
    )
    ->withDisabled(true);

    return $renderer->render($sg);
}
