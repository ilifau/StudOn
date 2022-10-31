<?php

// fau: exAssHook - require the extended type gui interface
require_once "./Modules/Exercise/AssignmentTypes/GUI/classes/interface.ilExAssignmentTypeExtendedGUIInterface.php";
// fau.

/**
 * GUI class for exercise assignments
 *
 * This is not a real GUI class, could be moved to ilObjExerciseGUI
 *
 * @author Alex Killing <alex.killing@gmx.de>
 */
class ilExAssignmentGUI
{
    /**
     * @var ilLanguage
     */
    protected $lng;

    /**
     * @var ilObjUser
     */
    protected $user;

    /**
     * @var ilCtrl
     */
    protected $ctrl;

    protected $exc; // [ilObjExercise]
    protected $current_ass_id; // [int]

    /**
     * @var ilExerciseInternalService
     */
    protected $service;

    /**
     * @var ilExcMandatoryAssignmentManager
     */
    protected $mandatory_manager;

    // fau: exAssHook - assignment type gui
    /**
     * @var ilExAssignmentTypeGUIInterface
     */
    protected $type_gui;
    // fau.

    /**
     * Constructor
     */
    public function __construct(ilObjExercise $a_exc, ilExerciseInternalService $service)
    {
        global $DIC;

        $this->lng = $DIC->language();
        $this->user = $DIC->user();
        $this->ctrl = $DIC->ctrl();
        $this->ui = $DIC->ui();

        $this->exc = $a_exc;
        $this->service = $service;
        $this->mandatory_manager = $service->getMandatoryAssignmentManager($this->exc);
    }
    
    /**
     * Get assignment header for overview
     */
    public function getOverviewHeader(ilExAssignment $a_ass)
    {
        $lng = $this->lng;
        $ilUser = $this->user;
        
        $lng->loadLanguageModule("exc");

        $state = ilExcAssMemberState::getInstanceByIds($a_ass->getId(), $ilUser->getId());

        $tpl = new ilTemplate("tpl.assignment_head.html", true, true, "Modules/Exercise");
        
        // we are completely ignoring the extended deadline here
        
        $idl = $a_ass->getPersonalDeadline($ilUser->getId());
        
        // :TODO: meaning of "ended on"
        $dl = max($a_ass->getDeadline(), $idl);
        // if ($dl &&
        //	$dl < time())
        if ($state->exceededOfficialDeadline()) {
            $tpl->setCurrentBlock("prop");
            $tpl->setVariable("PROP", $lng->txt("exc_ended_on"));
            $tpl->setVariable("PROP_VAL", $state->getCommonDeadlinePresentation());
            $tpl->parseCurrentBlock();
            
            // #14077						// this currently shows the feedback deadline during grace period
            if ($state->getPeerReviewDeadline()) {
                $tpl->setCurrentBlock("prop");
                $tpl->setVariable("PROP", $lng->txt("exc_peer_review_deadline"));
                $tpl->setVariable("PROP_VAL", $state->getPeerReviewDeadlinePresentation());
                $tpl->parseCurrentBlock();
            }
        } elseif (!$state->hasGenerallyStarted()) {
            if ($state->getRelativeDeadline()) {
                $tpl->setCurrentBlock("prop");
                $tpl->setVariable("PROP", $lng->txt("exc_earliest_start_time"));
                $tpl->setVariable("PROP_VAL", $state->getGeneralStartPresentation());
                $tpl->parseCurrentBlock();
            } else {
                $tpl->setCurrentBlock("prop");
                $tpl->setVariable("PROP", $lng->txt("exc_starting_on"));
                $tpl->setVariable("PROP_VAL", $state->getGeneralStartPresentation());
                $tpl->parseCurrentBlock();
            }
        } else {
            if ($state->getCommonDeadline() > 0) {
                $tpl->setCurrentBlock("prop");
                $tpl->setVariable("PROP", $lng->txt("exc_time_to_send"));
                $tpl->setVariable("PROP_VAL", $state->getRemainingTimePresentation());
                $tpl->parseCurrentBlock();

                $tpl->setCurrentBlock("prop");
                $tpl->setVariable("PROP", $lng->txt("exc_edit_until"));
                $tpl->setVariable("PROP_VAL", $state->getCommonDeadlinePresentation());
                $tpl->parseCurrentBlock();
            } elseif ($state->getRelativeDeadline()) {		// if we only have a relative deadline (not started yet)
                $tpl->setCurrentBlock("prop");
                $tpl->setVariable("PROP", $lng->txt("exc_rem_time_after_start"));
                $tpl->setVariable("PROP_VAL", $state->getRelativeDeadlinePresentation());
                $tpl->parseCurrentBlock();

                if ($state->getLastSubmissionOfRelativeDeadline()) {		// if we only have a relative deadline (not started yet)
                    $tpl->setCurrentBlock("prop");
                    $tpl->setVariable("PROP", $lng->txt("exc_rel_last_submission"));
                    $tpl->setVariable("PROP_VAL", $state->getLastSubmissionOfRelativeDeadlinePresentation());
                    $tpl->parseCurrentBlock();
                }
            }


            if ($state->getIndividualDeadline() > 0) {
                $tpl->setCurrentBlock("prop");
                $tpl->setVariable("PROP", $lng->txt("exc_individual_deadline"));
                $tpl->setVariable("PROP_VAL", $state->getIndividualDeadlinePresentation());
                $tpl->parseCurrentBlock();
            }
        }

        $mand = "";
        if ($this->mandatory_manager->isMandatoryForUser($a_ass->getId(), $this->user->getId())) {
            $mand = " (" . $lng->txt("exc_mandatory") . ")";
        }
        // fau: exGradeTime - add info about grade time
        if ($a_ass->getGradeStart() > 0) {
            $tpl->setCurrentBlock("prop");
            $tpl->setVariable("PROP", $lng->txt("exc_grade_start"));
            $tpl->setVariable(
                "PROP_VAL",
                ilDatePresentation::formatDate(new ilDateTime($a_ass->getGradeStart(), IL_CAL_UNIX))
            );
            $tpl->parseCurrentBlock();
        }
        // fau.

        // fau: exResTime - add info about result availability
        if ($a_ass->getResultTime() > 0) {
            $tpl->setCurrentBlock("prop");
            $tpl->setVariable("PROP", $lng->txt("exc_result_available_after"));
            $tpl->setVariable(
                "PROP_VAL",
                ilDatePresentation::formatDate(new ilDateTime($a_ass->getResultTime(), IL_CAL_UNIX))
            );
            $tpl->parseCurrentBlock();
        }
        // fau.

        // fau: exMaxPoints - add info about maximum points and reached points
        // fau: exPlag - add info about plagiarism
        if ((int) $a_ass->getResultTime() <= time()) {

            if ($tag1 = $a_ass->getMemberStatus()->getMarkWithInfo($a_ass)) {
                $tag1 = ' <span class="ilTag">'. $this->lng->txt('exc_mark') . ': ' . $tag1.'</span>';
            }

            if ($tag2 = $a_ass->getMemberStatus()->getPlagInfo($a_ass)) {
                $tag2 = ' <span class="ilTag">'. $tag2.'</span>';
            }
        }
        $tpl->setVariable("TITLE", $a_ass->getTitleWithInfo() . $tag1 . $tag2);
        // fau.

        // status icon
        // fau: exResTime - don't show the result status before the result time is reached
        // fau: exPlag - use effective status and icon
        // fau: exAssTest - check a status that is set without submission
        if ((int) $a_ass->getResultTime() <= time()) {
            // after result time: show real status
            $stat = $a_ass->getMemberStatus()->getEffectiveStatus();
            //$pic = $a_ass->getMemberStatus()->getStatusIcon();
        }
        else {
            // before result time: show real status
            $submission = new ilExSubmission($a_ass, $this->user->getId());
            if ($submission->hasSubmitted()
                || $a_ass->getMemberStatus()->getEffectiveStatus() != "notgraded") {
                $stat = "notgraded";
                //$pic = "scorm/running.svg";
            }
            else {
                $stat = "not_attempted";
                //$pic = "scorm/not_attempted.svg";
            }
        }
        $pic = $this->getIconForStatus(
            $stat,
            ilLPStatusIcons::ICON_VARIANT_SHORT
        );

        //$tpl->setVariable("IMG_STATUS", ilUtil::getImagePath($pic));
        //$tpl->setVariable("ALT_STATUS", $lng->txt("exc_" . $stat));
        $tpl->setVariable(
            "ICON_STAUTS",
            $pic
        );
        // fau.

        return $tpl->get();
    }

    /**
     * Get assignment body for overview
     */
    public function getOverviewBody(ilExAssignment $a_ass)
    {
        global $DIC;

        $ilUser = $DIC->user();

        $this->current_ass_id = $a_ass->getId();

        // fau: exAssHook - set type gui
        $this->type_gui = ilExAssignmentTypesGUI::getInstance()->getById($a_ass->getType());
        // fau.

        $tpl = new ilTemplate("tpl.assignment_body.html", true, true, "Modules/Exercise");

        $state = ilExcAssMemberState::getInstanceByIds($a_ass->getId(), $ilUser->getId());

        $info = new ilInfoScreenGUI(null);
        $info->setTableClass("");

        if ($state->areInstructionsVisible()) {
            $this->addInstructions($info, $a_ass);
            $this->addFiles($info, $a_ass);
        }

        $this->addSchedule($info, $a_ass);

        // fau: exStatement - add Statement section
        if ($a_ass->isAuthorshipStatementRequired() && $state->hasSubmissionStarted()) {
            $this->addRequirements($info, $a_ass);
        }
        // fau.

        if ($state->hasSubmissionStarted()) {
            $this->addSubmission($info, $a_ass);
        }

        $tpl->setVariable("CONTENT", $info->getHTML());
        
        return $tpl->get();
    }
    
    
    protected function addInstructions(ilInfoScreenGUI $a_info, ilExAssignment $a_ass)
    {
        $ilUser = $this->user;

        // fau: exFileSuffixes - Show the allowed suffixes in the exercise instructions
        $info = new ilExAssignmentInfo($a_ass->getId(), $ilUser->getId());
        $inst = $info->getInstructionInfo();
        $suffixes = $a_ass->getFileSuffixesInfo();
        if (count($inst) > 0 || !empty($suffixes)) {
            $a_info->addSection($inst["instruction"]["txt"]);

            if (count($inst) > 0) {
                $a_info->addProperty("", $inst["instruction"]["value"]);
            }

            if (!empty($suffixes)) {
                $a_info->addProperty($this->lng->txt('exc_file_suffixes'), $suffixes);
            }
        }

        // fau: exAssHook - additional instructions
        if ($this->type_gui instanceof ilExAssignmentTypeExtendedGUIInterface) {
            $this->type_gui->getOverviewAdditionalInstructions($a_info, $a_ass);
        }
        // fau.
    }
    
    protected function addSchedule(ilInfoScreenGUI $a_info, ilExAssignment $a_ass)
    {
        $lng = $this->lng;
        $ilUser = $this->user;
        $ilCtrl = $this->ctrl;

        $info = new ilExAssignmentInfo($a_ass->getId(), $ilUser->getId());
        $schedule = $info->getScheduleInfo();

        $state = ilExcAssMemberState::getInstanceByIds($a_ass->getId(), $ilUser->getId());

        $a_info->addSection($lng->txt("exc_schedule"));
        if ($state->getGeneralStart() > 0) {
            $a_info->addProperty($schedule["start_time"]["txt"], $schedule["start_time"]["value"]);
        }


        if ($state->getCommonDeadline()) {		// if we have a common deadline (target timestamp)
            $a_info->addProperty($schedule["until"]["txt"], $schedule["until"]["value"]);
        } elseif ($state->getRelativeDeadline()) {		// if we only have a relative deadline (not started yet)
            $but = "";
            if ($state->hasGenerallyStarted()) {
                $ilCtrl->setParameterByClass("ilobjexercisegui", "ass_id", $a_ass->getId());
                $but = $this->ui->factory()->button()->primary($lng->txt("exc_start_assignment"), $ilCtrl->getLinkTargetByClass("ilobjexercisegui", "startAssignment"));
                $ilCtrl->setParameterByClass("ilobjexercisegui", "ass_id", $_GET["ass_id"]);
                $but = $this->ui->renderer()->render($but);
            }

            $a_info->addProperty($schedule["time_after_start"]["txt"], $schedule["time_after_start"]["value"] . " " . $but);
            if ($state->getLastSubmissionOfRelativeDeadline()) {		// if we only have a relative deadline (not started yet)
                $a_info->addProperty(
                    $lng->txt("exc_rel_last_submission"),
                    $state->getLastSubmissionOfRelativeDeadlinePresentation()
                );
            }
        }

        if ($state->getOfficialDeadline() > $state->getCommonDeadline()) {
            $a_info->addProperty($schedule["individual_deadline"]["txt"], $schedule["individual_deadline"]["value"]);
        }
                
        if ($state->hasSubmissionStarted()) {
            $a_info->addProperty($schedule["time_to_send"]["txt"], $schedule["time_to_send"]["value"]);
        }
    }
    
    protected function addPublicSubmissions(ilInfoScreenGUI $a_info, ilExAssignment $a_ass)
    {
        $lng = $this->lng;
        $ilUser = $this->user;
        

        $state = ilExcAssMemberState::getInstanceByIds($a_ass->getId(), $ilUser->getId());

        // submissions are visible, even if other users may still have a larger individual deadline
        if ($state->hasSubmissionEnded()) {
            $button = ilLinkButton::getInstance();
            $button->setCaption("exc_list_submission");
            $button->setUrl($this->getSubmissionLink("listPublicSubmissions"));

            $a_info->addProperty($lng->txt("exc_public_submission"), $button->render());
        } else {
            $a_info->addProperty(
                $lng->txt("exc_public_submission"),
                $lng->txt("exc_msg_public_submission")
            );
        }
    }
    
    protected function addFiles(ilInfoScreenGUI $a_info, ilExAssignment $a_ass)
    {
        $lng = $this->lng;
        $ilCtrl = $this->ctrl;

        $lng->loadLanguageModule("exc");
        
        $files = $a_ass->getFiles();

        if (count($files) > 0) {
            $a_info->addSection($lng->txt("exc_files"));

            global $DIC;

            //file has -> name,fullpath,size,ctime
            $cnt = 0;
            foreach ($files as $file) {
                $cnt++;
                // get mime type
                $mime = ilObjMediaObject::getMimeType($file['fullpath']);

                list($format, $type) = explode("/", $mime);

                $ui_factory = $DIC->ui()->factory();
                $ui_renderer = $DIC->ui()->renderer();

                if (in_array($mime, array("image/jpeg", "image/svg+xml", "image/gif", "image/png"))) {
                    $item_id = "il-ex-modal-img-" . $a_ass->getId() . "-" . $cnt;


                    $image = $ui_renderer->render($ui_factory->image()->responsive($file['fullpath'], $file['name']));
                    $image_lens = ilUtil::getImagePath("enlarge.svg");

                    $modal = ilModalGUI::getInstance();
                    $modal->setId($item_id);
                    $modal->setType(ilModalGUI::TYPE_LARGE);
                    $modal->setBody($image);
                    $modal->setHeading($file["name"]);
                    $modal = $modal->getHTML();

                    $img_tpl = new ilTemplate("tpl.image_file.html", true, true, "Modules/Exercise");
                    $img_tpl->setCurrentBlock("image_content");
                    $img_tpl->setVariable("MODAL", $modal);
                    $img_tpl->setVariable("ITEM_ID", $item_id);
                    $img_tpl->setVariable("IMAGE", $image);
                    $img_tpl->setvariable("IMAGE_LENS", $image_lens);
                    $img_tpl->setvariable("ALT_LENS", $lng->txt("exc_fullscreen"));
                    $img_tpl->parseCurrentBlock();

                    $a_info->addProperty($file["name"], $img_tpl->get());
                } elseif (in_array($mime, array("audio/mpeg", "audio/ogg", "video/mp4", "video/x-flv", "video/webm"))) {
                    $media_tpl = new ilTemplate("tpl.media_file.html", true, true, "Modules/Exercise");
                    $mp = new ilMediaPlayerGUI();
                    $mp->setFile($file['fullpath']);
                    $media_tpl->setVariable("MEDIA", $mp->getMediaPlayerHtml());

                    $but = $ui_factory->button()->shy(
                        $lng->txt("download"),
                        $this->getSubmissionLink("downloadFile", array("file" => urlencode($file["name"])))
                    );
                    $media_tpl->setVariable("DOWNLOAD_BUTTON", $ui_renderer->render($but));
                    $a_info->addProperty($file["name"], $media_tpl->get());
                } else {
                    $a_info->addProperty($file["name"], $lng->txt("download"), $this->getSubmissionLink("downloadFile", array("file" => urlencode($file["name"]))));
                }
            }
        }
    }

    // fau: exStatement - new function addRequirements
    protected function addRequirements(ilInfoScreenGUI $a_info, ilExAssignment $a_ass)
    {
        include_once("./Modules/Exercise/AssMemberState/classes/class.ilExcAssMemberState.php");
        $state = ilExcAssMemberState::getInstanceByIds($a_ass->getId(), $this->user->getId());
        $a_info->addSection($this->lng->txt("exc_info_section_requirements"));
        if ($this->user->getLanguage() == 'de') {
            $tpl_stat = new ilTemplate('tpl.exc_authorship_statement_de.html', false, false, 'Modules/Exercise');
        }
        else {
            $tpl_stat = new ilTemplate('tpl.exc_authorship_statement_en.html', false, false, 'Modules/Exercise');
        }

        $this->ctrl->setParameterByClass('ilobjexercisegui', 'ass_id', $a_ass->getId());

        if (!$a_ass->getMemberStatus()->hasAuthorshipStatement()) {

            $button = ilLinkButton::getInstance();
            $button->setPrimary(true);
            $button->setCaption($this->lng->txt('exc_accept_authorship_statement'), false);
            $button->setUrl($this->ctrl->getLinkTargetByClass('ilobjexercisegui', 'acceptAuthorshipStatement'));
            $html = $button->render();
        }
        else {
            $date = new ilDateTime($a_ass->getMemberStatus()->getAuthorshipStatementTime(), IL_CAL_DATETIME);
            $html = '<p>' . sprintf($this->lng->txt('exc_authorship_statement_accepted_at'), ilDatePresentation::formatDate($date)) .'</p>';

//            $button = ilLinkButton::getInstance();
//            $button->setPrimary(false);
//            $button->setCaption($this->lng->txt('exc_revoke_authorship_statement'), false);
//            $button->setUrl($this->ctrl->getLinkTargetByClass('ilobjexercisegui', 'revokeAuthorshipStatement'));
//            $html .= $button->render();
        }

        $a_info->addProperty($this->lng->txt("exc_authorship_statement"), $tpl_stat->get() . $html);
    }
    // fau.

    protected function addSubmission(ilInfoScreenGUI $a_info, ilExAssignment $a_ass)
    {
        $lng = $this->lng;
        $ilCtrl = $this->ctrl;
        $ilUser = $this->user;

        $state = ilExcAssMemberState::getInstanceByIds($a_ass->getId(), $ilUser->getId());

        $a_info->addSection($lng->txt("exc_your_submission"));

        $submission = new ilExSubmission($a_ass, $ilUser->getId());

        // fau: exAssHook - add own submission section
        if ($this->type_gui instanceof ilExAssignmentTypeExtendedGUIInterface && $this->type_gui->hasOwnOverviewSubmission()) {
            $this->type_gui->getOverviewSubmission($a_info, $submission);
            if ($submission->hasSubmitted()) {
                // needed for $show_global_feedback below
                $last_sub = $submission->getLastSubmission();
            }
        }
        else {
            ilExSubmissionGUI::getOverviewContent($a_info, $submission, $this->exc);

            $last_sub = null;
            if ($submission->hasSubmitted()) {
                $last_sub = $submission->getLastSubmission();
                if ($last_sub) {
                    $last_sub = ilDatePresentation::formatDate(new ilDateTime($last_sub, IL_CAL_DATETIME));
                    $a_info->addProperty($lng->txt("exc_last_submission"), $last_sub);
                }
            }
        }
        // fau.


        if ($this->exc->getShowSubmissions()) {
            $this->addPublicSubmissions($a_info, $a_ass);
        }

        ilExPeerReviewGUI::getOverviewContent($a_info, $submission);

        // global feedback / sample solution

        // fau: exAssHook - extended check for global fedback file
        $has_global_feedback = $a_ass->getFeedbackFile() ||
            ($this->type_gui instanceof ilExAssignmentTypeExtendedGUIInterface && $this->type_gui->hasOwnOverviewGeneralFeedback());

        if ($a_ass->getFeedbackDate() == ilExAssignment::FEEDBACK_DATE_DEADLINE) {
            $show_global_feedback = ($state->hasSubmissionEndedForAllUsers() && $has_global_feedback);
        }
        //If it is not well configured...(e.g. show solution before deadline)
        //the user can get the solution before he summit it.
        //we can check in the elseif $submission->hasSubmitted()
        elseif ($a_ass->getFeedbackDate() == ilExAssignment::FEEDBACK_DATE_CUSTOM) {
            $show_global_feedback = ($a_ass->afterCustomDate() && $has_global_feedback);
        }
        // fau: exFeedbackNever - suppress feedback generally
        elseif($a_ass->getFeedbackDate() == ilExAssignment::FEEDBACK_DATE_NEVER) {
            $show_global_feedback = false;
            // fau.
        } else {
            $show_global_feedback = ($last_sub && $has_global_feedback);
        }
        // fau.

        // fau: exResTime - show tutor feedback section after result time
        // fau: exAssHook - provide submission instead of feedback id
        if ((int) $a_ass->getResultTime() <= time()) {
            $this->addSubmissionFeedback($a_info, $a_ass, $submission, $show_global_feedback);
        }
        // fau.
    }

    // fau: exAssHook - change feedback id parameter to submission (avoid multiple instantiation)
    protected function addSubmissionFeedback(ilInfoScreenGUI $a_info, ilExAssignment $a_ass, ilExSubmission $a_submission, $a_show_global_feedback)
        // fau.
    {
        $lng = $this->lng;

        $storage = new ilFSStorageExercise($a_ass->getExerciseId(), $a_ass->getId());
        // fau: exAssHook - use submission
        $cnt_files = $storage->countFeedbackFiles($a_submission->getFeedbackId());
        // fau.
        
        $lpcomment = $a_ass->getMemberStatus()->getComment();
        // fau: exPlag -get the effective mark and status
        $mark = $a_ass->getMemberStatus()->getEffectiveMark($a_ass->hasNumericPoints());
        $status = $a_ass->getMemberStatus()->getEffectiveStatus();
        // fau.
        
        if ($lpcomment != "" ||
            $mark != "" ||
            $status != "notgraded" ||
            $cnt_files > 0 ||
            $a_show_global_feedback) {
            $a_info->addSection($lng->txt("exc_feedback_from_tutor"));
            if ($lpcomment != "") {
                $a_info->addProperty(
                    $lng->txt("exc_comment"),
                    // fau: exFeedbackHtml - allow secure html output
                    nl2br(ilUtil::secureString($lpcomment))
                    // fau.
                );

            }
            if ($mark != "") {
                $a_info->addProperty(
                    $lng->txt("exc_mark"),
                    // fau: exMaxPoints - show extended mark
                    $a_ass->getMemberStatus()->getMarkWithInfo($a_ass)
                    // fau.
                );
            }

            // fau: exPlag - show plagiarism info
            if ($a_ass->getMemberStatus()->isPlagDetected()) {
                $a_info->addProperty(
                    $lng->txt("exc_plagiarism"),
                    $a_ass->getMemberStatus()->getPlagInfo($a_ass)
                );
                if ($a_ass->getMemberStatus()->getPlagComment()) {
                    $a_info->addProperty(
                        $lng->txt("exc_plag_comment"),
                        nl2br(ilUtil::secureString($a_ass->getMemberStatus()->getPlagComment()))
                    );
                }
            }
            // fau.

            if ($status != "" && $status != "notgraded") {
                $img = $this->getIconForStatus($status);
                $a_info->addProperty(
                    $lng->txt("status"),
                    $img . " " . $lng->txt("exc_" . $status)
                );
            }

            // fau: exAssHook - additional feedback
            if ($this->type_gui instanceof ilExAssignmentTypeExtendedGUIInterface) {
                $this->type_gui->getOverviewAdditionalFeedback( $a_info, $a_submission);
            }
            // fau.


            if ($cnt_files > 0) {
                $a_info->addSection($lng->txt("exc_fb_files") .
                    '<a name="fb' . $a_ass->getId() . '"></a>');

                if ($cnt_files > 0) {
                    // fau: exAssHook - use submission
                    $files = $storage->getFeedbackFiles($a_submission->getFeedbackId());
                    // fau.
                    // fau: exMultiFeedbackStructure - better listing of nested files
                    $i = 1;
                    foreach ($files as $file) {
                        $a_info->addProperty(
                            $lng->txt("file") . ' ' . $i++,
                            $this->getFeedbackFileDisplay($file),
                            $this->getSubmissionLink("downloadFeedbackFile", array("file" => urlencode($file)))
                        );
                    }
                    // fau.
                }
            }

            // #15002 - global feedback
            if ($a_show_global_feedback) {
                $a_info->addSection($lng->txt("exc_global_feedback_file"));

                // fau: exAssHook - show own general feedback
                if ($this->type_gui instanceof ilExAssignmentTypeExtendedGUIInterface && $this->type_gui->hasOwnOverviewGeneralFeedback()) {
                    $this->type_gui->getOverviewGeneralFeedback($a_info, $a_ass);
                }
                elseif ($a_ass->getFeedbackFile()) {

                    $a_info->addProperty(
                        $a_ass->getFeedbackFile(),
                        $lng->txt("download"),
                        $this->getSubmissionLink("downloadGlobalFeedbackFile")
                    );
                }
                // fau.
           }
        }
    }

    // fau: exMultiFeedbackStructure - new function getFeedbackFileDisplay()
    /**
     * Get the display title of a feedback file (extracting the member from the sub folder name)
     * @param string $file
     * @return string
     * @see ilExAssignment::getMemberListData()
     */
    public function getFeedbackFileDisplay($file) {
        //  $mem_dir = $name["lastname"] . "_" . $name["firstname"] . "_" . $name["login"] . "_" . $name["user_id"];

        $pi = pathinfo($file);
        $dirname = $pi['dirname'];
        $basename = $pi['basename'];
        $parts = explode('_', $dirname);


        if (count($parts) == 4) {
            $lastname = $parts[0];
            $firstname = $parts[1];
            $user_id = $parts[3];
            $login = $parts[2];

            return $basename . ' (' . $firstname . ' ' . $lastname . ')';
        }
        else {
            return $file;
        }
    }
    // fau.


    /**
     * Get time string for deadline
     */
    public function getTimeString($a_deadline)
    {
        $lng = $this->lng;
        
        if ($a_deadline == 0) {
            return $lng->txt("exc_submit_convenience_no_deadline");
        }
        
        if ($a_deadline - time() <= 0) {
            $time_str = $lng->txt("exc_time_over_short");
        } else {
            $time_str = ilUtil::period2String(new ilDateTime($a_deadline, IL_CAL_UNIX));
        }

        return $time_str;
    }
    
    protected function getSubmissionLink($a_cmd, array $a_params = null)
    {
        $ilCtrl = $this->ctrl;
        
        if (is_array($a_params)) {
            foreach ($a_params as $name => $value) {
                $ilCtrl->setParameterByClass("ilexsubmissiongui", $name, $value);
            }
        }
        
        $ilCtrl->setParameterByClass("ilexsubmissiongui", "ass_id", $this->current_ass_id);
        $url = $ilCtrl->getLinkTargetByClass("ilexsubmissiongui", $a_cmd);
        $ilCtrl->setParameterByClass("ilexsubmissiongui", "ass_id", "");
        
        if (is_array($a_params)) {
            foreach ($a_params as $name => $value) {
                $ilCtrl->setParameterByClass("ilexsubmissiongui", $name, "");
            }
        }
        
        return $url;
    }

    /**
     * Get the rendered icon for a status (failed, passed or not graded).
     */
    protected function getIconForStatus(string $status, int $variant = ilLPStatusIcons::ICON_VARIANT_LONG) : string
    {
        $icons = ilLPStatusIcons::getInstance($variant);
        $lng = $this->lng;

        switch ($status) {
            case "passed":
                return $icons->renderIcon(
                    $icons->getImagePathCompleted(),
                    $lng->txt("exc_" . $status)
                );

            case "failed":
                return $icons->renderIcon(
                    $icons->getImagePathFailed(),
                    $lng->txt("exc_" . $status)
                );

            default:
                return $icons->renderIcon(
                    $icons->getImagePathNotAttempted(),
                    $lng->txt("exc_" . $status)
                );
        }
    }
}
