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
require_once(__DIR__ . "/../../../../../libs/composer/vendor/autoload.php");
require_once(__DIR__ . "/../../../Base.php");

use ILIAS\UI\Implementation\Component as I;
use ILIAS\UI\Implementation\Component\SignalGenerator;
use ILIAS\UI\Component as C;
use ILIAS\Data;
use ILIAS\Refinery\Factory as Refinery;
use ILIAS\UI\Implementation\Component\Symbol as S;

class DateTimeInputTest extends ILIAS_UI_TestBase
{
    protected DefNamesource $name_source;
    protected Data\Factory $data_factory;
    protected I\Input\Field\Factory $factory;

    public function setUp(): void
    {
        $this->name_source = new DefNamesource();
        $this->data_factory = new Data\Factory();
        $this->factory = $this->buildFactory();
    }

    public function getUIFactory(): NoUIFactory
    {
        return new class () extends NoUIFactory {
            public function symbol(): C\Symbol\Factory
            {
                return new S\Factory(
                    new S\Icon\Factory(),
                    new S\Glyph\Factory(),
                    new S\Avatar\Factory()
                );
            }
        };
    }

    public function getLanguage(): ilLanguageMock
    {
        return new class () extends ilLanguageMock {
            public function getLangKey(): string
            {
                return 'en';
            }
        };
    }

    protected function buildFactory(): I\Input\Field\Factory
    {
        $df = new Data\Factory();
        $language = $this->createMock(ilLanguage::class);

        return new I\Input\Field\Factory(
            $this->createMock(\ILIAS\UI\Implementation\Component\Input\UploadLimitResolver::class),
            new SignalGenerator(),
            $this->data_factory,
            new Refinery($df, $language),
            $language
        );
    }

<<<<<<< HEAD
    public function test_withFormat(): void
=======
    public function testWithFormat(): void
>>>>>>> v9.1
    {
        $format = $this->data_factory->dateFormat()->germanShort();
        $datetime = $this->factory->datetime('label', 'byline')
                                  ->withFormat($format);

        $this->assertEquals(
            $format,
            $datetime->getFormat()
        );
    }

<<<<<<< HEAD
    public function test_withMinValue(): void
=======
    public function testWithMinValue(): void
>>>>>>> v9.1
    {
        $dat = new DateTimeImmutable('2019-01-09');
        $datetime = $this->factory->datetime('label', 'byline')
            ->withMinValue($dat);

        $this->assertEquals(
            $dat,
            $datetime->getMinValue()
        );
    }

<<<<<<< HEAD
    public function test_withMaxValue(): void
=======
    public function testWithMaxValue(): void
>>>>>>> v9.1
    {
        $dat = new DateTimeImmutable('2019-01-09');
        $datetime = $this->factory->datetime('label', 'byline')
            ->withMaxValue($dat);

        $this->assertEquals(
            $dat,
            $datetime->getMaxValue()
        );
    }

<<<<<<< HEAD
    public function test_withUseTime(): void
=======
    public function testWithUseTime(): void
>>>>>>> v9.1
    {
        $datetime = $this->factory->datetime('label', 'byline');
        $this->assertFalse($datetime->getUseTime());
        $this->assertTrue($datetime->withUseTime(true)->getUseTime());
    }

<<<<<<< HEAD
    public function test_withTimeOnly(): void
=======
    public function testWithTimeOnly(): void
>>>>>>> v9.1
    {
        $datetime = $this->factory->datetime('label', 'byline');
        $this->assertFalse($datetime->getTimeOnly());
        $this->assertTrue($datetime->withTimeOnly(true)->getTimeOnly());
    }

<<<<<<< HEAD
    public function test_withTimeZone(): void
=======
    public function testWithTimeZone(): void
>>>>>>> v9.1
    {
        $datetime = $this->factory->datetime('label', 'byline');
        $this->assertNull($datetime->getTimeZone());
        $tz = 'Europe/Moscow';
        $this->assertEquals(
            $tz,
            $datetime->withTimeZone($tz)->getTimeZone()
        );
    }

<<<<<<< HEAD
    public function test_withInvalidTimeZone(): void
=======
    public function testWithInvalidTimeZone(): void
>>>>>>> v9.1
    {
        $this->expectException(InvalidArgumentException::class);
        $datetime = $this->factory->datetime('label', 'byline');
        $tz = 'NOT/aValidTZ';
        $datetime->withTimeZone($tz);
    }

<<<<<<< HEAD
    public function test_jsConfigRendering(): void
    {
        $datetime = $this->factory->datetime('label', 'byline');
        $js_binding = $this->getJavaScriptBinding();
        $this->getDefaultRenderer($js_binding)->render($datetime);

        $expected = '$("#id_1").datetimepicker({'
            . '"showClear":true,'
            . '"sideBySide":true,'
            . '"format":"YYYY-MM-DD",'
            . '"locale":"en"'
            . '});';

        $onload_js = array_shift($js_binding->on_load_code);
        $this->assertEquals($expected, $onload_js);
    }

    public function test_withValueThatIsDateTimeImmutable(): void
    {
        $string_value = "1985-05-04";
=======
    public function testWithValueThatIsDateTimeImmutable(): void
    {
        $string_value = "1985-05-04 00:00";
>>>>>>> v9.1
        $value = new \DateTimeImmutable($string_value);
        $datetime = $this->factory->datetime('label', 'byline')
            ->withValue($value);
        $this->assertEquals(
            $string_value,
            $datetime->getValue()
        );
    }

    public function testWithInvalidValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $datetime = $this->factory->datetime('label', 'byline')
            ->withValue("this is no datetime...");
    }
}
