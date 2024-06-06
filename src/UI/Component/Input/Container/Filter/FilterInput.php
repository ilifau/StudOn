<?php

<<<<<<< HEAD
=======
declare(strict_types=1);

>>>>>>> v9.1
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

<<<<<<< HEAD
declare(strict_types=1);

namespace ILIAS\UI\Component\Input\Container\Filter;

use ILIAS\UI\Component\Input\Container\Form\FormInput;
use ILIAS\UI\Component\Input\Field\FilterInput as LegacyFilterInput;

/**
 * This interface must be implemented by all Inputs that support
 * Filter Containers.
 *
 * These inputs need to implement an additional rendering in the
 * FilterContextRenderer and provide the 'getUpdateOnLoadCode' method that allows
 * the Filter to show the current selected values within the Filter component.
 *
 * @author killing@leifos.de
 */
interface FilterInput extends FormInput, LegacyFilterInput
{
=======
<<<<<<<< HEAD:src/UI/Component/Input/Field/FilterInput.php
namespace ILIAS\UI\Component\Input\Field;
========
namespace ILIAS\UI\Component\Input\Container\Filter;

use ILIAS\UI\Component\Input\Container\Form\FormInput;
>>>>>>>> v9.1:src/UI/Component/Input/Container/Filter/FilterInput.php

/**
 * This is a legacy support of Component\Input\Field\FilterInput 
 * that has been moved to Component\Input\Container\Filter\FilterInput.
 * 
 * Please always hint to \ILIAS\UI\Component\Input\Container\Filter\FilterInput
 * 
 * @deprecated removed in 9
 */
interface FilterInput
{
<<<<<<<< HEAD:src/UI/Component/Input/Field/FilterInput.php
========
>>>>>>> v9.1
    /**
     * Is this input complex and must be rendered in a Popover when using it in a Filter?
     */
    public function isComplex(): bool;
<<<<<<< HEAD
=======
>>>>>>>> v9.1:src/UI/Component/Input/Container/Filter/FilterInput.php
>>>>>>> v9.1
}
