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
use ILIAS\UI\Implementation\Component\Signal;
use ILIAS\UI\Implementation\Component\Image\Factory;

/**
 * Test on button implementation.
 */
class ImageTest extends ILIAS_UI_TestBase
{
    /**
     * @return Factory
     */
    public function getImageFactory(): Factory
    {
        return new Factory();
    }


<<<<<<< HEAD
    public function test_implements_factory_interface(): void
=======
    public function testImplementsFactoryInterface(): void
>>>>>>> v9.1
    {
        $f = $this->getImageFactory();

        $this->assertInstanceOf("ILIAS\\UI\\Component\\Image\\Factory", $f);
        $this->assertInstanceOf("ILIAS\\UI\\Component\\Image\\Image", $f->standard("source", "alt"));
        $this->assertInstanceOf("ILIAS\\UI\\Component\\Image\\Image", $f->responsive("source", "alt"));
    }

<<<<<<< HEAD
    public function test_get_type(): void
=======
    public function testGetType(): void
>>>>>>> v9.1
    {
        $f = $this->getImageFactory();
        $i = $f->standard("source", "alt");

        $this->assertEquals($i::STANDARD, $i->getType());
    }

<<<<<<< HEAD
    public function test_get_source(): void
=======
    public function testGetSource(): void
>>>>>>> v9.1
    {
        $f = $this->getImageFactory();
        $i = $f->standard("source", "alt");

        $this->assertEquals("source", $i->getSource());
    }

<<<<<<< HEAD
    public function test_get_alt(): void
=======
    public function testGetAlt(): void
>>>>>>> v9.1
    {
        $f = $this->getImageFactory();
        $i = $f->standard("source", "alt");

        $this->assertEquals("alt", $i->getAlt());
    }

<<<<<<< HEAD
    public function test_set_source(): void
=======
    public function testSetSource(): void
>>>>>>> v9.1
    {
        $f = $this->getImageFactory();
        $i = $f->standard("source", "alt");
        $i = $i->withSource("newSource");
        $this->assertEquals("newSource", $i->getSource());
    }

<<<<<<< HEAD
    public function test_set_alt(): void
=======
    public function testSetAlt(): void
>>>>>>> v9.1
    {
        $f = $this->getImageFactory();
        $i = $f->standard("source", "alt");
        $i = $i->withAlt("newAlt");
        $this->assertEquals("newAlt", $i->getAlt());
    }

<<<<<<< HEAD
    public function test_set_string_action(): void
=======
    public function testSetStringAction(): void
>>>>>>> v9.1
    {
        $f = $this->getImageFactory();
        $i = $f->standard("source", "alt");
        $i = $i->withAction("newAction");
        $this->assertEquals("newAction", $i->getAction());
    }

<<<<<<< HEAD
    public function test_set_signal_action(): void
=======
    public function testSetSignalAction(): void
>>>>>>> v9.1
    {
        $f = $this->getImageFactory();
        $signal = $this->createMock(C\Signal::class);
        $i = $f->standard("source", "alt");
        $i = $i->withAction($signal);
        $this->assertEquals([$signal], $i->getAction());
    }

<<<<<<< HEAD
    public function test_invalid_source(): void
=======
    public function testSetAdditionalHighResSources(): void
    {
        $additional_sources = [
            600 => 'image1',
            300 => 'image2'
        ];
        $f = $this->getImageFactory();
        $i = $f->standard("source", "alt");
        foreach($additional_sources as $min_width_in_pixels => $source) {
            $i = $i->withAdditionalHighResSource($source, $min_width_in_pixels);
        }
        $this->assertEquals($additional_sources, $i->getAdditionalHighResSources());
    }

    public function testInvalidSource(): void
>>>>>>> v9.1
    {
        $this->expectException(TypeError::class);
        $f = $this->getImageFactory();
        $f->standard(1, "alt");
    }

<<<<<<< HEAD
    public function test_invalid_alt(): void
=======
    public function testInvalidAlt(): void
>>>>>>> v9.1
    {
        $this->expectException(TypeError::class);
        $f = $this->getImageFactory();
        $f->standard("source", 1);
    }

<<<<<<< HEAD
    public function test_render_standard(): void
=======
    public function testInvalidAdditionalHighResSource(): void
    {
        $this->expectException(TypeError::class);
        $f = $this->getImageFactory();
        $f->standard("source", 1)->withAdditionalHighResSource(
            1,
            1
        );
    }

    public function testInvalidAdditionalHighResSourceSize(): void
    {
        $this->expectException(TypeError::class);
        $f = $this->getImageFactory();
        $f->standard("source", 1)->withAdditionalHighResSource(
            '#',
            '#'
        );
    }

    public function testRenderStandard(): void
>>>>>>> v9.1
    {
        $f = $this->getImageFactory();
        $r = $this->getDefaultRenderer();
        $i = $f->standard("source", "alt");

        $html = $this->normalizeHTML($r->render($i));

        $expected = "<img src=\"source\" class=\"img-standard\" alt=\"alt\" />";

        $this->assertEquals($expected, $html);
    }

<<<<<<< HEAD
    public function test_render_responsive(): void
=======
    public function testRenderResponsive(): void
>>>>>>> v9.1
    {
        $f = $this->getImageFactory();
        $r = $this->getDefaultRenderer();
        $i = $f->responsive("source", "alt");

        $html = $this->normalizeHTML($r->render($i));

        $expected = "<img src=\"source\" class=\"img-responsive\" alt=\"alt\" />";

        $this->assertEquals($expected, $html);
    }

<<<<<<< HEAD
    public function test_render_alt_escaping(): void
=======
    public function testRenderAltEscaping(): void
>>>>>>> v9.1
    {
        $f = $this->getImageFactory();
        $r = $this->getDefaultRenderer();
        $i = $f->responsive("source", "\"=test;\")(blah\"");

        $html = $this->normalizeHTML($r->render($i));

        $expected = "<img src=\"source\" class=\"img-responsive\" alt=\"&quot;=test;&quot;)(blah&quot;\" />";

        $this->assertEquals($expected, $html);
    }

<<<<<<< HEAD
    public function test_render_with_string_action(): void
=======
    public function testRenderWithStringAction(): void
>>>>>>> v9.1
    {
        $f = $this->getImageFactory();
        $r = $this->getDefaultRenderer();
        $i = $f->standard("source", "alt")->withAction("action");

        $html = $this->normalizeHTML($r->render($i));

        $expected = "<a href=\"action\"><img src=\"source\" class=\"img-standard\" alt=\"alt\" /></a>";

        $this->assertEquals($expected, $html);
    }

<<<<<<< HEAD
    public function test_render_with_signal_action(): void
=======
    public function testRenderWithSignalAction(): void
>>>>>>> v9.1
    {
        $f = $this->getImageFactory();
        $r = $this->getDefaultRenderer();
        $signal = $this->createMock(Signal::class);

        $i = $f->standard("source", "alt")->withAction($signal);

        $html = $this->normalizeHTML($r->render($i));

        $expected = "<a href=\"#\" id=\"id_1\"><img src=\"source\" class=\"img-standard\" alt=\"alt\" /></a>";

        $this->assertEquals($expected, $html);
    }

<<<<<<< HEAD
    public function test_with_empty_action_and_no_additional_on_load_code(): void
=======
    public function testWithEmptyActionAndNoAdditionalOnLoadCode(): void
>>>>>>> v9.1
    {
        $f = $this->getImageFactory();
        $r = $this->getDefaultRenderer();

        $i = $f->standard("source", "alt")->withAction("#");

        $html = $this->normalizeHTML($r->render($i));

        $expected = "<a href=\"#\"><img src=\"source\" class=\"img-standard\" alt=\"alt\" /></a>";

        $this->assertEquals($expected, $html);
    }

<<<<<<< HEAD
    public function test_with_additional_on_load_code(): void
=======
    public function testWithAdditionalOnLoadCode(): void
>>>>>>> v9.1
    {
        $f = $this->getImageFactory();
        $r = $this->getDefaultRenderer();

        $i = $f->standard("source", "alt")->withAction("#")->withOnLoadCode(function ($id) {
            return "Something";
        });

        $html = $this->normalizeHTML($r->render($i));

        $expected = "<a href=\"#\"><img src=\"source\" class=\"img-standard\" id='id_1'  alt=\"alt\" /></a>";

        $this->assertEquals($expected, $html);
    }
}
