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

<<<<<<< HEAD
<<<<<<<< HEAD:components/ILIAS/WOPI/classes/Handler/WOPIUnknownStakeholder.php
namespace ILIAS\components\WOPI\Handler;
=======
namespace ILIAS\WOPI\Handler;
>>>>>>> v10.3

use ILIAS\ResourceStorage\Stakeholder\AbstractResourceStakeholder;

class WOPIUnknownStakeholder extends AbstractResourceStakeholder
{
    public function getId(): string
    {
        return 'wopi_unknown';
<<<<<<< HEAD
========
namespace ILIAS\Test\ExportImport;

use ILIAS\ResourceStorage\Stakeholder\AbstractResourceStakeholder;

class ResultsExportStakeholder extends AbstractResourceStakeholder
{
    public function getId(): string
    {
        return 'test_results_export';
>>>>>>>> v10.3:components/ILIAS/Test/src/ExportImport/ResultsExportStakeholder.php
=======
>>>>>>> v10.3
    }

    public function getOwnerOfNewResources(): int
    {
        return $this->default_owner;
    }

}
