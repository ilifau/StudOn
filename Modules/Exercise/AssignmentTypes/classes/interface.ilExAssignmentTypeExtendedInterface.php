<?php

/* Copyright (c) 1998-2018 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * fau: exAssHook - extended Interface for extended assignment type.
 *
 * Currently this interface contains team management related functions
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 */
interface ilExAssignmentTypeExtendedInterface extends ilExAssignmentTypeInterface
{

    /**
     * Handle a membership change in a team
     *
     * @param ilExAssignment     $ass
     * @param ilExAssignmentTeam $team
     * @param int[]              $added_users
     * @param int[]              $removed_users
     * @return mixed
     */
    public function handleTeamChange(ilExAssignment $ass, ilExAssignmentTeam $team, $added_users = [], $removed_users = []);
}
