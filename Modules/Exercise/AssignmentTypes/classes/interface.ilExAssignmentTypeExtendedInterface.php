<?php
// fau: exAssTest - extended interface for assignment types.
// fau: exAssHook - extended interface for assignment types.


/* Copyright (c) 1998-2018 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Interface for assignment types
 *
 */
interface ilExAssignmentTypeExtendedInterface extends ilExAssignmentTypeInterface
{
    /**
     * Check if manual grading should be supported
     * @param ilExAssignment $a_ass
     * @return bool
     */
    public function isManualGradingSupported($a_ass): bool;
}
