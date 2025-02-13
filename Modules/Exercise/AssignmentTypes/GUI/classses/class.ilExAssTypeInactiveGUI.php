<?php

/* Copyright (c) 1998-2018 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Modules/Exercise/AssignmentTypes/GUI/classes/interface.ilExAssignmentTypeGUIInterface.php");
include_once("./Modules/Exercise/AssignmentTypes/GUI/traits/trait.ilExAssignmentTypeGUIBase.php");

/**
 * fau: exAssHook - Inactive type gui implementations
 */
class ilExAssTypeInactiveGUI implements ilExAssignmentTypeGUIInterface
{
    use ilExAssignmentTypeGUIBase;

    /**
     * @inheritdoc
     */
    public function addEditFormCustomProperties(ilPropertyFormGUI $form): void
    {
    }

    /**
     * @inheritdoc
     */
    public function importFormToAssignment(ilExAssignment $ass, ilPropertyFormGUI $form): void
    {
    }

    /**
     * @inheritdoc
     */
    public function getFormValuesArray(ilExAssignment $ass): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getOverviewContent(ilInfoScreenGUI $a_info, ilExSubmission $a_submission): void
    {
        global $DIC;
        $a_info->addProperty($DIC->language()->txt("exc_type_inactive"), $DIC->language()->txt("exc_type_inactive_info"));
    }
}