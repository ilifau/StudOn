<?php

declare(strict_types=1);

namespace ILIAS\UI\examples\Tree\Node\Simple;

/**
 * ---
 * description: >
 *   Example for rendering a simple tree node with a link.
 *
 * expected output: >
 *   ILIAS shows a list with two entries. The first entry consists of one single word. The second entry includes an icon
 *   and a link which redirects to ilias.de.
 * ---
 */
function simpleWithLink()
{
    global $DIC;
    $factory = $DIC->ui()->factory();
    $renderer = $DIC->ui()->renderer();

    $icon = $factory->symbol()
        ->icon()
        ->standard("crs", 'Example');

    $node1 = $factory->tree()
        ->node()
        ->simple('label');


    $uri = new \ILIAS\Data\URI('https://ilias.de');

    $node2 = $factory->tree()
        ->node()
        ->simple('label', $icon, $uri);

    $data = [['node' => $node1], ['node' => $node2]];

    $recursion = new class () implements \ILIAS\UI\Component\Tree\TreeRecursion {
        public function getChildren($record, $environment = null): array
        {
            return [];
        }

        public function build(
            \ILIAS\UI\Component\Tree\Node\Factory $factory,
            $record,
            $environment = null
        ): \ILIAS\UI\Component\Tree\Node\Node {
            return $record['node'];
        }
    };

    $tree = $factory->tree()->expandable('Label', $recursion)
              ->withData($data);

    return $renderer->render([$tree]);
}
