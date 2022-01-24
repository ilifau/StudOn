<?php

/* Copyright (c) 1998-2018 ILIAS open source, Extended GPL, see docs/LICENSE */

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
    public function getSubmission()
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
    public function getExercise()
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
    public function setAssignment(ilExAssignment $assignment)
    {
        $this->assignment = $assignment;
    }
    // fau.

    // fau: exAssHook - add tab manipulation
    /**
     * Manipulate the assignment editor tabs
     * @param ilTabsGUI $tabs
     */
    public function handleEditorTabs(ilTabsGUI $tabs)
    {
        // add or remove tabs
    }
    // fau.
}
