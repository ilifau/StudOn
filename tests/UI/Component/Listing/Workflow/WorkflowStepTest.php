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

use ILIAS\UI\Component\Listing\Workflow;

class WorkflowStepTest extends ILIAS_UI_TestBase
{
    protected Workflow\Factory $f;

    protected function buildFactory(): Workflow\Factory
    {
        return new ILIAS\UI\Implementation\Component\Listing\Workflow\Factory();
    }

    public function setUp(): void
    {
        $this->f = $this->buildFactory();
    }

<<<<<<< HEAD
    public function test_implements_factory_interface(): void
=======
    public function testImplementsFactoryInterface(): void
>>>>>>> v9.1
    {
        $step = $this->f->step('');
        $this->assertInstanceOf(Workflow\Step::class, $step);
    }

<<<<<<< HEAD
    public function test_constructor_params(): void
=======
    public function testConstructorParams(): void
>>>>>>> v9.1
    {
        $label = 'label';
        $description = 'description';
        $step = $this->f->step($label, $description);
        $this->assertEquals($label, $step->getLabel());
        $this->assertEquals($description, $step->getDescription());
        $this->assertEquals(Workflow\Step::NOT_STARTED, $step->getStatus());
    }

<<<<<<< HEAD
    public function test_withStatus(): void
=======
    public function testWithStatus(): void
>>>>>>> v9.1
    {
        $status = Workflow\Step::SUCCESSFULLY;
        $step = $this->f->step('')->withStatus($status);
        $this->assertEquals($status, $step->getStatus());
    }

<<<<<<< HEAD
    public function test_withStatus_wrong_args(): void
=======
    public function testWithStatusWrongArgs(): void
>>>>>>> v9.1
    {
        $status = 100;
        $raised = false;
        try {
            $this->f->step('')->withStatus($status);
        } catch (InvalidArgumentException $e) {
            $raised = true;
        }
        $this->assertTrue($raised);
    }
}
