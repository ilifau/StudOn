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

<<<<<<< HEAD:Services/TermsOfService/interfaces/interface.ilTermsOfServiceControllerEnabled.php
=======
namespace ILIAS\UI\Component\Listing\Entity;

use ILIAS\UI\Factory as UIFactory;
use ILIAS\UI\Component\Entity\Entity;

>>>>>>> v9.1:src/UI/Component/Listing/Entity/RecordToEntity.php
/**
 * Listings will have to map records to Entities.
 */
interface RecordToEntity
{
<<<<<<< HEAD:Services/TermsOfService/interfaces/interface.ilTermsOfServiceControllerEnabled.php
    /**
     * The implemented class should be ilCtrlInterface enabled and execute or forward the given command
     */
    public function executeCommand(): void;
=======
    public function map(UIFactory $ui_factory, mixed $record): Entity;
>>>>>>> v9.1:src/UI/Component/Listing/Entity/RecordToEntity.php
}
