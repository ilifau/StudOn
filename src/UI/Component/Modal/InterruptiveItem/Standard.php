<?php

declare(strict_types=1);

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

<<<<<<< HEAD:src/UI/Component/Modal/InterruptiveItem.php
namespace ILIAS\UI\Component\Modal;
=======
namespace ILIAS\UI\Component\Modal\InterruptiveItem;
>>>>>>> v9.1:src/UI/Component/Modal/InterruptiveItem/Standard.php

use ILIAS\UI\Component\Image\Image;

/**
 * Interface InterruptiveItem
 *
 * Represents an object to be displayed inside an interruptive modal
 */
interface Standard extends InterruptiveItem
{
    /**
     * Get the title of the object
     */
<<<<<<< HEAD:src/UI/Component/Modal/InterruptiveItem.php
    public function getId(): string;
=======
    public function getTitle(): string;
>>>>>>> v9.1:src/UI/Component/Modal/InterruptiveItem/Standard.php

    /**
     * Get the description of the object
     */
<<<<<<< HEAD:src/UI/Component/Modal/InterruptiveItem.php
    public function getTitle(): string;
=======
    public function getDescription(): string;
>>>>>>> v9.1:src/UI/Component/Modal/InterruptiveItem/Standard.php

    /**
     * Get the icon of the object
     */
<<<<<<< HEAD:src/UI/Component/Modal/InterruptiveItem.php
    public function getDescription(): string;

    /**
     * Get the icon of the item
     */
=======
>>>>>>> v9.1:src/UI/Component/Modal/InterruptiveItem/Standard.php
    public function getIcon(): ?Image;
}
