<?php

/**
 * fau: exAssHook - extended Interface for extended assignment type.
 *
 * Currently this interface contains GUI functions for different scenarios
 * (editing screen, assignment overview, ...)
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 */
interface ilExAssignmentTypeExtendedGUIInterface extends ilExAssignmentTypeGUIInterface
{

    /**
     * Add additional overview content of instructions to info screen object
     * @param ilInfoScreenGUI $a_info
     * @param ilExAssignment $a_assignment
     */
    public function getOverviewAdditionalInstructions(ilInfoScreenGUI $a_info, ilExAssignment $a_assignment);

    /**
     * Indicate that the standard submission section should be replaced by an own one
     * @return bool
     */
    public function hasOwnOverviewSubmission() : bool;

    /**
     * Use a specific submission section on the info screen object (instead of standard)
     * @param ilInfoScreenGUI $a_info
     * @param ilExSubmission $a_submission
     */
    public function getOverviewSubmission(ilInfoScreenGUI $a_info, ilExSubmission $a_submission);

    /**
     * Indicate that the standard submission screen should not be shown
     * @return bool
     */
    public function hasOwnSubmissionScreen() : bool;

    /**
     * Get the link target to view the submission screen
     * @return string
     */
    public function getSubmissionScreenLinkTarget() : string;


    /**
     * Get additional tutor feedback for the submission
     * @param ilInfoScreenGUI $a_info
     * @param ilExSubmission $a_submission
     */
    public function getOverviewAdditionalFeedback(ilInfoScreenGUI $a_info, ilExSubmission $a_submission);


    /**
     * Indicate that the standard general feedback section should be replaced by an own one
     * @return bool
     */
    public function hasOwnOverviewGeneralFeedback() : bool;


    /**
     * Get a specific general feedback section on the info screen object (instead of standard)
     * @param ilInfoScreenGUI $a_info
     * @param ilExAssignment $_assignment
     */
    public function getOverviewGeneralFeedback(ilInfoScreenGUI $a_info, ilExAssignment $_assignment);

    /**
     * Modify the actions available in a submission table under submissions and grades
     * @param ilExSubmission             $a_submission
     * @param ilAdvancedSelectionListGUI $a_actions
     */
    public function modifySubmissionTableActions(ilExSubmission $a_submission, ilAdvancedSelectionListGUI $a_actions);
}
