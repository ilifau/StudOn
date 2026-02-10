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
 */

declare(strict_types=1);

namespace ILIAS\UI\Implementation\Component\Progress\State;

use ILIAS\UI\Component\Progress\State;

/**
 * @author Thibeau Fuhrer <thibeau@sr.solutions>
 */
class Factory implements State\Factory
{
    public function __construct(
        protected State\Bar\Factory $bar_factory,
    ) {
    }

    public function bar(): State\Bar\Factory
    {
        return $this->bar_factory;
    }
}
