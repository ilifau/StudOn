<?php

/* Copyright (c) 1998-2018 ILIAS open source, Extended GPL, see docs/LICENSE */

use ILIAS\Exercise\Assignment\PropertyAndActionBuilderUI;

/**
 * fau: exAssHook - Inactive type gui implementations
 */
class ilExAssTypeInactiveGUI implements ilExAssignmentTypeGUIInterface
{
    use ilExAssignmentTypeGUIBase;

    public function addEditFormCustomProperties(ilPropertyFormGUI $form): void
    {
    }

    public function importFormToAssignment(ilExAssignment $ass, ilPropertyFormGUI $form): void
    {
    }

    public function getFormValuesArray(ilExAssignment $ass): array
    {
        return [];
    }

    public function getOverviewContent(ilInfoScreenGUI $a_info, ilExSubmission $a_submission): void
    {
        global $DIC;
        $a_info->addProperty($DIC->language()->txt("exc_type_inactive"), $DIC->language()->txt("exc_type_inactive_info"));
    }

    public function buildSubmissionPropertiesAndActions(PropertyAndActionBuilderUI $builder): void
    {
        global $DIC;
        $builder->addProperty(
            $builder::SEC_SUBMISSION,
            $DIC->language()->txt("exc_type_inactive"),
            $DIC->language()->txt("exc_type_inactive_info")
        );
    }
}
