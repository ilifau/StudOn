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

<<<<<<< HEAD:Services/TermsOfService/exceptions/class.ilTermsOfServiceNoSignableDocumentFoundException.php
declare(strict_types=1);
=======
namespace ILIAS\ResourceStorage\Revision;

use ILIAS\ResourceStorage\Consumer\StreamAccess\Token;
>>>>>>> v9.1:src/ResourceStorage/Revision/RevisionStatus.php

/**
 * @author Fabian Schmid <fabian@sr.solutions.ch>
 */
enum RevisionStatus: int
{
    case PUBLISHED = 10;
    case DRAFT = 20;
}
