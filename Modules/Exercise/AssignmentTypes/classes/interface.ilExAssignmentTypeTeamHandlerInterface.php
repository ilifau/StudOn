<?php

/* Copyright (c) 1998-2018 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * fau: exAssHook - interface for handling team changes by an assignment type
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 */
interface ilExAssignmentTypeTeamHandlerInterface
{

    /**
     * Handle the adding of users to a team
     * This function is called after team creation
     *
     * @param ilExAssignmentTeam    $team
     * @return mixed
     */
    public function handleTeamCreated(ilExAssignmentTeam $team);


    /**
     * Handle the adding of users to a team
     * This function is called after adding of users
     *
     * @param array                 $added_users
     * @return mixed
     */
    public function handleTeamAddedUsers(ilExAssignmentTeam $team, $added_users = []);


    /**
     * Handle the removing of users from a team
     * This function is called after removing of users
     *
     * @param ilExAssignmentTeam    $team
     * @param array                 $removed_users
     * @return mixed
     */
    public function handleTeamRemovedUsers(ilExAssignmentTeam $team, $removed_users = []);


    /**
     * Get the removed users treated by the last handleTeamRemovedUsers() call that have a submission and should form a single team
     * The result depends on how the assignment type treats the distribution of submissions in a team
     *
     * @return int[]
     */
    public function getRemovedUsersWithSubmission();

    /**
     * Get the message that will be displayed when users are removed
     */
    public function getRemoveUsersMessage(): string;
}
