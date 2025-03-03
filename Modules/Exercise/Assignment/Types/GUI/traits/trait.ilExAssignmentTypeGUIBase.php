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
 *
 *********************************************************************/

/**
 * Base trait for ilExAssignmetnTypeGUI implementations
 *
 * @author killing@leifos.de
 * @ingroup ModulesExercise
 */
trait ilExAssignmentTypeGUIBase
{
    // fau: exAssHook - add assignment to trait
    /** @var ilExAssignment */
    protected $assignment;
    // fau.

    /**
     * @var ilExSubmission
     */
    protected $submission;

    /**
     * @var ilObjExercise
     */
    protected $exercise;

    /**
     * Set submission
     *
     * @param ilExSubmission $a_val submission
     */
    public function setSubmission(ilExSubmission $a_val)
    {
        $this->submission = $a_val;
    }

    /**
     * Get submission
     *
     * @return ilExSubmission submission
     */
    public function getSubmission(): ilExSubmission
    {
        return $this->submission;
    }

    /**
     * Set exercise
     *
     * @param ilObjExercise $a_val exercise
     */
    public function setExercise(ilObjExercise $a_val)
    {
        $this->exercise = $a_val;
    }

    /**
     * Get exercise
     *
     * @return ilObjExercise exercise
     */
    public function getExercise(): ilObjExercise
    {
        return $this->exercise;
    }
    // fau: exAssHook - setter and getter for assignment
    /**
     * @return ilExAssignment
     */
    public function getAssignment() : ilExAssignment
    {
        return $this->assignment;
    }

    /**
     * @param ilExAssignment $assignment
     */
    public function setAssignment(ilExAssignment $assignment): void
    {
        $this->assignment = $assignment;
    }
    // fau.

    // fau: exAssHook - add tab manipulation
    /**
     * Manipulate the assignment editor tabs
     * @param ilTabsGUI $tabs
     */
    public function handleEditorTabs(ilTabsGUI $tabs): void
    {
        // add or remove tabs
    }
    // fau.    
}
