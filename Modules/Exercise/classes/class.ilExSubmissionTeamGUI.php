<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Submission team
 *
 * @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
 *
 * @ilCtrl_Calls ilExSubmissionTeamGUI: ilRepositorySearchGUI
 * @ingroup ModulesExercise
 */
class ilExSubmissionTeamGUI
{
    /**
     * @var ilCtrl
     */
    protected $ctrl;

    /**
     * @var ilTabsGUI
     */
    protected $tabs_gui;

    /**
     * @var ilLanguage
     */
    protected $lng;

    /**
     * @var ilTemplate
     */
    protected $tpl;

    /**
     * @var ilToolbarGUI
     */
    protected $toolbar;

    /**
     * @var ilObjUser
     */
    protected $user;

    protected $exercise; // [ilObjExercise]
    protected $assignment; // [ilExAssignment]
    protected $submission; // [ilExSubmission]

    // fau: exTeamLimit - add type hint
    /** @var ilExAssignmentTeam */
    protected $team;
    // fau.
    
    public function __construct(ilObjExercise $a_exercise, ilExSubmission $a_submission)
    {
        global $DIC;

        $this->toolbar = $DIC->toolbar();
        $this->user = $DIC->user();
        $ilCtrl = $DIC->ctrl();
        $ilTabs = $DIC->tabs();
        $lng = $DIC->language();
        $tpl = $DIC["tpl"];
        
        $this->exercise = $a_exercise;
        $this->submission = $a_submission;
        $this->assignment = $a_submission->getAssignment();
    
        // :TODO:
        $this->ctrl = $ilCtrl;
        $this->tabs_gui = $ilTabs;
        $this->lng = $lng;
        $this->tpl = $tpl;
    }
    
    public function executeCommand()
    {
        $ilCtrl = $this->ctrl;
        
        $class = $ilCtrl->getNextClass($this);
        $cmd = $ilCtrl->getCmd("submissionScreenTeam");
            
        if (!$this->assignment->hasTeam() ||
            !$this->submission->canView()) {
            return;
        }
        $this->team = $this->submission->getTeam();
        
        if (!$this->submission->isTutor()) {
            self::handleTabs();
            $this->tabs_gui->activateTab("team");
        }
        
        switch ($class) {
            case 'ilrepositorysearchgui':
                $this->ctrl->setReturn($this, 'submissionScreenTeam');
                $rep_search = new ilRepositorySearchGUI();
                if (!$this->submission->isTutor()) {
                    $rep_search->setPrivacyMode(ilUserAutoComplete::PRIVACY_MODE_RESPECT_USER_SETTING);
                }
                $rep_search->setTitle($this->lng->txt("exc_team_member_add"));
                $rep_search->setCallback($this, 'addTeamMemberActionObject');
                $this->ctrl->forwardCommand($rep_search);
                break;
                            
            default:
                $this->{$cmd . "Object"}();
                break;
        }
    }
    
    public static function getOverviewContent(ilInfoScreenGUI $a_info, ilExSubmission $a_submission)
    {
        global $DIC;

        $lng = $DIC->language();
        $ilCtrl = $DIC->ctrl();
        
        if (!$a_submission->getAssignment()->hasTeam()) {
            return;
        }

        // fau: exTeamLimit - add info about max team size
        if ($a_submission->getAssignment()->getMaxTeamMembers()) {
            $a_info->addProperty($lng->txt("exc_max_team_size"), $a_submission->getAssignment()->getMaxTeamMembers());
        }
        // fau.

        $state = ilExcAssMemberState::getInstanceByIds($a_submission->getAssignment()->getId(), $a_submission->getUserId());
                                
        $team_members = $a_submission->getTeam()->getMembers();
        if (sizeof($team_members)) {									// we have a team
            $team = array();
            foreach ($team_members as $member_id) {
                //$team[] = ilObjUser::_lookupFullname($member_id);
                $team[] = ilUserUtil::getNamePresentation($member_id, false, false, "", false);
            }
            $team = implode("; ", $team);
            
            if (!$a_submission->getAssignment()->getTeamTutor()) {
                #23685

                // fau: exTeamRemove - don't show button for team management to participants if submission time is over
                if (!$state->hasSubmissionEnded()) {
                    // any team member upload?
                    if (!$a_submission->getLastSubmission()) {
                        $button = ilLinkButton::getInstance();
                        $button->setCaption("exc_delete_team");
                        $button->setUrl($ilCtrl->getLinkTargetByClass(array("ilExSubmissionGUI", "ilExSubmissionTeamGUI"), "confirmDeleteTeam"));
                        $team .= " " . $button->render();
                    }
                    $button = ilLinkButton::getInstance();
                    $button->setCaption("exc_manage_team");
                    $button->setUrl($ilCtrl->getLinkTargetByClass(array("ilExSubmissionGUI", "ilExSubmissionTeamGUI"), "submissionScreenTeam"));
                }
                // fau.
            } else {
                $button = ilLinkButton::getInstance();
                $button->setCaption("exc_team_log");
                $button->setUrl($ilCtrl->getLinkTargetByClass(array("ilExSubmissionGUI", "ilExSubmissionTeamGUI"), "submissionScreenTeamLog"));
            }

            // fau: exTeamRemove - render only an existing tam button instance
            if (isset($button)) {
                $team .= "<br><br>" . $button->render();
            }
            // fau.

            $a_info->addProperty($lng->txt("exc_team_members"), $team);
        } else {
            //if($a_submission->getAssignment()->beforeDeadline())		// this was "for all users"
            if (!$state->hasSubmissionEnded()) {							// this is for current user/team -> no team creation, if no submission possible
                if (!$a_submission->hasSubmitted()) {
                    $team_info = $lng->txt("exc_no_team_yet_notice");
                } else {
                    $team_info = '<span class="warning">' . $lng->txt("exc_no_team_yet_notice") . '</span>';
                }

                if (!$a_submission->getAssignment()->getTeamTutor()) {
                    $button = ilLinkButton::getInstance();
                    $button->setPrimary(true);
                    $button->setCaption("exc_create_team");		// team creation
                    $button->setUrl($ilCtrl->getLinkTargetByClass(array("ilExSubmissionGUI", "ilExSubmissionTeamGUI"), "createTeam"));
                    $team_info .= " " . $button->render();
                    
                    $team_info .= '<div class="ilFormInfo">' . $lng->txt("exc_no_team_yet_info") . '</div>';
                } else {
                    $team_info .= '<div class="ilFormInfo">' . $lng->txt("exc_no_team_yet_info_tutor") . '</div>';
                }
            } else {
                $team_info = '<span class="warning">' . $lng->txt("exc_create_team_times_up_warning") . '</span>';
            }
            
            $a_info->addProperty($lng->txt("exc_team_members"), $team_info);
        }
    }
    
    public function returnToParentObject()
    {
        $this->ctrl->returnToParent($this);
    }
    
    public static function handleTabs()
    {
        global $DIC;

        $ilTabs = $DIC->tabs();
        $ilCtrl = $DIC->ctrl();
        $lng = $DIC->language();
        
        $ilTabs->addTab(
            "team",
            $lng->txt("exc_team"),
            $ilCtrl->getLinkTargetByClass("ilExSubmissionTeamGUI", "submissionScreenTeam")
        );

        $ilTabs->addTab(
            "log",
            $lng->txt("exc_team_log"),
            $ilCtrl->getLinkTargetByClass("ilExSubmissionTeamGUI", "submissionScreenTeamLog")
        );
    }
    
    protected function canEditTeam()
    {
        return (($this->submission->canSubmit() &&
            !$this->submission->getAssignment()->getTeamTutor()) ||
            $this->submission->isTutor());
    }
    
    /**
    * Displays a form which allows members to manage team uploads
    *
    * @access public
    */
    public function submissionScreenTeamObject()
    {
        $ilToolbar = $this->toolbar;
                        
        // #13414
        $read_only = !$this->canEditTeam();
                
        if ($this->submission->getAssignment()->afterDeadlineStrict(false)) {
            ilUtil::sendInfo($this->lng->txt("exercise_time_over"));
            // fau: exTeamLimit - show limit info instead of add function
        } elseif ($this->assignment->isMaxTeamMembersReached(count($this->team->getMembers())) ) {
            ilUtil::sendInfo($this->lng->txt("exc_max_team_members_reached"));
            // fau.
        } elseif (!$read_only) {
            $add_search = $this->submission->isTutor();
            // add member
            ilRepositorySearchGUI::fillAutoCompleteToolbar(
                $this,
                $ilToolbar,
                array(
                    'auto_complete_name' => $this->lng->txt('user'),
                    'submit_name' => $this->lng->txt('add'),
                    'add_search' => $add_search,
                    'add_from_container' => $this->exercise->getRefId()
                )
            );
            // fau: exTeamLimit - show info about max team size
            if ($this->assignment->getMaxTeamMembers()) {
                ilUtil::sendInfo($this->lng->txt("exc_max_team_size") . ": " . $this->assignment->getMaxTeamMembers());
            }
            // fau.
        } elseif ($this->submission->getAssignment()->getTeamTutor()) {
            ilUtil::sendInfo($this->lng->txt("exc_no_team_yet_info_tutor"));
        }
        
        $tbl = new ilExAssignmentTeamTableGUI(
            $this,
            "submissionScreenTeam",
            ilExAssignmentTeamTableGUI::MODE_EDIT,
            $this->exercise->getRefId(),
            $this->team,
            $read_only
        );
        
        $this->tpl->setContent($tbl->getHTML());
    }
    
    public function addTeamMemberActionObject($a_user_ids = array())
    {
        if (!$this->canEditTeam()) {
            $this->ctrl->redirect("submissionScreenTeam");
        }

        // fau: exTeamLimit - check team size when member is added
        if ($this->assignment->isMaxTeamMembersReached(count($this->team->getMembers())) ) {
            ilUtil::sendFailure($this->lng->txt("exc_max_team_members_reached"), true);
            return false;
        }
        // fau.

        if (!count($a_user_ids)) {
            ilUtil::sendFailure($this->lng->txt("no_checkbox"));
            return false;
        }

        $new_users = [];

        foreach ($a_user_ids as $user_id) {
            if ($this->team->addTeamMember($user_id, $this->exercise->getRefId())) {
                $new_users[] = $user_id;
                
                // #14277
                if (!$this->exercise->members_obj->isAssigned($user_id)) {
                    $this->exercise->members_obj->assignMember($user_id);
                }
            } else {
                // #11959
                ilUtil::sendFailure($this->lng->txt("exc_members_already_assigned_team"), true);
            }
        }
        
        if (sizeof($new_users)) {
            // re-evaluate complete team, as new member could have already submitted
            $this->exercise->processExerciseStatus(
                $this->assignment,
                $this->team->getMembers(),
                $this->submission->hasSubmitted(),
                $this->submission->validatePeerReviews()
            );

            // fau: exAssHook - handle the adding of new users to the team
            $assignmentType = $this->assignment->getAssignmentType();
            if ($assignmentType instanceof ilExAssignmentTypeExtendedInterface) {
                $assignmentType->getTeamHandler($this->assignment)->handleTeamAddedUsers($this->team, $new_users);
            }
            // fau.

            // :TODO: notification?
            ilUtil::sendSuccess($this->lng->txt("settings_saved"), true);
        }

        $this->ctrl->redirect($this, "submissionScreenTeam");
    }
    
    public function confirmDeleteTeamObject()
    {
        $this->confirmRemoveTeamMemberObject(true);
    }
    
    public function confirmRemoveTeamMemberObject($a_full_delete = false)
    {
        $ilUser = $this->user;
        $tpl = $this->tpl;
        
        if (!$this->submission->isTutor()) {
            $ids = [];
            if ((bool) $a_full_delete) {
                $ids = $this->team->getMembers();
            } elseif (isset($_POST["id"]) && is_array($_POST["id"])) {
                $ids = $_POST["id"];
            }
            $ids = array_filter(array_map('intval', $ids));

            if (0 === count($ids) && !$this->canEditTeam()) {
                ilUtil::sendFailure($this->lng->txt("select_one"), true);
                $this->ctrl->redirect($this, "submissionScreenTeam");
            }
        } else {
            $ids = array_filter(array_map('intval', array($_GET["id"])));
            if (0 === count($ids)) {
                $this->returnToParentObject();
            }
        }
            
        $members = $this->team->getMembers();
        if (sizeof($members) <= sizeof($ids)) {
            if (sizeof($members) == 1 && $members[0] == $ilUser->getId()) {
                // direct team deletion - no confirmation
                return $this->removeTeamMemberObject($a_full_delete);
            } else {
                ilUtil::sendFailure($this->lng->txt("exc_team_at_least_one"), true);
                $this->ctrl->redirect($this, "submissionScreenTeam");
            }
        }
            
        $cgui = new ilConfirmationGUI();
        $cgui->setFormAction($this->ctrl->getFormAction($this));
        // fau: exTeamRemove - add notice about forming of a new team
        $assignmentType = $this->assignment->getAssignmentType();

        if ($assignmentType instanceof ilExAssignmentTypeExtendedInterface) {
            $handler = $assignmentType->getTeamHandler($this->assignment);
            $cgui->setHeaderText($handler->getRemoveUsersMessage() . ' ' . $this->lng->txt("exc_team_member_remove_new_team"));
        }
        else {
            $cgui->setHeaderText($this->lng->txt("exc_team_member_remove_sure") . ' ' . $this->lng->txt("exc_team_member_remove_new_team"));
        }
        // fau.
        $cgui->setConfirm($this->lng->txt("remove"), "removeTeamMember");
        $cgui->setCancel($this->lng->txt("cancel"), $this->submission->isTutor()
            ? "returnToParent"
            : "submissionScreenTeam");

        $files = $this->submission->getFiles();
        
        foreach ($ids as $id) {
            $details = array();
            foreach ($files as $file) {
                if ($file["owner_id"] == $id) {
                    $details[] = $file["filetitle"];
                }
            }
            $uname = ilUserUtil::getNamePresentation($id);
            if (sizeof($details)) {
                $uname .= ": " . implode(", ", $details);
            }
            $cgui->addItem("id[]", $id, $uname);
        }

        $tpl->setContent($cgui->getHTML());
    }
    
    public function removeTeamMemberObject($a_full_delete = false)
    {
        $ilUser = $this->user;
        
        $cancel_cmd = $this->submission->isTutor()
            ? "returnToParent"
            : "submissionScreenTeam";

        $ids = [];
        if ((bool) $a_full_delete) {
            $ids = $this->team->getMembers();
        } elseif (isset($_POST["id"]) && is_array($_POST["id"])) {
            $ids = $_POST["id"];
        }
        $ids = array_filter(array_map('intval', $ids));

        if (0 === count($ids) && !$this->canEditTeam()) {
            ilUtil::sendFailure($this->lng->txt("select_one"), true);
            $this->ctrl->redirect($this, $cancel_cmd);
        }
                
        $team_deleted = (bool) $a_full_delete;
        if (!$team_deleted) {
            $members = $this->team->getMembers();
            if (sizeof($members) <= sizeof($ids)) {
                if (sizeof($members) == 1 && $members[0] == $ilUser->getId()) {
                    $team_deleted = true;
                } else {
                    ilUtil::sendFailure($this->lng->txt("exc_team_at_least_one"), true);
                    $this->ctrl->redirect($this, $cancel_cmd);
                }
            }
        }

        // fau: exTeamRemove - prevent removing of own account with submission
        $items = $this->submission->getFiles();
        $remove_with_submissison_ids = [];
        foreach ($items as $item) {
            if (in_array($item['owner_id'], $ids)) {
                $remove_with_submissison_ids[] = $item['owner_id'];
            }
        }
        $remove_with_submissison_ids = array_unique($remove_with_submissison_ids);

        // check if team should be dissolved with an own submission
        if ($team_deleted && in_array($this->user->getId(), $remove_with_submissison_ids)) {
            ilUtil::sendFailure($this->lng->txt("exc_team_remove_self_with_submission"), true);
            $this->ctrl->redirect($this, $cancel_cmd);
        }
        // fau.


        foreach ($ids as $user_id) {
            $this->team->removeTeamMember($user_id, $this->exercise->getRefId());
        }

        // fau: exTeamRemove - form single user teams for all users that have submitted something
        // fau: exAssHook - handle the remaining team and the building of new single teams
        $assignmentType = $this->assignment->getAssignmentType();

        if ($assignmentType instanceof ilExAssignmentTypeExtendedInterface) {
            $handler = $assignmentType->getTeamHandler($this->assignment);
            $handler->handleTeamRemovedUsers($this->team, $ids);
            $remove_with_submissison_ids = $handler->getRemovedUsersWithSubmission();
        }

        foreach ($remove_with_submissison_ids as $user_id) {
            $newTeam = new ilExAssignmentTeam($this->team->createTeam($this->assignment->getId(), $user_id));
            $idl = ilExcIndividualDeadline::getInstance($this->assignment->getId(), $this->team->getId(), true);
            if ($idl->getStartingTimestamp() > 0) {
                $newIdl = ilExcIndividualDeadline::getInstance($this->assignment->getId(), $newTeam->getId(), true);
                $newIdl->setStartingTimestamp($newIdl->getStartingTimestamp());
                $newIdl->save();
            }

            if ($assignmentType instanceof ilExAssignmentTypeExtendedInterface) {
                $assignmentType->getTeamHandler($this->assignment)->handleTeamCreated($newTeam);
            }
        }
        // fau.


        // reset ex team members, as any submission is not valid without team
        // fau: exTeamRemove treat only users that haven't formed a new team
        $this->exercise->processExerciseStatus(
            $this->assignment,
            array_diff($ids, $remove_with_submissison_ids),
            false
        );
        // fau.

        if (!$team_deleted) {
            // re-evaluate complete team, as removed member might have had submitted
            $this->exercise->processExerciseStatus(
                $this->assignment,
                $this->team->getMembers(),
                $this->submission->hasSubmitted(),
                $this->submission->validatePeerReviews()
            );
        }

        ilUtil::sendSuccess($this->lng->txt("settings_saved"), true);

        // fau: exTeamRemove - return to overview if self removed and not in new team
        if (in_array($this->user->getId(), $ids) && !in_array($this->user->getId(), $remove_with_submissison_ids)) {
            $this->ctrl->redirect($this, "returnToParent");
        }
        // fau.

        if (!$team_deleted) {
            $this->ctrl->redirect($this, $cancel_cmd);
        } else {
            $this->ctrl->redirect($this, "returnToParent");
        }
    }
    
    public function submissionScreenTeamLogObject()
    {
        $this->tabs_gui->activateTab("log");
    
        $tbl = new ilExAssignmentTeamLogTableGUI(
            $this,
            "submissionScreenTeamLog",
            $this->team
        );
        
        $this->tpl->setContent($tbl->getHTML());
    }
    
    public function createSingleMemberTeamObject()
    {
        // fau: exAssHook - handle the creation of a team
        $team_id = ilExAssignmentTeam::getTeamId(
            $this->assignment->getId(),
            $this->submission->getUserId(),
            true
        );

        $assignmentType = $this->assignment->getAssignmentType();
        if ($assignmentType instanceof ilExAssignmentTypeExtendedInterface) {
            $team = new ilExAssignmentTeam($team_id);
            $assignmentType->getTeamHandler($this->assignment)->handleTeamCreated($team);
        }
        // fau.

        ilUtil::sendSuccess($this->lng->txt("settings_saved"), true);
        $this->returnToParentObject();
    }
    
    public function showTeamLogObject()
    {
        $tbl = new ilExAssignmentTeamLogTableGUI($this, "showTeamLog", $this->team);
        $this->tpl->setContent($tbl->getHTML());
    }
        
    public function createTeamObject()
    {
        $ilCtrl = $this->ctrl;
        $ilUser = $this->user;
        $lng = $this->lng;
        $tpl = $this->tpl;
        
        if ($this->submission->canSubmit()) {
            $options = ilExAssignmentTeam::getAdoptableTeamAssignments($this->assignment->getExerciseId(), $this->assignment->getId(), $ilUser->getId());
            if (sizeof($options)) {
                $form = new ilPropertyFormGUI();
                $form->setTitle($lng->txt("exc_team_assignment_adopt_user"));
                $form->setFormAction($ilCtrl->getFormAction($this, "createAdoptedTeam"));

                $teams = new ilRadioGroupInputGUI($lng->txt("exc_assignment"), "ass_adpt");
                $teams->setValue(-1);

                $teams->addOption(new ilRadioOption($lng->txt("exc_team_assignment_adopt_none_user"), -1));
                
                $current_map = ilExAssignmentTeam::getAssignmentTeamMap($this->assignment->getId());

                foreach ($options as $id => $item) {
                    $members = array();
                    $free = false;
                    foreach ($item["user_team"] as $user_id) {
                        $members[$user_id] = ilUserUtil::getNamePresentation($user_id);
                        
                        if (array_key_exists($user_id, $current_map)) {
                            $members[$user_id] .= " (" . $lng->txt("exc_team_assignment_adopt_already_assigned") . ")";
                        } else {
                            $free = true;
                        }
                    }
                    asort($members);
                    $members = implode("<br />", $members);
                    $option = new ilRadioOption($item["title"], $id);
                    $option->setInfo($members);
                    if (!$free) {
                        $option->setDisabled(true);
                    }
                    $teams->addOption($option);
                }

                $form->addItem($teams);

                $form->addCommandButton("createAdoptedTeam", $lng->txt("save"));
                $form->addCommandButton("returnToParent", $lng->txt("cancel"));

                $tpl->setContent($form->getHTML());
                return;
            }
            
            ilExAssignmentTeam::getTeamId($this->assignment->getId(), $ilUser->getId(), true);
            
            // #18046
            if (!$this->exercise->members_obj->isAssigned($ilUser->getId())) {
                $this->exercise->members_obj->assignMember($ilUser->getId());
            }
            
            ilUtil::sendSuccess($this->lng->txt("settings_saved"), true);
        }
        
        $ilCtrl->redirect($this, "returnToParent");
    }
    
    public function createAdoptedTeamObject()
    {
        $ilCtrl = $this->ctrl;
        $ilUser = $this->user;
        $lng = $this->lng;
        
        if ($this->submission->canSubmit()) {
            $src_ass_id = (int) $_POST["ass_adpt"];
            if ($src_ass_id > 0) {
                ilExAssignmentTeam::adoptTeams($src_ass_id, $this->assignment->getId(), $ilUser->getId(), $this->exercise->getRefId());
            } else {
                ilExAssignmentTeam::getTeamId($this->assignment->getId(), $ilUser->getId(), true);
            }

            // fau: exAssHook - handle the newly created team
            $assignmentType = $this->assignment->getAssignmentType();
            if ($assignmentType instanceof ilExAssignmentTypeExtendedInterface) {
                $team = ilExAssignmentTeam::getInstanceByUserId($this->assignment->getId(),$ilUser->getId());
                $assignmentType->getTeamHandler($this->assignment)->handleTeamCreated($team);
            }
            // fau.
            
            ilUtil::sendSuccess($lng->txt("settings_saved"), true);
        }
        
        $ilCtrl->redirect($this, "returnToParent");
    }
    
    
    /**
    * Add user as member
    */
    public function addUserFromAutoCompleteObject()
    {
        if (!strlen(trim($_POST['user_login']))) {
            ilUtil::sendFailure($this->lng->txt('msg_no_search_string'));
            $this->submissionScreenTeamObject();
            return false;
        }
        
        $users = explode(',', $_POST['user_login']);

        $user_ids = array();
        foreach ($users as $user) {
            $user_id = ilObjUser::_lookupId($user);

            if (!$user_id) {
                ilUtil::sendFailure($this->lng->txt('user_not_known'));
                return $this->submissionScreenTeamObject();
            }
            
            $user_ids[] = $user_id;
        }
    
        return $this->addTeamMemberActionObject($user_ids);
    }
}
