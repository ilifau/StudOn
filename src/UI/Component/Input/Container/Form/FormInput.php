<?php
<<<<<<< HEAD
=======
<<<<<<<< HEAD:src/UI/Component/Input/Field/FormInput.php

declare(strict_types=1);
========
>>>>>>>> v9.1:src/UI/Component/Input/Container/Form/FormInput.php
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
=======
<<<<<<<< HEAD:src/UI/Component/Input/Field/FormInput.php

namespace ILIAS\UI\Component\Input\Field;

========
>>>>>>> v9.1
declare(strict_types=1);

namespace ILIAS\UI\Component\Input\Container\Form;

use ILIAS\UI\Component\Input\Input;
use ILIAS\UI\Component\JavaScriptBindable;
use ILIAS\UI\Component\OnUpdateable;
use ILIAS\Refinery\Constraint;
use Closure;

<<<<<<< HEAD
/**
 * This describes inputs that can be used in forms.
 */
interface FormInput extends Input, JavaScriptBindable, OnUpdateable
{
=======
>>>>>>>> v9.1:src/UI/Component/Input/Container/Form/FormInput.php
/**
 * This is a legacy support of Component\Input\Field\Input 
 * that has been moved to Component\Input\Container\Form\FormInput.
 * 
 * Please always hint to \ILIAS\UI\Component\Input\Container\Form\FormInput
 * 
 * @deprecated removed in 9
 */
interface FormInput
{
<<<<<<<< HEAD:src/UI/Component/Input/Field/FormInput.php
========
>>>>>>> v9.1
    /**
     * Get the label of the input.
     */
    public function getLabel(): string;

    /**
     * Get an input like this, but with a replaced label.
     *
     * @return static
     */
    public function withLabel(string $label);

    /**
     * Get the byline of the input.
     */
    public function getByline(): ?string;

    /**
     * Get an input like this, but with an additional/replaced label.
     *
     * @return static
     */
    public function withByline(string $byline);

    /**
     * Is this field required?
     */
    public function isRequired(): bool;

    /**
     * Get an input like this, but set the field to be required (or not).
     * With the optional $required_constraint, you can REPLACE the default
     * constraint that is checked if $is_required is true
     * (see getConstraintForRequirement() on Input/Field implementations).
     * A custom constraint SHOULD be explained in the byline of the input.
     */
<<<<<<< HEAD
    public function withRequired(bool $is_required, ?Constraint $requirement_constraint = null);
=======
    public function withRequired(bool $is_required, ?Constraint $requirement_constraint = null): self;
>>>>>>> v9.1

    /**
     * Is this input disabled?
     */
    public function isDisabled(): bool;

    /**
     * Get an input like this, but set it to a disabled state.
     *
     * @return static
     */
    public function withDisabled(bool $is_disabled);

    /**
     * Get update code
     *
     * This method has to return JS code that calls
     * il.UI.filter.onFieldUpdate(event, '$id', string_value);
     * - initially "onload" and
     * - on every input change.
     * It must pass a readable string representation of its value in parameter 'string_value'.
     */
    public function getUpdateOnLoadCode(): Closure;
<<<<<<< HEAD
=======
>>>>>>>> v9.1:src/UI/Component/Input/Container/Form/FormInput.php
>>>>>>> v9.1
}
