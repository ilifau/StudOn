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

<<<<<<< HEAD:Services/TermsOfService/interfaces/interface.ilTermsOfServiceEvaluableCriterion.php
declare(strict_types=1);

/**
 * Interface ilTermsOfServiceEvaluableCriterion
 * @author Michael Jansen <mjansen@databay.de>
 */
interface ilTermsOfServiceEvaluableCriterion
{
    public function getCriterionValue(): ilTermsOfServiceCriterionConfig;

    public function getCriterionId(): string;
=======
namespace ILIAS\GlobalScreen\Scope\MetaBar\Factory;

use ILIAS\Data\LanguageTag;

interface hasLanguageForTargetedResource
{
    public function withLanguageForTargetedResource(LanguageTag $content_language);

    public function getLanguageForTargetedResource(): ?LanguageTag;
>>>>>>> v9.1:src/GlobalScreen/Scope/MetaBar/Factory/hasLanguageForTargetedResource.php
}
