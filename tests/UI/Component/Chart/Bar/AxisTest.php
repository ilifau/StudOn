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

use ILIAS\UI\Component\Chart\Bar\XAxis;
use ILIAS\UI\Component\Chart\Bar\YAxis;

/**
 * Test on Bar Configuration implementation.
 */
class AxisTest extends ILIAS_UI_TestBase
{
<<<<<<< HEAD
    public function test_x_abbreviation(): void
=======
    public function testXAbbreviation(): void
>>>>>>> v9.1
    {
        $x_axis = new XAxis();

        $this->assertEquals("x", $x_axis->getAbbreviation());
    }

<<<<<<< HEAD
    public function test_y_abbreviation(): void
=======
    public function testYAbbreviation(): void
>>>>>>> v9.1
    {
        $y_axis = new YAxis();

        $this->assertEquals("y", $y_axis->getAbbreviation());
    }

<<<<<<< HEAD
    public function test_type(): void
=======
    public function testType(): void
>>>>>>> v9.1
    {
        $x_axis = new XAxis();

        $this->assertEquals("linear", $x_axis->getType());

        $y_axis = new YAxis();

        $this->assertEquals("linear", $y_axis->getType());
    }

<<<<<<< HEAD
    public function test_with_displayed(): void
=======
    public function testWithDisplayed(): void
>>>>>>> v9.1
    {
        $x_axis = new XAxis();
        $x_axis1 = $x_axis->withDisplayed(false);

        $this->assertEquals(true, $x_axis->isDisplayed());
        $this->assertEquals(false, $x_axis1->isDisplayed());
    }

<<<<<<< HEAD
    public function test_with_step_size(): void
=======
    public function testWithStepSize(): void
>>>>>>> v9.1
    {
        $x_axis = new XAxis();
        $x_axis1 = $x_axis->withStepSize(0.5);

        $this->assertEquals(1.0, $x_axis->getStepSize());
        $this->assertEquals(0.5, $x_axis1->getStepSize());
    }

<<<<<<< HEAD
    public function test_with_begin_at_zero(): void
=======
    public function testWithBeginAtZero(): void
>>>>>>> v9.1
    {
        $x_axis = new XAxis();
        $x_axis1 = $x_axis->withBeginAtZero(false);

        $this->assertEquals(true, $x_axis->isBeginAtZero());
        $this->assertEquals(false, $x_axis1->isBeginAtZero());
    }

<<<<<<< HEAD
    public function test_with_min(): void
=======
    public function testWithMin(): void
>>>>>>> v9.1
    {
        $x_axis = new XAxis();
        $x_axis1 = $x_axis->withMinValue(-2);

        $this->assertEquals(null, $x_axis->getMinValue());
        $this->assertEquals(-2, $x_axis1->getMinValue());
    }

<<<<<<< HEAD
    public function test_with_max(): void
=======
    public function testWithMax(): void
>>>>>>> v9.1
    {
        $x_axis = new XAxis();
        $x_axis1 = $x_axis->withMaxValue(10);

        $this->assertEquals(null, $x_axis->getMaxValue());
        $this->assertEquals(10, $x_axis1->getMaxValue());
    }

<<<<<<< HEAD
    public function test_x_with_position(): void
=======
    public function testXWithPosition(): void
>>>>>>> v9.1
    {
        $x_axis = new XAxis();
        $x_axis1 = $x_axis->withPosition("top");

        $this->assertEquals("bottom", $x_axis->getPosition());
        $this->assertEquals("top", $x_axis1->getPosition());
    }

<<<<<<< HEAD
    public function test_x_with_invalid_position(): void
=======
    public function testXWithInvalidPosition(): void
>>>>>>> v9.1
    {
        $x_axis = new XAxis();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Position must be 'bottom' or 'top'.");

        $x_axis = $x_axis->withPosition("left");
    }

<<<<<<< HEAD
    public function test_y_with_position(): void
=======
    public function testYWithPosition(): void
>>>>>>> v9.1
    {
        $y_axis = new YAxis();
        $y_axis1 = $y_axis->withPosition("right");

        $this->assertEquals("left", $y_axis->getPosition());
        $this->assertEquals("right", $y_axis1->getPosition());
    }

<<<<<<< HEAD
    public function test_y_with_invalid_position(): void
=======
    public function testYWithInvalidPosition(): void
>>>>>>> v9.1
    {
        $y_axis = new YAxis();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Position must be 'left' or 'right'.");

        $y_axis = $y_axis->withPosition("bottom");
    }
}
