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

<<<<<<<< HEAD:src/ResourceStorage/Consumer/StreamAccess/UnlockKey.php
namespace ILIAS\ResourceStorage\Consumer\StreamAccess;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 * @internal
 */
class UnlockKey
{
    public function __serialize(): array
    {
        return [];
    }

    public function __unserialize(array $data): void
========
namespace ILIAS\MetaData\Elements\RessourceID;

class NullRessourceID implements RessourceIDInterface
{
    public function type(): string
>>>>>>>> v9.1:Services/MetaData/classes/Elements/RessourceID/NullRessourceID.php
    {
    }

    public function objID(): int
    {
        return 0;
    }

    public function subID(): int
    {
        return 0;
    }
}
