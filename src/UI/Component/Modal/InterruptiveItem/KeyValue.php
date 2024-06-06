<?php
<<<<<<<< HEAD:src/GlobalScreen/Scope/Toast/Provider/ToastProvider.php
========

declare(strict_types=1);

>>>>>>>> v9.1:src/UI/Component/Modal/InterruptiveItem/KeyValue.php
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

<<<<<<<< HEAD:src/GlobalScreen/Scope/Toast/Provider/ToastProvider.php
declare(strict_types=1);

namespace ILIAS\GlobalScreen\Scope\Toast\Provider;

use ILIAS\GlobalScreen\Provider\Provider;
use ILIAS\GlobalScreen\Scope\Toast\Factory\isStandardItem;
use ILIAS\DI\Container;

interface ToastProvider extends Provider
========
namespace ILIAS\UI\Component\Modal\InterruptiveItem;

/**
 * Interface InterruptiveItem
 *
 * Represents a key-value item to be displayed inside an interruptive modal
 */
interface KeyValue extends InterruptiveItem
>>>>>>>> v9.1:src/UI/Component/Modal/InterruptiveItem/KeyValue.php
{
    public function __construct(Container $dic);

    /**
<<<<<<<< HEAD:src/GlobalScreen/Scope/Toast/Provider/ToastProvider.php
     * @return isStandardItem[]
     */
    public function getToasts(): array;
========
     * Get the key of the pair
     */
    public function getKey(): string;

    /**
     * Get the value of the pair
     */
    public function getValue(): string;
>>>>>>>> v9.1:src/UI/Component/Modal/InterruptiveItem/KeyValue.php
}
