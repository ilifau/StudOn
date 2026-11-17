<?php

/* Copyright (c) 1998-2018 ILIAS open source, Extended GPL, see docs/LICENSE */

use ILIAS\Exercise\Assignment\PropertyAndActionBuilderUI;

/**
 * fau: exAssTest - new class ilExAssTypeTestResultGUI.
 * Test result assignment type gui implementation
 */
class ilExAssTypeTestResultGUI implements ilExAssignmentTypeGUIInterface
{
    use ilExAssignmentTypeGUIBase;

    protected ilLanguage $lng;
    protected ilCtrl $ctrl;

    public function __construct(
        protected \ILIAS\Exercise\InternalDomainService $domain,
        protected \ILIAS\Exercise\InternalGUIService $gui
    ) {
        $this->lng = $domain->lng();
        $this->ctrl = $gui->ctrl();
    }

    public function addEditFormCustomProperties(ilPropertyFormGUI $form): void
    {
        $input = new ilRepositorySelector2InputGUI($this->lng->txt('exc_ass_type_test_object'), 'test_ref_id');
        $input->getExplorerGUI()->setTypeWhiteList(['root', 'cat', 'crs', 'grp', 'fold', 'tst']);
        $input->getExplorerGUI()->setSelectableTypes(['tst']);
        $input->setRequired(true);
        $input->setInfo($this->lng->txt('exc_ass_type_test_object_info'));
        $form->addItem($input);
    }

    public function importFormToAssignment(ilExAssignment $ass, ilPropertyFormGUI $form): void
    {
        $assTest = ilExAssTypeTestResultAssignment::findOrGetInstance($ass->getId());
        $assTest->setTestRefId((int) $form->getInput('test_ref_id'));
        $assTest->save();
    }

    public function getFormValuesArray(ilExAssignment $ass): array
    {
        $assTest = ilExAssTypeTestResultAssignment::findOrGetInstance($ass->getId());
        return ['test_ref_id' => $assTest->getTestRefId()];
    }

    public function getOverviewContent(ilInfoScreenGUI $a_info, ilExSubmission $a_submission): void
    {
    }

    public function buildSubmissionPropertiesAndActions(PropertyAndActionBuilderUI $builder): void
    {
        $f = $this->gui->ui()->factory();
        $lng = $this->lng;
        $ilCtrl = $this->ctrl;
        $submission = $this->getSubmission();

        // user needs to create a team first (for team assignments)
        if ($submission->getAssignment()->hasTeam() && $submission->hasNoTeamYet()) {
            $ilCtrl->setParameterByClass(ilExSubmissionTeamGUI::class, 'ass_id', $submission->getAssignment()->getId());
            $url = $ilCtrl->getLinkTargetByClass(
                [ilAssignmentPresentationGUI::class, ilExSubmissionGUI::class, ilExSubmissionTeamGUI::class],
                'createSingleMemberTeam'
            );
            $builder->setMainAction($builder::SEC_SUBMISSION, $f->button()->primary($lng->txt('exc_create_team'), $url));
            $builder->addView('submission', $lng->txt('exc_submission'), $url);
            return;
        }

        if ($submission->canSubmit()) {
            $url = $ilCtrl->getLinkTargetByClass(
                [ilAssignmentPresentationGUI::class, ilExSubmissionGUI::class, ilExSubmissionTestResultGUI::class],
                "callTest"
            );
            $builder->setMainAction(
                $builder::SEC_SUBMISSION,
                $f->button()->primary($lng->txt("exc_ass_type_test_open"), $url)
            );
        }

        if (ilObjExerciseAccess::checkExtendedGradingAccess($submission->getAssignment()->getExerciseId(), false)) {
            $url = $ilCtrl->getLinkTargetByClass(
                [ilAssignmentPresentationGUI::class, ilExSubmissionGUI::class, ilExSubmissionTestResultGUI::class],
                "syncTestResults"
            );
            $builder->addAction(
                $builder::SEC_SUBMISSION,
                $f->button()->standard($lng->txt("exc_ass_type_test_sync"), $url)
            );
            $builder->addProperty(
                $builder::SEC_SUBMISSION,
                $lng->txt("exc_ass_type_test_sync_label"),
                ""
            );
            $builder->addProperty(
                $builder::SEC_SUBMISSION,
                $lng->txt("exc_ass_type_test_sync"),
                $lng->txt("exc_ass_type_test_sync_info")
            );
        }
    }
}
