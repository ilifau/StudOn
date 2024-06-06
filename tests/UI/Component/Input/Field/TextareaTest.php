<?php

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

declare(strict_types=1);

require_once(__DIR__ . "/../../../../../libs/composer/vendor/autoload.php");
require_once(__DIR__ . "/../../../Base.php");
require_once(__DIR__ . "/InputTest.php");

use ILIAS\UI\Implementation\Component as I;
use ILIAS\UI\Implementation\Component\SignalGenerator;
use ILIAS\UI\Component\Input\Field;
use ILIAS\Data;
use ILIAS\Refinery\Factory as Refinery;

class TextareaTest extends ILIAS_UI_TestBase
{
    private DefNamesource $name_source;

    public function setUp(): void
    {
        $this->name_source = new DefNamesource();
    }

    protected function buildFactory(): I\Input\Field\Factory
    {
        $df = new Data\Factory();
        $language = $this->createMock(ilLanguage::class);
        return new I\Input\Field\Factory(
            $this->createMock(\ILIAS\UI\Implementation\Component\Input\UploadLimitResolver::class),
            new SignalGenerator(),
            $df,
            new Refinery($df, $language),
            $language
        );
    }

<<<<<<< HEAD
    public function test_implements_factory_interface(): void
=======
    public function testImplementsFactoryInterface(): void
>>>>>>> v9.1
    {
        $f = $this->buildFactory();
        $textarea = $f->textarea("label", "byline");
        $this->assertInstanceOf(\ILIAS\UI\Component\Input\Container\Form\FormInput::class, $textarea);
        $this->assertInstanceOf(Field\Textarea::class, $textarea);
    }

<<<<<<< HEAD
    public function test_implements_factory_interface_without_byline(): void
=======
    public function testImplementsFactoryInterface_without_byline(): void
>>>>>>> v9.1
    {
        $f = $this->buildFactory();
        $textarea = $f->textarea("label");
        $this->assertInstanceOf(\ILIAS\UI\Component\Input\Container\Form\FormInput::class, $textarea);
        $this->assertInstanceOf(Field\Textarea::class, $textarea);
    }

<<<<<<< HEAD
    public function test_with_min_limit(): void
=======
    public function testWithMinLimit(): void
>>>>>>> v9.1
    {
        $f = $this->buildFactory();
        $limit = 5;
        $textarea = $f->textarea('label')->withMinLimit($limit);
        $this->assertInstanceOf(\ILIAS\UI\Component\Input\Container\Form\FormInput::class, $textarea);
        $this->assertInstanceOf(Field\Textarea::class, $textarea);
        $this->assertEquals($textarea->getMinLimit(), $limit);
    }

<<<<<<< HEAD
    public function test_with_max_limit(): void
=======
    public function testWithMaxLimit(): void
>>>>>>> v9.1
    {
        $f = $this->buildFactory();
        $limit = 15;
        $textarea = $f->textarea('label')->withMaxLimit($limit);
        $this->assertInstanceOf(\ILIAS\UI\Component\Input\Container\Form\FormInput::class, $textarea);
        $this->assertInstanceOf(Field\Textarea::class, $textarea);
        $this->assertEquals($textarea->getMaxLimit(), $limit);
    }

<<<<<<< HEAD
    public function test_is_limited(): void
=======
    public function testIsLimited(): void
>>>>>>> v9.1
    {
        $f = $this->buildFactory();

        // with min limit
        $textarea = $f->textarea('label')->withMinLimit(5);
        $this->assertTrue($textarea->isLimited());

        // with max limit
        $textarea = $f->textarea('label')->withMaxLimit(5);
        $this->assertTrue($textarea->isLimited());

        // with min-max limit
        $textarea = $f->textarea('label')->withMinLimit(5)->withMaxLimit(20);
        $this->assertTrue($textarea->isLimited());

        // without limit
        $textarea = $f->textarea('label');
        $this->assertFalse($textarea->isLimited());
    }

<<<<<<< HEAD
    public function test_get_min_limit(): void
=======
    public function testGetMinLimit(): void
>>>>>>> v9.1
    {
        $f = $this->buildFactory();
        $limit = 5;
        $textarea = $f->textarea('label')->withMinLimit($limit);
        $this->assertEquals($textarea->getMinLimit(), $limit);
    }

<<<<<<< HEAD
    public function test_get_max_limit(): void
=======
    public function testGetMaxLimit(): void
>>>>>>> v9.1
    {
        $f = $this->buildFactory();
        $limit = 15;
        $textarea = $f->textarea('label')->withMaxLimit($limit);
        $this->assertEquals($textarea->getMaxLimit(), $limit);
    }

    // RENDERER
<<<<<<< HEAD
    public function test_renderer(): void
=======
    public function testRenderer(): void
>>>>>>> v9.1
    {
        $f = $this->buildFactory();
        $r = $this->getDefaultRenderer();
        $id = "id_1";
        $label = "label";
        $byline = "byline";
        $name = "name_0";
        $textarea = $f->textarea($label, $byline)->withNameFrom($this->name_source);

        $expected = "
            <div class=\"form-group row\">
                <label for=\"$id\" class=\"control-label col-sm-4 col-md-3 col-lg-2\">$label</label>
                <div class=\"col-sm-8 col-md-9 col-lg-10\">
                    <div class=\"ui-input-textarea\">
                        <textarea id=\"$id\" class=\"form-control form-control-sm\" name=\"$name\"></textarea>
                    </div>
                    <div class=\"help-block\">$byline</div>
                </div>
            </div>
        ";

        $html = $this->normalizeHTML($r->render($textarea));
        $this->assertHTMLEquals($expected, $html);
    }

<<<<<<< HEAD
    public function test_renderer_with_min_limit(): void
=======
    public function testRendererWithMinLimit(): void
>>>>>>> v9.1
    {
        $f = $this->buildFactory();
        $r = $this->getDefaultRenderer();
        $name = "name_0";
        $id = "id_1";
        $label = "label";

        $min = 5;
        $byline = "This is just a byline Min: " . $min;
        $textarea = $f->textarea($label, $byline)->withMinLimit($min)->withNameFrom($this->name_source);

        $expected = "
            <div class=\"form-group row\">
                <label for=\"$id\" class=\"control-label col-sm-4 col-md-3 col-lg-2\">$label</label>
                <div class=\"col-sm-8 col-md-9 col-lg-10\">
                    <div class=\"ui-input-textarea\">
                        <textarea id=\"$id\" class=\"form-control form-control-sm\" name=\"$name\" minlength=\"$min\"></textarea>
                    </div>
                    <div class=\"help-block\">$byline</div>
                </div>
            </div>
        ";

        $html = $this->normalizeHTML($r->render($textarea));
        $this->assertHTMLEquals($expected, $html);
    }

<<<<<<< HEAD
    public function test_renderer_with_max_limit(): void
=======
    public function testRendererWithMaxLimit(): void
>>>>>>> v9.1
    {
        $f = $this->buildFactory();
        $r = $this->getDefaultRenderer();
        $name = "name_0";
        $id = "id_1";
        $label = "label";
        $max = 20;
        $byline = "This is just a byline Max: " . $max;
        $textarea = $f->textarea($label, $byline)->withMaxLimit($max)->withNameFrom($this->name_source);

        $expected = "
            <div class=\"form-group row\">
                <label for=\"$id\" class=\"control-label col-sm-4 col-md-3 col-lg-2\">$label</label>
                <div class=\"col-sm-8 col-md-9 col-lg-10\">
                    <div class=\"ui-input-textarea\">
                        <textarea id=\"$id\" class=\"form-control form-control-sm\" name=\"$name\" maxlength=\"$max\"></textarea>
                        <div class=\"ui-input-textarea-remainder\"> ui_chars_remaining <span data-action=\"remainder\">$max</span> </div>
                    </div>
                    <div class=\"help-block\">$byline</div>
                </div>
            </div>
        ";

        $html = $this->brutallyTrimHTML($r->render($textarea));
        $this->assertHTMLEquals($this->brutallyTrimHTML($expected), $html);
    }

<<<<<<< HEAD
    public function test_renderer_with_min_and_max_limit(): void
=======
    public function testRendererWithMinAndMaxLimit(): void
>>>>>>> v9.1
    {
        $f = $this->buildFactory();
        $r = $this->getDefaultRenderer();
        $name = "name_0";
        $id = "id_1";
        $label = "label";
        $min = 5;
        $max = 20;
        $byline = "This is just a byline Min: " . $min . " Max: " . $max;
        $textarea = $f->textarea($label, $byline)->withMinLimit($min)->withMaxLimit($max)->withNameFrom(
            $this->name_source
        );

        $expected = "
            <div class=\"form-group row\">
                <label for=\"$id\" class=\"control-label col-sm-4 col-md-3 col-lg-2\">$label</label>
                <div class=\"col-sm-8 col-md-9 col-lg-10\">
                    <div class=\"ui-input-textarea\">
                        <textarea id=\"$id\" class=\"form-control form-control-sm\" name=\"$name\" minlength=\"5\" maxlength=\"20\"></textarea>
                        <div class=\"ui-input-textarea-remainder\"> ui_chars_remaining <span data-action=\"remainder\">$max</span> </div>
                    </div>
                    <div class=\"help-block\">$byline</div>
                </div>
            </div>
        ";

        $html = $this->brutallyTrimHTML($r->render($textarea));
        $this->assertHTMLEquals($this->brutallyTrimHTML($expected), $html);
    }

<<<<<<< HEAD
    public function test_renderer_counter_with_value(): void
=======
    public function testRendererCounterWithValue(): void
>>>>>>> v9.1
    {
        $f = $this->buildFactory();
        $r = $this->getDefaultRenderer();
        $id = 'id_1';
        $label = "label";
        $byline = "byline";
        $name = "name_0";
        $value = "Lorem ipsum dolor sit";
        $textarea = $f->textarea($label, $byline)->withValue($value)->withNameFrom($this->name_source);

        $expected = "
            <div class=\"form-group row\">
                <label for=\"$id\" class=\"control-label col-sm-4 col-md-3 col-lg-2\">$label</label>
                <div class=\"col-sm-8 col-md-9 col-lg-10\">
                    <div class=\"ui-input-textarea\">
                        <textarea id=\"$id\" class=\"form-control form-control-sm\" name=\"$name\">$value</textarea>
                    </div>
                    <div class=\"help-block\">$byline</div>
                </div>
            </div>
        ";

        $html = $this->normalizeHTML($r->render($textarea));
        $this->assertHTMLEquals($expected, $html);
    }

<<<<<<< HEAD
    public function test_renderer_with_error(): void
=======
    public function testRendererWithError(): void
>>>>>>> v9.1
    {
        $f = $this->buildFactory();
        $r = $this->getDefaultRenderer();
        $id = "id_1";
        $label = "label";
        $name = "name_0";
        $min = 5;
        $byline = "This is just a byline Min: " . $min;
        $error = "an_error";
        $textarea = $f->textarea($label, $byline)->withNameFrom($this->name_source)->withError($error);

<<<<<<< HEAD
        $expected = $this->brutallyTrimHTML('
<div class="form-group row">
   <label for="id_1" class="control-label col-sm-4 col-md-3 col-lg-2">label</label>
   <div class="col-sm-8 col-md-9 col-lg-10">
      <div class="help-block alert alert-danger" aria-describedby="id_1" role="alert">an_error</div>
      <textarea id="id_1" name="name_0" class="form-control form-control-sm"></textarea>
      <div class="help-block">This is just a byline Min: 5</div>
   </div>
</div>
');
=======
        $expected = "
            <div class=\"form-group row\">
                <label for=\"$id\" class=\"control-label col-sm-4 col-md-3 col-lg-2\">$label</label>
                <div class=\"col-sm-8 col-md-9 col-lg-10\">
                    <div class=\"help-block alert alert-danger\" aria-describedby=\"$id\" role=\"alert\">an_error</div>
                    <div class=\"ui-input-textarea\">
                        <textarea id=\"$id\" class=\"form-control form-control-sm\" name=\"$name\"></textarea>
                    </div>
                    <div class=\"help-block\">$byline</div>
                </div>
            </div>
        ";
>>>>>>> v9.1

        $html = $this->brutallyTrimHTML($r->render($textarea));
        $this->assertEquals($this->brutallyTrimHTML($expected), $html);
    }

<<<<<<< HEAD
    public function test_renderer_with_disabled(): void
=======
    public function testRendererWithDisabled(): void
>>>>>>> v9.1
    {
        $f = $this->buildFactory();
        $r = $this->getDefaultRenderer();
        $id = "id_1";
        $label = "label";
        $byline = "byline";
        $name = "name_0";
        $textarea = $f->textarea($label, $byline)->withNameFrom($this->name_source)->withDisabled(true);

        $expected = "
            <div class=\"form-group row\">
                <label for=\"$id\" class=\"control-label col-sm-4 col-md-3 col-lg-2\">$label</label>
                <div class=\"col-sm-8 col-md-9 col-lg-10\">
                    <div class=\"ui-input-textarea\">
                        <textarea id=\"$id\" class=\"form-control form-control-sm\" name=\"$name\" disabled=\"disabled\"></textarea>
                    </div>
                    <div class=\"help-block\">$byline</div>
                </div>
            </div>
        ";

        $html = $this->normalizeHTML($r->render($textarea));
        $this->assertHTMLEquals($expected, $html);
    }

<<<<<<< HEAD
    public function test_stripsTags(): void
=======
    public function testStripsTags(): void
>>>>>>> v9.1
    {
        $f = $this->buildFactory();
        $name = "name_0";
        $text = $f->textarea("")
            ->withNameFrom($this->name_source)
            ->withInput(new DefInputData([$name => "<script>alert()</script>"]));

        $content = $text->getContent();
        $this->assertEquals("alert()", $content->value());
    }
}
