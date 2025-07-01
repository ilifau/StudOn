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

use ILIAS\UI\Factory as UIFactory;
use ILIAS\UI\Renderer as UIRenderer;
use ILIAS\Test\InternalRequestService;

/**
 * @ilCtrl_Calls ilParticipantsTestResultsGUI: ilTestEvaluationGUI
 * @ilCtrl_Calls ilParticipantsTestResultsGUI: ilAssQuestionPageGUI
 * @ilCtrl_Calls ilParticipantsTestResultsGUI: ilAssSpecFeedbackPageGUI
 * @ilCtrl_Calls ilParticipantsTestResultsGUI: ilAssGenFeedbackPageGUI
 */
class ilParticipantsTestResultsGUI
{
    public const CMD_SHOW_PARTICIPANTS = 'showParticipants';
    public const CMD_CONFIRM_DELETE_ALL_USER_RESULTS = 'deleteAllUserResults';
    public const CMD_PERFORM_DELETE_ALL_USER_RESULTS = 'confirmDeleteAllUserResults';
    public const CMD_CONFIRM_DELETE_SELECTED_USER_RESULTS = 'deleteSingleUserResults';
    public const CMD_PERFORM_DELETE_SELECTED_USER_RESULTS = 'confirmDeleteSelectedUserData';

    private ?ilObjTest $test_obj = null;
    private ?ilTestQuestionSetConfig $question_set_config = null;
    private ?ilTestAccess $test_access = null;
    private ?ilTestObjectiveOrientedContainer $objective_parent = null;


    public function __construct(
        private ilCtrlInterface $ctrl,
        private ilLanguage $lng,
        private ilDBInterface $db,
        private ilObjUser $user,
        private ilTabsGUI $tabs,
        private ilToolbarGUI $toolbar,
        private ilGlobalTemplateInterface $main_tpl,
        private UIFactory $ui_factory,
        private UIRenderer $ui_renderer,
        private ilTestParticipantAccessFilterFactory $participant_access_filter_factory,
        private InternalRequestService $testrequest,
        private \ILIAS\HTTP\GlobalHttpState $http,
        private \ILIAS\Refinery\Factory $refinery,
    ) {
    }

    public function getObject(): ?ilObjTest
    {
        return $this->test_obj;
    }

    public function getTestObj(): ?ilObjTest
    {
        return $this->test_obj;
    }

    public function setTestObj(ilObjTest $test_obj): void
    {
        $this->test_obj = $test_obj;
    }

    public function getQuestionSetConfig(): ?ilTestQuestionSetConfig
    {
        return $this->question_set_config;
    }

    public function setQuestionSetConfig(ilTestQuestionSetConfig $question_set_config): void
    {
        $this->question_set_config = $question_set_config;
    }

    public function getTestAccess(): ?ilTestAccess
    {
        return $this->test_access;
    }

    public function setTestAccess(ilTestAccess $test_access): void
    {
        $this->test_access = $test_access;
    }

    public function getObjectiveParent(): ?ilTestObjectiveOrientedContainer
    {
        return $this->objective_parent;
    }

    public function setObjectiveParent(ilTestObjectiveOrientedContainer $objective_parent): void
    {
        $this->objective_parent = $objective_parent;
    }

    public function executeCommand(): void
    {
        switch ($this->ctrl->getNextClass($this)) {
            case "iltestevaluationgui":
                $this->forwardToEvaluationGUI();
                break;

            case 'ilassquestionpagegui':
                $forwarder = new ilAssQuestionPageCommandForwarder();
                $forwarder->setTestObj($this->getTestObj());
                $forwarder->forward();
                break;

            default:

                $command = $this->ctrl->getCmd(self::CMD_SHOW_PARTICIPANTS) . 'Cmd';
                $this->{$command}();
        }
    }
    /**
     * @return list<int>
     */
    private function getUserIdsFromPost(): array
    {
        return $this->http->wrapper()->post()->retrieve(
            'chbUser',
            $this->refinery->byTrying([
                $this->refinery->kindlyTo()->listOf($this->refinery->kindlyTo()->int()),
                $this->refinery->always([])
            ])
        );
    }

    private function forwardToEvaluationGUI(): void
    {
        $gui = new ilTestEvaluationGUI($this->getTestObj());
        $gui->setObjectiveOrientedContainer($this->getObjectiveParent());
        $gui->setTestAccess($this->getTestAccess());
        $this->tabs->clearTargets();
        $this->tabs->clearSubTabs();
        $this->ctrl->forwardCommand($gui);
    }

    private function buildTableGUI(): ilParticipantsTestResultsTableGUI
    {
        $table_gui = new ilParticipantsTestResultsTableGUI(
            $this,
            self::CMD_SHOW_PARTICIPANTS,
            $this->ui_factory,
            $this->ui_renderer
        );
        $table_gui->setTitle($this->lng->txt('tst_tbl_results_grades'));
        return $table_gui;
    }

    private function showParticipantsCmd(): void
    {
        ilSession::clear("show_user_results");

        if ($this->getQuestionSetConfig()->areDepenciesBroken()) {
            $this->main_tpl->setOnScreenMessage(
                'failure',
                $this->getQuestionSetConfig()->getDepenciesBrokenMessage($this->lng)
            );
        } elseif ($this->getQuestionSetConfig()->areDepenciesInVulnerableState()) {
            $this->main_tpl->setOnScreenMessage(
                'info',
                $this->getQuestionSetConfig()->getDepenciesInVulnerableStateMessage($this->lng)
            );
        }

        $manage_participant_filter = $this->participant_access_filter_factory->getManageParticipantsUserFilter(
            $this->getTestObj()->getRefId()
        );
        $access_results_filter = $this->participant_access_filter_factory->getAccessResultsUserFilter(
            $this->getTestObj()->getRefId()
        );

        $full_participant_list = $this->getTestObj()->getActiveParticipantList();
        $participantList = $full_participant_list->getAccessFilteredList($manage_participant_filter);
        $access_to_results_participants = $full_participant_list->getAccessFilteredList($access_results_filter);
        foreach ($access_to_results_participants as $participant) {
            if (!$participantList->isActiveIdInList($participant->getActiveId())) {
                $participantList->addParticipant($participant);
            }
        }

        $scored_participant_list = $participantList->getScoredParticipantList();

        $table_gui = $this->buildTableGUI();

        if (!$this->getQuestionSetConfig()->areDepenciesBroken()) {
            $table_gui->setAccessResultsCommandsEnabled(
                $this->getTestAccess()->checkParticipantsResultsAccess()
            );

            $table_gui->setManageResultsCommandsEnabled(
                $this->getTestAccess()->checkManageParticipantsAccess()
            );

            if ($this->test_access->checkManageParticipantsAccess()
                && $scored_participant_list->hasScorings()) {
                $this->addDeleteAllTestResultsButton($this->toolbar);
            }
        }

        $table_gui->setAnonymity($this->getTestObj()->getMainSettings()->getGeneralSettings()->getAnonymity());

        $table_gui->initColumns();
        $table_gui->initCommands();

        $table_gui->setData($participantList->getScoringsTableRows());

        $this->main_tpl->setContent($table_gui->getHTML());
    }

    private function addDeleteAllTestResultsButton(ilToolbarGUI $toolbar): void
    {
        $delete_all_results_btn = $this->ui_factory->button()->standard($this->lng->txt('delete_all_user_data'), $this->ctrl->getLinkTarget($this, 'deleteAllUserResults'));
        $toolbar->addComponent($delete_all_results_btn);
    }

    /**
     * Asks for a confirmation to delete all user data of the test object
     */
    private function deleteAllUserResultsCmd(): void
    {
        $cgui = new ilConfirmationGUI();
        $cgui->setFormAction($this->ctrl->getFormAction($this));
        $cgui->setHeaderText($this->lng->txt("delete_all_user_data_confirmation"));
        $cgui->setCancel($this->lng->txt("cancel"), self::CMD_SHOW_PARTICIPANTS);
        $cgui->setConfirm($this->lng->txt("proceed"), self::CMD_PERFORM_DELETE_ALL_USER_RESULTS);

        $this->main_tpl->setContent($cgui->getHTML());
    }

    /**
     * Deletes all user data for the test object
     */
    private function confirmDeleteAllUserResultsCmd(): void
    {
        $access_filter = $this->participant_access_filter_factory->getManageParticipantsUserFilter(
            $this->getTestObj()->getRefId()
        );

        $participant_data = new ilTestParticipantData($this->db, $this->lng);
        $participant_data->setParticipantAccessFilter($access_filter);
        $participant_data->load($this->getTestObj()->getTestId());

        $this->getTestObj()->removeTestResults($participant_data);

        $this->main_tpl->setOnScreenMessage('success', $this->lng->txt("tst_all_user_data_deleted"), true);
        $this->ctrl->redirect($this, self::CMD_SHOW_PARTICIPANTS);
    }

    /**
     * Asks for a confirmation to delete selected user data of the test object
     */
    private function deleteSingleUserResultsCmd(): void
    {
        $usr_ids = $this->getUserIdsFromPost();
        if ($usr_ids === []) {
            $this->main_tpl->setOnScreenMessage('info', $this->lng->txt("select_one_user"), true);
            $this->ctrl->redirect($this);
        }

        $cgui = new ilConfirmationGUI();
        $cgui->setHeaderText($this->lng->txt("confirm_delete_single_user_data"));

        $cgui->setFormAction($this->ctrl->getFormAction($this));
        $cgui->setCancel($this->lng->txt("cancel"), self::CMD_SHOW_PARTICIPANTS);
        $cgui->setConfirm($this->lng->txt("confirm"), self::CMD_PERFORM_DELETE_SELECTED_USER_RESULTS);

        $access_filter = $this->participant_access_filter_factory->getManageParticipantsUserFilter($this->getTestObj()->getRefId());

        $participant_data = new ilTestParticipantData($this->db, $this->lng);
        $participant_data->setParticipantAccessFilter($access_filter);

        $participant_data->setActiveIdsFilter($usr_ids);

        $participant_data->load($this->getTestObj()->getTestId());

        foreach ($participant_data->getActiveIds() as $active_id) {
            if ($this->test_obj->getAnonymity()) {
                $username = $this->lng->txt('anonymous');
            } else {
                $username = $participant_data->getFormatedFullnameByActiveId($active_id);
            }

            $cgui->addItem(
                "chbUser[]",
                (string) $active_id,
                $username,
                ilUtil::getImagePath("standard/icon_usr.svg"),
                $this->lng->txt("usr")
            );
        }

        $this->main_tpl->setContent($cgui->getHTML());
    }

    /**
     * Deletes the selected user data for the test object
     */
    private function confirmDeleteSelectedUserDataCmd(): void
    {
        $usr_ids = $this->getUserIdsFromPost();
        if ($usr_ids !== []) {
            $access_filter = $this->participant_access_filter_factory->getManageParticipantsUserFilter($this->getTestObj()->getRefId());

            $participant_data = new ilTestParticipantData($this->db, $this->lng);
            $participant_data->setParticipantAccessFilter($access_filter);
            $participant_data->setActiveIdsFilter($usr_ids);

            $participant_data->load($this->getTestObj()->getTestId());

            $this->getTestObj()->removeTestResults($participant_data);

            $this->main_tpl->setOnScreenMessage('success', $this->lng->txt("tst_selected_user_data_deleted"), true);
        }

        $this->ctrl->redirect($this, self::CMD_SHOW_PARTICIPANTS);
    }

    /**
     * Shows the pass overview and the answers of one ore more users for the scored pass
     */
    private function showDetailedResultsCmd(): void
    {
        $usr_ids = $this->getUserIdsFromPost();
        if ($usr_ids === []) {
            $this->main_tpl->setOnScreenMessage('info', $this->lng->txt('select_one_user'), true);
            $this->ctrl->redirect($this);
        }

        ilSession::set('show_user_results', $usr_ids);
        $results_href = $this->ctrl->getLinkTargetByClass(
            [ilTestResultsGUI::class, ilParticipantsTestResultsGUI::class, ilTestEvaluationGUI::class],
            'multiParticipantsPassDetails'
        );
        $this->ctrl->redirectToURL($results_href);
    }

    /**
     * Shows the answers of one ore more users for the scored pass
     */
    private function showUserAnswersCmd(): void
    {
        $usr_ids = $this->getUserIdsFromPost();
        if ($usr_ids !== []) {
            ilSession::set('show_user_results', $usr_ids);
        }
        $this->showUserResults(false, true);
    }

    /**
     * Shows the pass overview of the scored pass for one ore more users
     */
    private function showPassOverviewCmd(): void
    {
        $usr_ids = $this->getUserIdsFromPost();
        if ($usr_ids !== []) {
            ilSession::set('show_user_results', $usr_ids);
        }
        $this->showUserResults(true, false);
    }

    /**
     * Shows the pass overview of the scored pass for one ore more users
     */
    private function showUserResults($show_pass_details, $show_answers, $show_reached_points = false): void
    {
        $this->tabs->clearTargets();
        $this->tabs->clearSubTabs();

        $show_user_results = ilSession::get("show_user_results");

        if (!is_array($show_user_results) || count($show_user_results) === 0) {
            $this->main_tpl->setOnScreenMessage('info', $this->lng->txt("select_one_user"), true);
            $this->ctrl->redirect($this, self::CMD_SHOW_PARTICIPANTS);
        }

        $template = $this->createUserResults(
            $show_pass_details,
            $show_answers,
            $show_reached_points,
            $show_user_results
        );

        if ($template instanceof ilTemplate) {
            $this->main_tpl->setVariable("ADM_CONTENT", $template->get());
            $this->main_tpl->addCss(ilUtil::getStyleSheetLocation("output", "test_print.css", "Modules/Test"), "print");
            if ($this->getTestObj()->getShowSolutionAnswersOnly()) {
                $this->main_tpl->addCss(
                    ilUtil::getStyleSheetLocation("output", "test_print_hide_content.css", "Modules/Test"),
                    "print"
                );
            }
        }
    }

    public function createUserResults(
        bool $show_pass_details,
        bool $show_answers,
        bool $show_reached_points,
        array $show_user_results
    ): ilTemplate {
        $this->tabs->setBackTarget(
            $this->lng->txt('back'),
            $this->ctrl->getLinkTarget($this, self::CMD_SHOW_PARTICIPANTS)
        );

        if ($this->getObjectiveParent()->isObjectiveOrientedPresentationRequired()) {
            $courseLink = ilLink::_getLink($this->getObjectiveParent()->getRefId());
            $this->tabs->setBack2Target($this->lng->txt('back_to_objective_container'), $courseLink);
        }

        $template = new ilTemplate("tpl.il_as_tst_participants_result_output.html", true, true, "Modules/Test");

        $toolbar = new ilTestResultsToolbarGUI($this->ctrl, $this->main_tpl, $this->lng);

        if ($show_answers) {
            if ($this->testrequest->isset('show_best_solutions')) {
                ilSession::set('tst_results_show_best_solutions', true);
            } elseif ($this->testrequest->isset('hide_best_solutions')) {
                ilSession::set('tst_results_show_best_solutions', false);
            } elseif (ilSession::get('tst_results_show_best_solutions') !== null) {
                ilSession::set('tst_results_show_best_solutions', false);
            }

            if (ilSession::get('tst_results_show_best_solutions')) {
                $this->ctrl->setParameter($this, 'hide_best_solutions', '1');
                $toolbar->setHideBestSolutionsLinkTarget($this->ctrl->getLinkTarget($this, $this->ctrl->getCmd()));
                $this->ctrl->setParameter($this, 'hide_best_solutions', '');
            } else {
                $this->ctrl->setParameter($this, 'show_best_solutions', '1');
                $toolbar->setShowBestSolutionsLinkTarget($this->ctrl->getLinkTarget($this, $this->ctrl->getCmd()));
                $this->ctrl->setParameterByClass('', 'show_best_solutions', '');
            }
        }

        $participant_data = new ilTestParticipantData($this->db, $this->lng);
        $participant_data->setParticipantAccessFilter(
            $this->participant_access_filter_factory->getAccessResultsUserFilter($this->getTestObj()->getRefId())
        );

        $participant_data->setActiveIdsFilter($show_user_results);

        $participant_data->load($this->getTestObj()->getTestId());
        $toolbar->setParticipantSelectorOptions($participant_data->getOptionArray());

        $toolbar->build();
        $template->setVariable('RESULTS_TOOLBAR', $toolbar->getHTML());

        $service_gui = new ilTestServiceGUI($this->getTestObj());
        $service_gui->setObjectiveOrientedContainer($this->getObjectiveParent());
        $service_gui->setParticipantData($participant_data);

        $testSessionFactory = new ilTestSessionFactory($this->getTestObj(), $this->db, $this->user);

        $count = 0;
        foreach ($show_user_results as $key => $active_id) {
            if (!in_array($active_id, $participant_data->getActiveIds())) {
                continue;
            }

            $count++;
            $results = "";
            if ($active_id > 0) {
                $results = $service_gui->getResultsOfUserOutput(
                    $testSessionFactory->getSession((int) $active_id),
                    (int) $active_id,
                    ilObjTest::_getResultPass((int) $active_id),
                    $this,
                    $show_pass_details,
                    $show_answers,
                    false,
                    $show_reached_points
                );
            }
            if ($count < count($show_user_results)) {
                $template->touchBlock("break");
            }
            $template->setCurrentBlock("user_result");
            $template->setVariable("USER_RESULT", $results);
            $template->parseCurrentBlock();
        }

        return $template;
    }

    // fau: sendSimpleResults - new function confirmSendSimpleResultsToParticipantsCmd()
    /**
     * Show confirmation screen to send the results messages to the participants as e-mail
     */
    public function sendSimpleResultsToParticipantsCmd()
    {
        global $DIC;
        
        if (!isset($_POST["chbUser"]) || !is_array($_POST["chbUser"]) || !count($_POST["chbUser"])) {
            $this->main_tpl->setOnScreenMessage('info',    $DIC->language()->txt("select_one_user"), true);
            $DIC->ctrl()->redirect($this, self::CMD_SHOW_PARTICIPANTS);
        }
        
        $cgui = new ilConfirmationGUI();
        $cgui->setFormAction($DIC->ctrl()->getFormAction($this));
        $cgui->setHeaderText($DIC->language()->txt('send_simple_results_to_participants_confirm'));
        $cgui->setCancel($DIC->language()->txt('cancel'), self::CMD_SHOW_PARTICIPANTS);
        $cgui->setConfirm($DIC->language()->txt('confirm'), 'confirmedSendSimpleResultsToParticipants');
        
        $accessFilter = $this->participant_access_filter_factory->getManageParticipantsUserFilter($this->getTestObj()->getRefId());
        $participantData = new ilTestParticipantData($DIC->database(), $DIC->language());
        $participantData->setParticipantAccessFilter($accessFilter);
        $participantData->setActiveIdsFilter((array) $_POST["chbUser"]);
        $participantData->load($this->getTestObj()->getTestId());
        
        foreach ($participantData->getActiveIds() as $activeId) {
            $cgui->addItem(
                "chbUser[]",
                (string) $activeId,
                $participantData->getFormatedFullnameByActiveId($activeId),
                ilUtil::getImagePath("icon_usr.svg"),
                $DIC->language()->txt("usr")
            );
        }
        
        $DIC->ui()->mainTemplate()->setContent($cgui->getHTML());
    }
    // fau.

    // fau: sendSimpleResults - new function confirmedSendSimpleResultsToParticipantsCmd()
    /**
     * Actually send the result messages to the participants (confirmation is done)
     * The e-mails are not sent directly but distributes via SOAP function of a confighred installation
     * This allows to send the e-mails from the exam instance and distribute them in the lms instance
     * Students will get the e-mail in ilias if haven't configured a forwarding
     */
    public function confirmedSendSimpleResultsToParticipantsCmd()
    {
        global $DIC;
        
        if (!isset($_POST["chbUser"]) || !is_array($_POST["chbUser"]) || !count($_POST["chbUser"])) {
            $this->main_tpl->setOnScreenMessage('info',$DIC->language()->txt("select_one_user"), true);
            $DIC->ctrl()->redirect($this, self::CMD_SHOW_PARTICIPANTS);
        }
        
        // init remote notification
        if (ilCust::get('tst_notify_remote')) {
            include_once 'Services/WebServices/SOAP/classes/class.ilRemoteIliasClient.php';
            $soap_client = ilRemoteIliasClient::_getInstance();
            if (!$soap_sid = $soap_client->login()) {
                $this->main_tpl->setOnScreenMessage('info',$DIC->language()->txt("ilias_remote_soap_login_failed"), true);
                $DIC->ctrl()->redirect($this, self::CMD_SHOW_PARTICIPANTS);
            }
        }
        
        $accessFilter = $this->participant_access_filter_factory->getManageParticipantsUserFilter($this->getTestObj()->getRefId());
        $participantData = new ilTestParticipantData($DIC->database(), $DIC->language());
        $participantData->setParticipantAccessFilter($accessFilter);
        $participantData->setActiveIdsFilter((array) $_POST["chbUser"]);
        $participantData->load($this->getTestObj()->getTestId());
        
        $sent = array();
        $failed = array();
        foreach ($participantData->getActiveIds() as $active_id) {
            $user_id = $participantData->getUserIdByActiveId($active_id);
            $uname = $participantData->getFormatedFullnameByActiveId($active_id);
            $user = new ilObjUser($user_id);
            
            $gradingMessageBuilder = new ilTestGradingMessageBuilder($DIC->language(), $this->main_tpl, $this->getTestObj());
            $gradingMessageBuilder->setActiveId($active_id);
            $gradingMessageBuilder->buildMessage();
            $message = $gradingMessageBuilder->buildGradingMarkMsg();
            
            // create a new factory because the session is a singleton in it
            $factory = new ilTestSessionFactory($this->getTestObj(), $this->db, $this->user);
            $session = $factory->getSession($active_id);
            $timestamp = $session->getSubmittedTimestamp();
            if (!$timestamp) {
                $timestamp = $this->getTestObj()->_getLastAccess($active_id);
            }
            $time = strftime("%d.%m.%Y %H:%M", strtotime($timestamp));
            
            $subject = sprintf(
                str_replace('\n', "\n", $DIC->language()->txt('send_simple_results_subject')),
                $this->getTestObj()->getTitle(),
                $time
            );
            
            // make breaks to newlines
            $message = str_replace('<br />', "\n", $message);
            $message = preg_replace('/<a.*href="([^"]*)".*>(.*)<\/a>/', '$2 ($1)', $message);
            $message = strip_tags($message);
            $message = htmlspecialchars_decode($message, ENT_QUOTES);
            
            $body = sprintf(
                str_replace('\n', "\n", $DIC->language()->txt('send_simple_results_body')),
                $message,
                $uname,
                $user->getMatriculation(),
                $time
            );
            
            if (ilCust::get('tst_notify_remote')) {
                // send as mail in remote platform
                
                $success = $soap_client->call('sendUserMail', array(
                    $soap_sid,				// session id
                    $user->getLogin(),      // to
                    "",                     // cc
                    "",                     // bcc
                    "anonymous",    		// sender
                    $subject,               // subject
                    $body,                  // message
                    "",                     // attachments (imploded with ',')
                    "system",               // type (imploded with ',')
                    0                       // use placholders
                ));
                
                if (!$success) {
                    if ($user->getEmail()) {
                        $success = $soap_client->call('sendUserMail', array(
                            $soap_sid,				// session id
                            $user->getEmail(),      // to
                            "",                     // cc
                            "",                     // bcc
                            "anonymous",    		// sender
                            $subject,               // subject
                            $body,                  // message
                            "",                     // attachments (imploded with ',')
                            "system",               // type (imploded with ',')
                            0                       // use placholders
                        ));
                    }
                }
                
                if ($success) {
                    $sent[] = $uname;
                } else {
                    $failed[] = $uname;
                }
            } else {
                // send as mail in local platform
                $mail = new ilMail(ANONYMOUS_USER_ID);
                $mail_data = new MailDeliveryData(
                    $user->getLogin(),		// to
                    "", 					// cc
                    "", 					// bcc
                    $subject, 				// subject
                    $body, 				    // message
                    array(),				// attachments 
                    //explode(',', $type), 	// type
                    false                   // use placeholders
                );
                
                if ($error) {
                    $failed[] = $uname;
                } else {
                    $sent[] = $uname;
                }
            }
        }
        
        // close remote notification
        if (ilCust::get('tst_notify_remote')) {
            $soap_client->logout();
        }
        
        if (count($failed)) {
            $this->main_tpl->setOnScreenMessage('failure', sprintf(
                $DIC->language()->txt("send_simple_results_to_participants_failed"),
                implode(', ', $failed)
            ), true);
        }
        if (count($sent)) {
            $this->main_tpl->setOnScreenMessage('failure', sprintf(
                $DIC->language()->txt("send_simple_results_to_participants_success"),
                implode(', ', $sent)
            ), true);
        }
        
        $DIC->ctrl()->redirect($this, self::CMD_SHOW_PARTICIPANTS);
    }
    // fau.
        
}
