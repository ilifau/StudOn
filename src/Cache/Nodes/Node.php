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

namespace ILIAS\Cache\Nodes;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class Node
{
    public function __construct(
        private string $host,
        private int $port,
        private ?int $weight = null
    ) {
    }

<<<<<<< HEAD:Services/Preview/classes/Setup/class.ilPreviewSetupConfig.php
    protected function toLinuxConvention(?string $p): ?string
=======
    public function getHost(): string
>>>>>>> v9.1:src/Cache/Nodes/Node.php
    {
        return $this->host;
    }

<<<<<<< HEAD:Services/Preview/classes/Setup/class.ilPreviewSetupConfig.php
    public function getPathToGhostscript(): ?string
=======
    public function getPort(): int
>>>>>>> v9.1:src/Cache/Nodes/Node.php
    {
        return $this->port;
    }

    public function getWeight(): ?int
    {
        return $this->weight;
    }
}
