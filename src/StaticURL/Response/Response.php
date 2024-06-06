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

<<<<<<< HEAD:Services/TermsOfService/interfaces/interface.ilTermsOfServiceJsonSerialization.php
declare(strict_types=1);
=======
namespace ILIAS\StaticURL\Response;
>>>>>>> v9.1:src/StaticURL/Response/Response.php

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
interface Response
{
<<<<<<< HEAD:Services/TermsOfService/interfaces/interface.ilTermsOfServiceJsonSerialization.php
    public function toJson(): string;
=======
    public function getURIPath(): ?string;

    public function targetCanBeReached(): bool;

>>>>>>> v9.1:src/StaticURL/Response/Response.php
}
