<?php

/* Copyright (c) 1998-2018 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * fau: exAssHook -  Extended interface for assignment type.
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 */
interface ilExAssignmentTypeExtendedInterface extends ilExAssignmentTypeInterface
{
    /**
     * Get the handler for team changes
     * is_management is set if the handler is used for team management by an admin
     *
     * @param ilExAssignment $assignment
     * @param bool $is_management
     * @return ilExAssignmentTypeTeamHandlerInterface
     */
    public function getTeamHandler(ilExAssignment $assignment, $is_management = false): ilExAssignmentTypeTeamHandlerInterface;
}
