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

declare(strict_types=1);

namespace ILIAS\Exercise\Settings;

use ILIAS\Exercise\InternalDomainService;
use ILIAS\Exercise\InternalGUIService;
use ILIAS\Repository\Form\FormAdapterGUI;
use ILIAS\Exercise\InternalDataService;

class SettingsGUI
{
    public function __construct(
        protected InternalDataService $data,
        protected InternalDomainService $domain,
        protected InternalGUIService $gui,
        protected int $obj_id
    ) {
    }

    public function executeCommand(): void
    {
        $ctrl = $this->gui->ctrl();
        $next_class = $ctrl->getNextClass($this);
        $cmd = $ctrl->getCmd("edit");

        switch ($next_class) {
            default:
                if (in_array($cmd, ["edit", "save"])) {
                    $this->$cmd();
                }
        }
    }

    protected function edit(): void
    {
        $mt = $this->gui->ui()->mainTemplate();
        $form = $this->getEditForm();
        $mt->setContent($form->render());
    }

    protected function getEditForm(): FormAdapterGUI
    {
        $settings = $this->domain->exerciseSettings()->getByObjId($this->obj_id);
        $random_manager = $this->domain->assignment()->randomAssignments(new \ilObjExercise($this->obj_id, false));
        $lng = $this->domain->lng();

        $mand = \ilExAssignment::countMandatory($this->obj_id);
        $pass_nr_min = max($mand, 1);
        $pass_nr_max = \ilExAssignment::count($this->obj_id);

        $form = $this->gui
            ->form([self::class], "save")
            ->section("general", $lng->txt('exc_edit_exercise'))
            // fau: editDidacticTemplateChoice - add the form properties for didactic templates
            ->addStdTitleAndDescription($this->obj_id, "exc");
        $form = $this->addDidacticTemplate($form);
        $form = $form
            // fau.
            ->section("avail", $lng->txt('rep_activation_availability'))
            ->addOnline($this->obj_id, "exc")
            ->section("pres", $lng->txt('obj_presentation'))
            ->addStdTile($this->obj_id, "exc");

        $form = $form
            ->section("pass_exc", $lng->txt('exc_passing_exc'))
            ->switch(
                "pass_mode",
                $lng->txt("exc_pass_mode"),
                (!$random_manager->canBeDeactivated() && $settings->getPassMode() == \ilObjExercise::PASS_MODE_RANDOM)
                    ? $lng->txt("exc_pass_mode_not_changeable_info") . " " .
                    implode(" ", $random_manager->getDeniedDeactivationReasons())
                    : "",
                $settings->getPassMode()
            )
            ->group(
                \ilObjExercise::PASS_MODE_ALL,
                $lng->txt("exc_pass_all"),
                $lng->txt("exc_pass_all_info")
            )
            ->group(
                \ilObjExercise::PASS_MODE_NR,
                $lng->txt("exc_pass_minimum_nr"),
                $lng->txt("exc_pass_minimum_nr_info")
            )
            ->number(
                "pass_nr",
                $lng->txt("exc_min_nr"),
                $lng->txt("exc_min_nr_info"),
                $settings->getPassNr(),
                $pass_nr_min,
                $pass_nr_max
            )->required()
            ->group(
                \ilObjExercise::PASS_MODE_RANDOM,
                $lng->txt("exc_random_selection"),
                !$random_manager->canBeActivated() && $settings->getPassMode() != \ilObjExercise::PASS_MODE_RANDOM
                    ? $lng->txt("exc_random_selection_not_changeable_info") . " " . implode(
                        " ",
                        $random_manager->getDeniedActivationReasons()
                    )
                    : $lng->txt("exc_random_selection_info"),
                (!$random_manager->canBeActivated() && $settings->getPassMode() != \ilObjExercise::PASS_MODE_RANDOM)
            )
            ->number(
                "nr_random_mand",
                $lng->txt("exc_nr_random_mand"),
                "",
                $settings->getNrMandatoryRandom(),
                1,
                \ilExAssignment::count($this->obj_id)
            )
            ->required()
            ->end()
            ->disabled((!$random_manager->canBeDeactivated() && $settings->getPassMode() == \ilObjExercise::PASS_MODE_RANDOM));

        $form = $form
            ->switch(
                "completion_by_submission",
                $lng->txt("exc_passed_status_determination"),
                "",
                $settings->getCompletionBySubmission() ? "1" : "0"
            )
            ->group("0", $lng->txt("exc_completion_by_tutor"))
            ->group(
                "1",
                $lng->txt("exc_completion_by_submission"),
                $lng->txt("exc_completion_by_submission_info")
            )->end();

        $form = $form
            ->section("publishing", $lng->txt('exc_publishing'))
            ->checkbox(
                "show_submissions",
                $lng->txt("exc_show_submissions"),
                $lng->txt("exc_show_submissions_info"),
                $settings->getShowSubmissions()
            );

        $form = $form
            ->section("notification", $lng->txt('exc_notification'))
            ->checkbox(
                "notification",
                $lng->txt("exc_submission_notification"),
                $lng->txt("exc_submission_notification_info"),
                \ilNotification::hasNotification(
                    \ilNotification::TYPE_EXERCISE_SUBMISSION,
                    $this->domain->user()->getId(),
                    $this->obj_id
                )
            );

        $form = $form
            ->section("feedback", $lng->txt('exc_feedback'))
            ->checkbox(
                "exc_settings_feedback_mail",
                $lng->txt("exc_settings_feedback_mail"),
                $lng->txt("exc_settings_feedback_mail_info"),
                $settings->hasTutorFeedbackMail()
            )
            ->checkbox(
                "exc_settings_feedback_file",
                $lng->txt("exc_settings_feedback_file"),
                $lng->txt("exc_settings_feedback_file_info"),
                $settings->hasTutorFeedbackFile()
            )
            ->checkbox(
                "exc_settings_feedback_text",
                $lng->txt("exc_settings_feedback_text"),
                $lng->txt("exc_settings_feedback_text_info"),
                $settings->hasTutorFeedbackText()
            );

        $form = $form->addAdditionalFeatures(
            $this->obj_id,
            [
                \ilObjectServiceSettingsGUI::CUSTOM_METADATA,
                \ilObjectServiceSettingsGUI::ORGU_POSITION_ACCESS,
            ]
        );

        return $form;
    }

    protected function save(): void
    {
        $mt = $this->gui->ui()->mainTemplate();
        $form = $this->getEditForm();
        $ctrl = $this->gui->ctrl();
        $lng = $this->domain->lng();

        $old_settings = $this->domain->exerciseSettings()->getByObjId($this->obj_id);

        if ($form->isValid()) {

            $form->saveStdTitleAndDescription($this->obj_id, "exc");
            // fau: editDidacticTemplateChoice - switch the didactic template without confirmation
            $this->saveDidacticTemplate($form);
            // fau.
            $form->saveStdTile($this->obj_id, "exc");
            $form->saveOnline($this->obj_id, "exc");

            $feedback = 0;
            $feedback += $form->getData("exc_settings_feedback_mail")
                ? \ilObjExercise::TUTOR_FEEDBACK_MAIL
                : 0;
            $feedback += $form->getData("exc_settings_feedback_file")
                ? \ilObjExercise::TUTOR_FEEDBACK_FILE
                : 0;
            $feedback += $form->getData("exc_settings_feedback_text")
                ? \ilObjExercise::TUTOR_FEEDBACK_TEXT
                : 0;
            $settings = $this->data->settings(
                $this->obj_id,
                $old_settings->getInstruction(),    // obsolete?
                $old_settings->getTimeStamp(),  // obsolete?
                $form->getData("pass_mode"),
                (int) $form->getData("nr_random_mand"),
                (int) $form->getData("pass_nr"),
                (bool) $form->getData("show_submissions"),
                (bool) $form->getData("completion_by_submission"),
                (int) $old_settings->getCertificateVisibility(),  // obsolete?
                $feedback
            );
            $this->domain->exerciseSettings()->update($settings);

            \ilNotification::setNotification(
                \ilNotification::TYPE_EXERCISE_SUBMISSION,
                $this->domain->user()->getId(),
                $this->obj_id,
                (bool) $form->getData("notification")
            );
            $form->saveAdditionalFeatures(
                $this->obj_id,
                [
                    \ilObjectServiceSettingsGUI::CUSTOM_METADATA,
                    \ilObjectServiceSettingsGUI::ORGU_POSITION_ACCESS,
                ]
            );

            $mt->setOnScreenMessage("success", $lng->txt("msg_obj_modified"), true);
            $ctrl->redirectByClass(self::class, "edit");
        } else {
            $mt = $this->gui->ui()->mainTemplate();
            $mt->setContent($form->render());
        }
    }

    // fau: editDidacticTemplateChoice - new functions addDidacticTemplate(), saveDidacticTemplate()
    /**
     * Get the didactic templates that can be chosen for the exercise
     * @return array<string, array{0: string, 1: string}>
     * @see \ilObjectGUI::buildDidacticTemplateOptions()
     */
    protected function getDidacticTemplateOptions(int $ref_id): array
    {
        $lng = $this->domain->lng();
        $lng->loadLanguageModule('didactic');
        $options = [
            'dtpl_0' => [
                $lng->txt('didactic_default_type'),
                sprintf($lng->txt('didactic_default_type_info'), $lng->txt('objs_exc'))
            ]
        ];
        $exclusive = false;
        foreach (\ilDidacticTemplateSettings::getInstanceByObjectType('exc')->getTemplates() as $template) {
            if ($template->isEffective($ref_id)) {
                $options['dtpl_' . $template->getId()] = [
                    $template->getPresentationTitle(),
                    $template->getPresentationDescription()
                ];
                $exclusive = $exclusive || $template->isExclusive();
            }
        }
        if ($exclusive) {
            unset($options['dtpl_0']);
        }
        return $options;
    }

    protected function addDidacticTemplate(FormAdapterGUI $form): FormAdapterGUI
    {
        $ref_id = $this->gui->request()->getRefId();
        $options = $this->getDidacticTemplateOptions($ref_id);
        if (count($options) < 2) {
            return $form;
        }
        $current = 'dtpl_' . \ilDidacticTemplateObjSettings::lookupTemplateId($ref_id);
        if (!isset($options[$current])) {
            $options[$current] = [$this->domain->lng()->txt('not_available'), ''];
        }
        $form = $form->radio('didactic_type', $this->domain->lng()->txt('type'), '', $current);
        foreach ($options as $value => $option) {
            $form = $form->radioOption((string) $value, $option[0], $option[1]);
        }
        return $form;
    }

    protected function saveDidacticTemplate(FormAdapterGUI $form): void
    {
        $ref_id = $this->gui->request()->getRefId();
        $value = $form->getData('didactic_type');
        if (!is_string($value) || !isset($this->getDidacticTemplateOptions($ref_id)[$value])) {
            return;
        }
        $new_tpl_id = (int) substr($value, strlen('dtpl_'));
        if ($new_tpl_id !== \ilDidacticTemplateObjSettings::lookupTemplateId($ref_id)) {
            \ilDidacticTemplateUtils::switchTemplate($ref_id, $new_tpl_id);
        }
    }
    // fau.
}
