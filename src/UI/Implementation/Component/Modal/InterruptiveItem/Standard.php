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

<<<<<<< HEAD:src/UI/Implementation/Component/Modal/InterruptiveItem.php
namespace ILIAS\UI\Implementation\Component\Modal;
=======
namespace ILIAS\UI\Implementation\Component\Modal\InterruptiveItem;
>>>>>>> v9.1:src/UI/Implementation/Component/Modal/InterruptiveItem/Standard.php

use ILIAS\UI\Component\Image\Image;
use ILIAS\UI\Component\Modal\InterruptiveItem\Standard as StandardInterface;

class Standard extends InterruptiveItem implements StandardInterface
{
    protected string $title;
    protected string $description;
    protected ?Image $icon;

    public function __construct(
        string $id,
        string $title,
        Image $icon = null,
        string $description = ''
    ) {
        parent::__construct($id);

        $this->title = $title;
        $this->icon = $icon;
        $this->description = $description;
    }

<<<<<<< HEAD:src/UI/Implementation/Component/Modal/InterruptiveItem.php
    /**
     * @inheritdoc
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
=======
>>>>>>> v9.1:src/UI/Implementation/Component/Modal/InterruptiveItem/Standard.php
    public function getTitle(): string
    {
        return $this->title;
    }

<<<<<<< HEAD:src/UI/Implementation/Component/Modal/InterruptiveItem.php
    /**
     * @inheritdoc
     */
=======
>>>>>>> v9.1:src/UI/Implementation/Component/Modal/InterruptiveItem/Standard.php
    public function getDescription(): string
    {
        return $this->description;
    }

<<<<<<< HEAD:src/UI/Implementation/Component/Modal/InterruptiveItem.php
    /**
     * @inheritdoc
     */
=======
>>>>>>> v9.1:src/UI/Implementation/Component/Modal/InterruptiveItem/Standard.php
    public function getIcon(): ?Image
    {
        return $this->icon;
    }
}
