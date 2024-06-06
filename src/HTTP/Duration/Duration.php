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

namespace ILIAS\HTTP\Duration;

use ILIAS\HTTP\Duration\Increment\IncrementStrategy;

/**
 * @author Thibeau Fuhrer <thibeau@sr.solutions>
 */
abstract class Duration
{
    /**
     * number used for converting seconds (s) into microseconds (us/µ) or vise-versa.
     */
    protected const S_TO_US = 1_000_000;

    /**
     * number used for converting miliseconds (ms) into microseconds (us/µ) or vise-versa.
     */
    protected const MS_TO_US = 1_000;

    protected ?IncrementStrategy $increment = null;
    protected int $duration_in_ms;

    public function __construct(int $duration_in_ms)
    {
        $this->duration_in_ms = $duration_in_ms;
    }

<<<<<<< HEAD
    public function withIncrement(IncrementStrategy $increment) : self
=======
    public function withIncrement(IncrementStrategy $increment): self
>>>>>>> v9.1
    {
        $clone = clone $this;
        $clone->increment = $increment;

        return $clone;
    }

<<<<<<< HEAD
    public function increment() : self
=======
    public function increment(): self
>>>>>>> v9.1
    {
        if (null === $this->increment) {
            return $this;
        }

        $clone = clone $this;
        $clone->duration_in_ms = $clone->increment->increment($clone->duration_in_ms);

        return $clone;
    }

<<<<<<< HEAD
    public function withDuration(int $duration_in_ms) : self
=======
    public function withDuration(int $duration_in_ms): self
>>>>>>> v9.1
    {
        $clone = clone $this;
        $clone->duration_in_ms = $duration_in_ms;

        return $clone;
    }

<<<<<<< HEAD
    public function getDuration() : int
=======
    public function getDuration(): int
>>>>>>> v9.1
    {
        return $this->duration_in_ms;
    }
}
