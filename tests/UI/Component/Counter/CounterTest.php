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
require_once("libs/composer/vendor/autoload.php");
require_once(__DIR__ . "/../../Base.php");

use ILIAS\UI\Component as C;
use ILIAS\UI\Implementation\Component\Counter\Counter;
use ILIAS\UI\Implementation\Component\Counter\Factory;

/**
 * Defines tests that a counter implementation should pass.
 */
class CounterTest extends ILIAS_UI_TestBase
{
    public function getCounterFactory(): Factory
    {
        return new Factory();
    }

<<<<<<< HEAD
    public function test_implements_factory_interface(): void
=======
    public function testImplementsFactoryInterface(): void
>>>>>>> v9.1
    {
        $f = $this->getCounterFactory();

        $this->assertInstanceOf("ILIAS\\UI\\Component\\Counter\\Factory", $f);

        $this->assertInstanceOf("ILIAS\\UI\\Component\\Counter\\Counter", $f->status(0));
        $this->assertInstanceOf("ILIAS\\UI\\Component\\Counter\\Counter", $f->novelty(0));
    }

    /**
     * @dataProvider getNumberProvider
     */
<<<<<<< HEAD
    public function test_status_counter(int $number): void
=======
    public function testStatusCounter(int $number): void
>>>>>>> v9.1
    {
        $f = $this->getCounterFactory();

        $c = $f->status($number);

        $this->assertNotNull($c);
        $this->assertEquals(C\Counter\Counter::STATUS, $c->getType());
        $this->assertEquals($number, $c->getNumber());
    }

    /**
     * @dataProvider getNumberProvider
     */
<<<<<<< HEAD
    public function test_novelty_counter(int $number): void
=======
    public function testNoveltyCounter(int $number): void
>>>>>>> v9.1
    {
        $f = $this->getCounterFactory();

        $c = $f->novelty($number);

        $this->assertNotNull($c);
        $this->assertEquals(C\Counter\Counter::NOVELTY, $c->getType());
        $this->assertEquals($number, $c->getNumber());
    }

<<<<<<< HEAD
    public function test_known_counters_only(): void
=======
    public function testKnownCountersOnly(): void
>>>>>>> v9.1
    {
        $this->expectException(InvalidArgumentException::class);
        new Counter("FOO", 1);
    }

    /**
     * @dataProvider getNoNumberProvider
     */
<<<<<<< HEAD
    public function test_int_numbers_only($no_number): void
=======
    public function testIntNumbersOnly($no_number): void
>>>>>>> v9.1
    {
        $this->expectException(TypeError::class);
        $f = $this->getCounterFactory();
        $f->status($no_number);
    }

<<<<<<< HEAD
    public function number_provider(): array
=======
    public function getNumberProvider(): array
>>>>>>> v9.1
    {
        return [
            [-13],
            [0],
            [23],
            [4]
        ];
    }

<<<<<<< HEAD
    public function no_number_provider(): array
=======
    public function getNoNumberProvider(): array
>>>>>>> v9.1
    {
        return [
            ["foo"],
            [9.1],
            [array()],
            [new stdClass()]
        ];
    }

    public static array $canonical_css_classes = [
        "status" => "badge badge-notify il-counter-status",
        "novelty" => "badge badge-notify il-counter-novelty"
    ];

    /**
     * @dataProvider getCounterTypeAndNumberProvider
     */
<<<<<<< HEAD
    public function test_render_status(string $type, int $number): void
=======
    public function testRenderStatus(string $type, int $number): void
>>>>>>> v9.1
    {
        $f = $this->getCounterFactory();
        $r = $this->getDefaultRenderer();
        $c = $f->$type($number);

        $html = $this->normalizeHTML($r->render($c));

        $css_classes = self::$canonical_css_classes[$type];
        $expected = "<span class=\"il-counter\"><span class=\"$css_classes\">$number</span></span>";
        $this->assertHTMLEquals($expected, $html);
    }

<<<<<<< HEAD
    public function counter_type_and_number_provider(): array
=======
    public function getCounterTypeAndNumberProvider(): array
>>>>>>> v9.1
    {
        return [
            ["status", 42],
            ["novelty", 13],
            ["status", 1],
            ["novelty", 23],
        ];
    }
}
