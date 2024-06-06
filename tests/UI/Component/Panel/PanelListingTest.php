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

/**
 * Test listing panels
 */
class PanelListingTest extends ILIAS_UI_TestBase
{
    public function getFactory(): C\Panel\Listing\Factory
    {
        return new I\Component\Panel\Listing\Factory();
    }

<<<<<<< HEAD
    public function test_implements_factory_interface(): void
=======
    public function testImplementsFactoryInterface(): void
>>>>>>> v9.1
    {
        $f = $this->getFactory();

        $std_list = $f->standard("List Title", array(
            new I\Component\Item\Group("Subtitle 1", array(
                new I\Component\Item\Standard("title1"),
                new I\Component\Item\Standard("title2")
            )),
            new I\Component\Item\Group("Subtitle 2", array(
                new I\Component\Item\Standard("title3")
            ))
        ));

        $this->assertInstanceOf("ILIAS\\UI\\Component\\Panel\\Listing\\Standard", $std_list);
    }

<<<<<<< HEAD
    public function test_get_title_get_groups(): void
=======
    public function testGetTitleGetGroups(): void
>>>>>>> v9.1
    {
        $f = $this->getFactory();

        $groups = array(
            new I\Component\Item\Group("Subtitle 1", array(
                new I\Component\Item\Standard("title1"),
                new I\Component\Item\Standard("title2")
            )),
            new I\Component\Item\Group("Subtitle 2", array(
                new I\Component\Item\Standard("title3")
            ))
        );

        $c = $f->standard("title", $groups);

        $this->assertEquals("title", $c->getTitle());
        $this->assertEquals($groups, $c->getItemGroups());
    }

<<<<<<< HEAD
    public function test_with_actions(): void
=======
    public function testWithActions(): void
>>>>>>> v9.1
    {
        $f = $this->getFactory();

        $actions = new I\Component\Dropdown\Standard(array(
            new I\Component\Button\Shy("ILIAS", "https://www.ilias.de"),
            new I\Component\Button\Shy("GitHub", "https://www.github.com")
        ));

        $groups = array();

        $c = $f->standard("title", $groups)
            ->withActions($actions);

        $this->assertEquals($actions, $c->getActions());
    }

<<<<<<< HEAD
    public function test_render_base(): void
=======
    public function testRenderBase(): void
>>>>>>> v9.1
    {
        $f = $this->getFactory();
        $r = $this->getDefaultRenderer();

        $groups = array(
            new I\Component\Item\Group("Subtitle 1", array(
                new I\Component\Item\Standard("title1"),
                new I\Component\Item\Standard("title2")
            )),
            new I\Component\Item\Group("Subtitle 2", array(
                new I\Component\Item\Standard("title3")
            ))
        );

        $c = $f->standard("title", $groups);

        $html = $r->render($c);

        $expected = <<<EOT
<<<<<<< HEAD
<div class="panel il-panel-listing-std-container clearfix">
  <h2>title</h2>
  <div class="il-item-group">
    <h3>Subtitle 1</h3>
    <div class="il-item-group-items">
        <ul>
              <li class="il-std-item-container">
                <div class="il-item il-std-item ">
                  <div class="il-item-title">title1</div>
                </div>
              </li>
              <li class="il-std-item-container">
                <div class="il-item il-std-item ">
                  <div class="il-item-title">title2</div>
                </div>
              </li>
      </ul>
    </div>
  </div>
  <div class="il-item-group">
    <h3>Subtitle 2</h3>
    <div class="il-item-group-items">
      <ul>
            <li class="il-std-item-container">
                <div class="il-item il-std-item ">
                  <div class="il-item-title">title3</div>
                </div>
            </li>
      </ul>
    </div>
  </div>
=======
<div class="panel panel-flex il-panel-listing-std-container clearfix">
<div class="panel-heading ilHeader">
<div class="panel-title"><h2>title</h2></div><div class="panel-controls"></div></div>
<div class="il-item-group">
<h3>Subtitle 1</h3>
<div class="il-item-group-items">
    <ul>
          <li class="il-std-item-container">
            <div class="il-item il-std-item ">
              <h4 class="il-item-title">title1</h4>
            </div>
          </li>
          <li class="il-std-item-container">
            <div class="il-item il-std-item ">
              <h4 class="il-item-title">title2</h4>
            </div>
          </li>
  </ul>
</div>
</div>
<div class="il-item-group">
<h3>Subtitle 2</h3>
<div class="il-item-group-items">
  <ul>
        <li class="il-std-item-container">
            <div class="il-item il-std-item ">
              <h4 class="il-item-title">title3</h4>
            </div>
        </li>
  </ul>
</div>
</div>
>>>>>>> v9.1
</div>
EOT;
        $this->assertHTMLEquals(
            $this->brutallyTrimHTML($expected),
            $this->brutallyTrimHTML($html)
        );
    }

<<<<<<< HEAD
    public function test_render_with_actions(): void
=======
    public function testRenderWithActions(): void
>>>>>>> v9.1
    {
        $f = $this->getFactory();
        $r = $this->getDefaultRenderer();

        $groups = array();

        $actions = new I\Component\Dropdown\Standard(array(
            new I\Component\Button\Shy("ILIAS", "https://www.ilias.de"),
            new I\Component\Button\Shy("GitHub", "https://www.github.com")
        ));

        $c = $f->standard("title", $groups)
            ->withActions($actions);

        $html = $r->render($c);

        $expected = <<<EOT
<<<<<<< HEAD
<div class="panel il-panel-listing-std-container clearfix">
<h2>title</h2><div class="dropdown"><button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" id="id_3" aria-label="actions" aria-haspopup="true" aria-expanded="false" aria-controls="id_3_menu"> <span class="caret"></span></button>
=======
<div class="panel panel-flex il-panel-listing-std-container clearfix">
<div class="panel-heading ilHeader">
<div class="panel-title"><h2>title</h2></div><div class="panel-controls"><div class="dropdown"><button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" id="id_3" aria-label="actions" aria-haspopup="true" aria-expanded="false" aria-controls="id_3_menu"> <span class="caret"></span></button>
>>>>>>> v9.1
<ul id="id_3_menu" class="dropdown-menu">
	<li><button class="btn btn-link" data-action="https://www.ilias.de" id="id_1">ILIAS</button></li>
	<li><button class="btn btn-link" data-action="https://www.github.com" id="id_2">GitHub</button></li>
</ul>
</div>
</div>
</div>
</div>
EOT;
        $this->assertHTMLEquals($expected, $html);
    }
}
