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

<<<<<<< HEAD:Services/TermsOfService/interfaces/interface.ilTermsOfServiceSignableDocument.php
=======
namespace ILIAS\Cache\Nodes;

>>>>>>> v9.1:src/Cache/Nodes/NodeRepository.php
/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
interface NodeRepository
{
<<<<<<< HEAD:Services/TermsOfService/interfaces/interface.ilTermsOfServiceSignableDocument.php
    public function content(): string;

    public function title(): string;

    public function id(): int;
=======
    public function store(Node $node): Node;

    public function create(
        string $host,
        int $port,
        int $weight
    ): Node;
>>>>>>> v9.1:src/Cache/Nodes/NodeRepository.php

    /**
     * @return Node[]
     */
<<<<<<< HEAD:Services/TermsOfService/interfaces/interface.ilTermsOfServiceSignableDocument.php
    public function criteria(): array;
=======
    public function getNodes(): array;

    public function deleteAll(): void;
>>>>>>> v9.1:src/Cache/Nodes/NodeRepository.php
}
