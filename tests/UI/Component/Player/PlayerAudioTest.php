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
use ILIAS\UI\Component\Input\Field\Factory as FieldFactory;

/**
 * @author Alexander Killing <killing@leifos.de>
 */
class PlayerAudioTest extends ILIAS_UI_TestBase
{
    public function getUIFactory(): NoUIFactory
    {
<<<<<<< HEAD
        $field_factory = $this->createMock(FieldFactory::class);
        return new class ($field_factory) extends NoUIFactory {
            protected FieldFactory $factory;
            public function __construct(FieldFactory $factory)
            {
                $this->factory = $factory;
=======
        return new class (
            $this->createMock(C\Modal\InterruptiveItem\Factory::class),
            $this->createMock(FieldFactory::class),
        ) extends NoUIFactory {
            public function __construct(
                protected C\Modal\InterruptiveItem\Factory $item_factory,
                protected FieldFactory $field_factory,
            ) {
>>>>>>> v9.1
            }

            public function modal(): C\Modal\Factory
            {
<<<<<<< HEAD
                return new I\Component\Modal\Factory(new I\Component\SignalGenerator(), $this->factory);
=======
                return new I\Component\Modal\Factory(
                    new I\Component\SignalGenerator(),
                    $this->item_factory,
                    $this->field_factory,
                );
>>>>>>> v9.1
            }
            public function button(): C\Button\Factory
            {
                return new I\Component\Button\Factory();
            }
        };
    }

    public function getFactory(): C\Player\Factory
    {
        return new I\Component\Player\Factory();
    }

<<<<<<< HEAD
    public function test_implements_factory_interface(): void
=======
    public function testImplementsFactoryInterface(): void
>>>>>>> v9.1
    {
        $f = $this->getFactory();

        $audio = $f->audio("/foo", "bar");

        $this->assertInstanceOf("ILIAS\\UI\\Component\\Player\\Audio", $audio);
    }

<<<<<<< HEAD
    public function test_get_title_get_source(): void
=======
    public function testGetTitleGetSource(): void
>>>>>>> v9.1
    {
        $f = $this->getFactory();

        $audio = $f->audio("/foo");

        $this->assertEquals("/foo", $audio->getSource());
    }

<<<<<<< HEAD
    public function test_get_title_get_transcript(): void
=======
    public function testGetTitleGetTranscript(): void
>>>>>>> v9.1
    {
        $f = $this->getFactory();

        $audio = $f->audio("/foo", "bar");

        $this->assertEquals("bar", $audio->getTranscription());
    }

<<<<<<< HEAD
    public function test_render_audio(): void
=======
    public function testRenderAudio(): void
>>>>>>> v9.1
    {
        $f = $this->getFactory();
        $r = $this->getDefaultRenderer();

        $audio = $f->audio("/foo");

        $html = $r->render($audio);

        $expected = <<<EOT
<div class="il-audio-container">
    <audio class="il-audio-player" id="id_1" src="/foo" preload="metadata"></audio>
</div>
EOT;
        $this->assertHTMLEquals(
            $this->brutallyTrimHTML($expected),
            $this->brutallyTrimHTML($html)
        );
    }

<<<<<<< HEAD
    public function test_render_with_transcript(): void
=======
    public function testRenderWithTranscript(): void
>>>>>>> v9.1
    {
        $f = $this->getFactory();
        $r = $this->getDefaultRenderer();

        $audio = $f->audio("/foo", "x*123");

        $html = $r->render($audio);

        $this->assertEquals(
            true,
            is_int(strpos($html, "ui_transcription</button>"))
        );
        $this->assertEquals(
            true,
            is_int(strpos($html, "il-modal-lightbox"))
        );
        $this->assertEquals(
            true,
            is_int(strpos($html, "x*123"))
        );
    }
}
