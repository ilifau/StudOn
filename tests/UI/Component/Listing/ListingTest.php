<?php
<<<<<<< HEAD

declare(strict_types=1);
=======
>>>>>>> v9.1

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

<<<<<<< HEAD
=======
declare(strict_types=1);

>>>>>>> v9.1
require_once(__DIR__ . "/../../../../libs/composer/vendor/autoload.php");
require_once(__DIR__ . "/../../Base.php");

use ILIAS\UI\Component as C;
use ILIAS\UI\Implementation\Component\Listing\Factory;

/**
 * Test on Listing implementation.
 */
class ListingTest extends ILIAS_UI_TestBase
{
    public function getListingFactory(): C\Listing\Factory
    {
        return new Factory();
    }

<<<<<<< HEAD
    public function test_implements_factory_interface(): void
=======
    public function testImplementsFactoryInterface(): void
>>>>>>> v9.1
    {
        $f = $this->getListingFactory();

        $this->assertInstanceOf("ILIAS\\UI\\Component\\Listing\\Factory", $f);
        $this->assertInstanceOf(
            "ILIAS\\UI\\Component\\Listing\\Ordered",
            $f->ordered(array("1"))
        );
        $this->assertInstanceOf(
            "ILIAS\\UI\\Component\\Listing\\Unordered",
            $f->unordered(array("1"))
        );
        $this->assertInstanceOf(
            "ILIAS\\UI\\Component\\Listing\\Descriptive",
            $f->descriptive(array("k1" => "c1"))
        );

        $this->assertInstanceOf(
            "ILIAS\\UI\\Component\\Listing\\CharacteristicValue\\Factory",
            $f->characteristicValue()
        );
        $this->assertInstanceOf(
            "ILIAS\\UI\\Component\\Listing\\Entity\\Factory",
            $f->entity()
        );
        $this->assertInstanceOf(
            "ILIAS\\UI\\Component\\Listing\\Property",
            $f->property()
        );
    }

<<<<<<< HEAD
    public function test_ordered_get_items(): void
=======
    public function testOrderedGetItems(): void
>>>>>>> v9.1
    {
        $f = $this->getListingFactory();
        $l = $f->ordered(array("1","2"));

        $items = array("1","2");
        $this->assertEquals($l->getItems(), $items);
    }

<<<<<<< HEAD
    public function test_unordered_get_items(): void
=======
    public function testUnorderedGetItems(): void
>>>>>>> v9.1
    {
        $f = $this->getListingFactory();
        $l = $f->unordered(array("1","2"));

        $items = array("1","2");
        $this->assertEquals($l->getItems(), $items);
    }

<<<<<<< HEAD
    public function test_descriptive_get_items(): void
=======
    public function testDescriptiveGetItems(): void
>>>>>>> v9.1
    {
        $f = $this->getListingFactory();
        $l = $f->descriptive(array("k1" => "c1","k2" => "c2"));

        $items = array("k1" => "c1","k2" => "c2");
        $this->assertEquals($l->getItems(), $items);
    }

<<<<<<< HEAD
    public function test_ordered_with_items(): void
=======
    public function testOrderedWithItems(): void
>>>>>>> v9.1
    {
        $f = $this->getListingFactory();
        $l = $f->ordered(array())->withItems(array("1","2"));

        $items = array("1","2");
        $this->assertEquals($l->getItems(), $items);
    }

<<<<<<< HEAD
    public function test_unordered_with_items(): void
=======
    public function testUnorderedWithItems(): void
>>>>>>> v9.1
    {
        $f = $this->getListingFactory();
        $l = $f->unordered(array())->withItems(array("1","2"));

        $items = array("1","2");
        $this->assertEquals($l->getItems(), $items);
    }

<<<<<<< HEAD
    public function test_descriptive_with_items(): void
=======
    public function testDescriptiveWithItems(): void
>>>>>>> v9.1
    {
        $f = $this->getListingFactory();
        $l = $f->descriptive(array())->withItems(array("k1" => "c1","k2" => "c2"));

        $items = array("k1" => "c1","k2" => "c2");
        $this->assertEquals($l->getItems(), $items);
    }

<<<<<<< HEAD
    public function test_render_ordered_listing(): void
=======
    public function testRenderOrderedListing(): void
>>>>>>> v9.1
    {
        $f = $this->getListingFactory();
        $r = $this->getDefaultRenderer();
        $l = $f->ordered(array("1","2"));

        $html = $this->normalizeHTML($r->render($l));

        $expected = "<ol>" .
                    "\t\t<li>1</li>" .
                    "\t\t<li>2</li>\t" .
                    "</ol>";

        $this->assertEquals($expected, $html);
    }

<<<<<<< HEAD
    public function test_descriptive_invalid_items2(): void
=======
    public function testDescriptiveInvalidItems2(): void
>>>>>>> v9.1
    {
        $f = $this->getListingFactory();

        try {
            $f->descriptive(array("1"));
        } catch (InvalidArgumentException $e) {
            $this->assertEquals("InvalidArgumentException", get_class($e));
        }
    }

<<<<<<< HEAD
    public function test_descriptive_invalid_items3(): void
=======
    public function testDescriptiveInvalidItems3(): void
>>>>>>> v9.1
    {
        $f = $this->getListingFactory();

        try {
            $f->descriptive(array("1","1"));
        } catch (InvalidArgumentException $e) {
            $this->assertEquals("InvalidArgumentException", get_class($e));
        }
    }

<<<<<<< HEAD
    public function test_render_unordered_listing(): void
=======
    public function testRenderUnorderedListing(): void
>>>>>>> v9.1
    {
        $f = $this->getListingFactory();
        $r = $this->getDefaultRenderer();
        $l = $f->unordered(array("1","2"));

        $html = $this->normalizeHTML($r->render($l));

        $expected = "<ul>" .
                "\t\t<li>1</li>" .
                "\t\t<li>2</li>\t" .
                "</ul>";

        $this->assertEquals($expected, $html);
    }

<<<<<<< HEAD
    public function test_render_descriptive_listing(): void
=======
    public function testRenderDescriptiveListing(): void
>>>>>>> v9.1
    {
        $f = $this->getListingFactory();
        $r = $this->getDefaultRenderer();
        $l = $f->descriptive(array("k1" => "c1","k2" => "c2"));

        $html = $this->normalizeHTML($r->render($l));

        $expected = "<dl>" .
                "\t\t<dt>k1</dt>" .
                "\t<dd>c1</dd>" .
                "\t\t<dt>k2</dt>" .
                "\t<dd>c2</dd>\t" .
                "</dl>";

        $this->assertEquals($expected, $html);
    }
}
