<?php
<<<<<<< HEAD

declare(strict_types=1);
=======
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

declare(strict_types=1);

namespace ILIAS\Refinery;

use Closure;
use ArrayAccess;
use Countable;

class KeyValueAccess implements ArrayAccess, Countable
{
    private array $raw_values;
    private Transformation $trafo;

    public function __construct(array $raw_values, Transformation $trafo)
    {
        $this->trafo = $trafo;
        $this->raw_values = $raw_values;
    }

<<<<<<< HEAD
    /**
     * @inheritDoc
     */
    public function offsetExists($offset): bool
=======
    public function offsetExists(mixed $offset): bool
>>>>>>> v9.1
    {
        return isset($this->raw_values[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        if (!$this->offsetExists($offset)) {
            return null;
        }

        return is_array($this->raw_values[$offset])
            ? array_map($this->getApplicator(), $this->raw_values[$offset])
            : $this->getApplicator()($this->raw_values[$offset]);
    }

    private function getApplicator(): Closure
    {
        return function ($value) {
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    $value[$k] = $this->getApplicator()($v);
                }
                return $value;
            }
            return $this->trafo->transform($value);
        };
    }

<<<<<<< HEAD
    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value): void
=======
    public function offsetSet(mixed $offset, mixed $value): void
>>>>>>> v9.1
    {
        $this->raw_values[$offset] = $value;
    }

<<<<<<< HEAD
    /**
     * @inheritDoc
     */
    public function offsetUnset($offset): void
=======
    public function offsetUnset(mixed $offset): void
>>>>>>> v9.1
    {
        if ($this->offsetExists($offset)) {
            unset($this->raw_values[$offset]);
        }
    }

<<<<<<< HEAD
    /**
     * @inheritDoc
     */
=======
>>>>>>> v9.1
    public function count(): int
    {
        return count($this->raw_values);
    }
}
