<?php

/* Copyright (c) 1998-2018 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Modules/Exercise/AssignmentTypes/classes/interface.ilExAssignmentTypeInterface.php");

/**
 * fau: exAssHook - dummy type for inactive plugins.
 */
class ilExAssTypeInactive implements ilExAssignmentTypeInterface
{
    /**
     * @var ilLanguage
     */
    protected $lng;

    /**
     * Constructor
     *
     * @param ilLanguage|null $a_lng
     */
    public function __construct(ilLanguage $a_lng = null)
    {
        global $DIC;

        $this->lng = ($a_lng)
            ? $a_lng
            : $DIC->language();
    }

    /**
     * @inheritdoc
     */
    public function isActive()
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function usesTeams()
    {
        return false;
    }

    // fau: exAssHook - new function hasFiles()
    public function hasFiles()
    {
        return false;
    }
    // fau.

    /**
     * @inheritdoc
     */
    public function usesFileUpload()
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getTitle()
    {
        $lng = $this->lng;

        return $lng->txt("exc_type_inactive");
    }

    /**
     * @inheritdoc
     */
    public function getSubmissionType()
    {
        return ilExSubmission::TYPE_INACTIVE;
    }

    /**
     * @inheritdoc
     */
    public function isSubmissionAssignedToTeam()
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function cloneSpecificProperties(ilExAssignment $source, ilExAssignment $target)
    {
    }

    // fau: exAssHook - new function isManualGradingSupported()
    /**
     * @inheritdoc
     */
    public function isManualGradingSupported($a_ass) : bool
    {
        return false;
    }
    //fau.
}
