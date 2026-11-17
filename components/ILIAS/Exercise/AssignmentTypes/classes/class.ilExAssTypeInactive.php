<?php

/* Copyright (c) 1998-2018 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * fau: exAssHook - dummy type for inactive plugins.
 */
class ilExAssTypeInactive implements ilExAssignmentTypeInterface
{
    protected ilLanguage $lng;

    public function __construct(?ilLanguage $a_lng = null)
    {
        global $DIC;

        $this->lng = ($a_lng)
            ? $a_lng
            : $DIC->language();
    }

    public function isActive(): bool
    {
        return false;
    }

    public function usesTeams(): bool
    {
        return false;
    }

    public function hasFiles(): bool
    {
        return false;
    }

    public function usesFileUpload(): bool
    {
        return false;
    }

    public function getTitle(): string
    {
        return $this->lng->txt("exc_type_inactive");
    }

    public function getSubmissionType(): string
    {
        return ilExSubmission::TYPE_INACTIVE;
    }

    public function isSubmissionAssignedToTeam(): bool
    {
        return false;
    }

    public function cloneSpecificProperties(ilExAssignment $source, ilExAssignment $target): void
    {
    }

    public function supportsWebDirAccess(): bool
    {
        return false;
    }

    public function getStringIdentifier(): string
    {
        return '';
    }
}
