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
use ILIAS\UI\Implementation as I;
use ILIAS\UI\Implementation\Component\Card\Factory;

/**
 * Test on card implementation.
 */
class CardTest extends ILIAS_UI_TestBase
{
    public function getFactory(): NoUIFactory
    {
        return new class () extends NoUIFactory {
            public function legacy($content): C\Legacy\Legacy
            {
                $f = new I\Component\Legacy\Factory(new I\Component\SignalGenerator());
                return $f->legacy($content);
            }
        };
    }

    private function getCardFactory(): I\Component\Card\Factory
    {
        return new Factory();
    }

    private function getBaseCard(): I\Component\Card\Standard
    {
        $cf = $this->getCardFactory();
        $image = new I\Component\Image\Image("standard", "src", "alt");

        return $cf->standard("Card Title", $image);
    }

<<<<<<< HEAD
    public function test_implements_factory_interface(): void
=======
    public function testImplementsFactoryInterface(): void
>>>>>>> v9.1
    {
        $this->assertInstanceOf("ILIAS\\UI\\Component\\Card\\Standard", $this->getBaseCard());
    }

<<<<<<< HEAD
    public function test_factory_with_shy_button(): void
=======
    public function testFactoryWithShyButton(): void
>>>>>>> v9.1
    {
        $button_factory = new I\Component\Button\Factory();
        $button = $button_factory->shy("Card Title New", "");

        $cf = $this->getCardFactory();
        $image = new I\Component\Image\Image("standard", "src", "alt");

        $this->assertEquals($button, $cf->standard($button, $image)->getTitle());
    }

<<<<<<< HEAD
    public function test_get_title(): void
=======
    public function testGetTitle(): void
>>>>>>> v9.1
    {
        $c = $this->getBaseCard();

        $this->assertEquals("Card Title", $c->getTitle());
    }

<<<<<<< HEAD
    public function test_with_title(): void
=======
    public function testWithTitle(): void
>>>>>>> v9.1
    {
        $c = $this->getBaseCard();
        $c = $c->withTitle("Card Title New");

        $this->assertEquals("Card Title New", $c->getTitle());
    }

<<<<<<< HEAD
    public function test_with_title_as_shy_button(): void
=======
    public function testWithTitleAsShyButton(): void
>>>>>>> v9.1
    {
        $c = $this->getBaseCard();
        $button_factory = new I\Component\Button\Factory();
        $button = $button_factory->shy("Card Title New", "");

        $c = $c->withTitle($button);
        $this->assertEquals($button, $c->getTitle());
    }

<<<<<<< HEAD
    public function test_with_string_title_action(): void
=======
    public function testWithStringTitleAction(): void
>>>>>>> v9.1
    {
        $c = $this->getBaseCard();
        $c = $c->withTitleAction("newAction");
        $this->assertEquals("newAction", $c->getTitleAction());
    }

<<<<<<< HEAD
    public function test_with_signal_title_action(): void
=======
    public function testWithSignalTitleAction(): void
>>>>>>> v9.1
    {
        $c = $this->getBaseCard();
        $signal = $this->createMock(C\Signal::class);
        $c = $c->withTitleAction($signal);
        $this->assertEquals([$signal], $c->getTitleAction());
    }

<<<<<<< HEAD
    public function test_with_highlight(): void
=======
    public function testWithHighlight(): void
>>>>>>> v9.1
    {
        $c = $this->getBaseCard();
        $c = $c->withHighlight(true);
        $this->assertTrue($c->isHighlighted());
    }

<<<<<<< HEAD
    public function test_get_image(): void
=======
    public function testGetImage(): void
>>>>>>> v9.1
    {
        $card = $this->getBaseCard();
        $image = new I\Component\Image\Image("standard", "src", "alt");

        $this->assertEquals($card->getImage(), $image);
    }

<<<<<<< HEAD
    public function test_with_image(): void
=======
    public function testWithImage(): void
>>>>>>> v9.1
    {
        $card = $this->getBaseCard();
        $image_new = new I\Component\Image\Image("standard", "src/new", "alt");
        $c = $card->withImage($image_new);

        $this->assertEquals($c->getImage(), $image_new);
    }

<<<<<<< HEAD
    public function test_with_section(): void
=======
    public function testWithSection(): void
>>>>>>> v9.1
    {
        $f = $this->getFactory();
        $c = $this->getBaseCard();
        $content = $f->legacy("Random Content");
        $c = $c->withSections(array($content));

        $this->assertEquals($c->getSections(), array($content));
    }

<<<<<<< HEAD
    public function test_render_content_full(): void
=======
    public function testRenderContentFull(): void
>>>>>>> v9.1
    {
        $r = $this->getDefaultRenderer();
        $c = $this->getBaseCard();
        $content = $this->getFactory()->legacy("Random Content");

        $c = $c->withSections(array($content));

        $html = $this->brutallyTrimHTML($r->render($c));

        $expected_html =
                "<div class=\"il-card thumbnail\">" .
                "   <div class=\"il-card-image-container\"><img src=\"src\" class=\"img-standard\" alt=\"open Card Title\" /></div>" .
                "   <div class=\"card-no-highlight\"></div>" .
                "   <div class=\"caption card-title\">Card Title</div>" .
                "   <div class=\"caption\">Random Content</div>" .
                "</div>";

        $this->assertHTMLEquals($this->brutallyTrimHTML($expected_html), $html);
    }

<<<<<<< HEAD
    public function test_render_content_with_highlight(): void
=======
    public function testRenderContentWithHighlight(): void
>>>>>>> v9.1
    {
        $r = $this->getDefaultRenderer();
        $c = $this->getBaseCard();
        $c = $c->withHighlight(true);

        $html = $this->brutallyTrimHTML($r->render($c));

        $expected_html =
            "<div class=\"il-card thumbnail\">" .
            "   <div class=\"il-card-image-container\"><img src=\"src\" class=\"img-standard\" alt=\"open Card Title\" /></div>" .
            "   <div class=\"card-highlight\"></div>" .
            "   <div class=\"caption card-title\">Card Title</div>" .
            "</div>";

        $this->assertHTMLEquals($this->brutallyTrimHTML($expected_html), $html);
    }
}
