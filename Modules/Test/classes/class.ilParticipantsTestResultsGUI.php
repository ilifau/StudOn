<?php

/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

use ILIAS\DI\Container;

/**
 * Class ilParticipantsTestResultsGUI
 *
 * @author    Björn Heyser <info@bjoernheyser.de>
 * @version    $Id$
 *
 * @package    Modules/Test
 *
 * @ilCtrl_Calls ilParticipantsTestResultsGUI: ilTestEvaluationGUI
 * @ilCtrl_Calls ilParticipantsTestResultsGUI: ilAssQuestionPageGUI
 * @ilCtrl_Calls ilParticipantsTestResultsGUI: ilAssSpecFeedbackPageGUI
 * @ilCtrl_Calls ilParticipantsTestResultsGUI: ilAssGenFeedbackPageGUI
 */
class ilParticipantsTestResultsGUI
{
    const CMD_SHOW_PARTICIPANTS = 'showParticipants';
    const CMD_CONFIRM_DELETE_ALL_USER_RESULTS = 'deleteAllUserResults';
    const CMD_PERFORM_DELETE_ALL_USER_RESULTS = 'confirmDeleteAllUserResults';
    const CMD_CONFIRM_DELETE_SELECTED_USER_RESULTS = 'deleteSingleUserResults';
    const CMD_PERFORM_DELETE_SELECTED_USER_RESULTS = 'confirmDeleteSelectedUserData';
    
    /**
     * @var ilObjTest
     */
    protected $testObj;
    
    /**
     * @var ilTestQuestionSetConfig
     */
    protected $questionSetConfig;
    
    /**
     * @var ilTestAccess
     */
    protected $testAccess;
    
    /**
     * @var ilTestObjectiveOrientedContainer
     */
    protected $objectiveParent;
    
    /**
     * @return ilObjTest
     */
    public function getTestObj()
    {
        return $this->testObj;
    }
    
    /**
     * @param ilObjTest $testObj
     */
    public function setTestObj($testObj)
    {
        $this->testObj = $testObj;
    }
    
    /**
     * @return ilTestQuestionSetConfig
     */
    public function getQuestionSetConfig()
    {
        return $this->questionSetConfig;
    }
    
    /**
     * @param ilTestQuestionSetConfig $questionSetConfig
     */
    public function setQuestionSetConfig($questionSetConfig)
    {
        $this->questionSetConfig = $questionSetConfig;
    }
    
    /**
     * @return ilTestAccess
     */
    public function getTestAccess()
    {
        return $this->testAccess;
    }
    
    /**
     * @param ilTestAccess $testAccess
     */
    public function setTestAccess($testAccess)
    {
        $this->testAccess = $testAccess;
    }
    
    /**
     * @return ilTestObjectiveOrientedContainer
     */
    public function getObjectiveParent()
    {
        return $this->objectiveParent;
    }
    
    /**
     * @param ilTestObjectiveOrientedContainer $objectiveParent
     */
    public function setObjectiveParent($objectiveParent)
    {
        $this->objectiveParent = $objectiveParent;
    }
    
    /**
     * Execute Command
     */
    public function executeCommand()
    {
        global $DIC; /* @var ILIAS\DI\Container $DIC */
        
        switch ($DIC->ctrl()->getNextClass($this)) {
            case "iltestevaluationgui":
                require_once 'Modules/Test/classes/class.ilTestEvaluationGUI.php';
                $gui = new ilTestEvaluationGUI($this->getTestObj());
                $gui->setObjectiveOrientedContainer($this->getObjectiveParent());
                $gui->setTestAccess($this->getTestAccess());
                $DIC->tabs()->clearTargets();
                $DIC->tabs()->clearSubTabs();
                $DIC->ctrl()->forwardCommand($gui);
                break;
                
            case 'ilassquestionpagegui':
                require_once 'Modules/Test/classes/class.ilAssQuestionPageCommandForwarder.php';
                $forwarder = new ilAssQuestionPageCommandForwarder();
                $forwarder->setTestObj($this->getTestObj());
                $forwarder->forward();
                break;
            
            default:
                
                $command = $DIC->ctrl()->getCmd(self::CMD_SHOW_PARTICIPANTS) . 'Cmd';
                $this->{$command}();
        }
    }
    
    /**
     * @return ilParticipantsTestResultsTableGUI
     */
    protected function buildTableGUI()
    {
        global $DIC; /* @var ILIAS\DI\Container $DIC */
        require_once 'Modules/Test/classes/tables/class.ilParticipantsTestResultsTableGUI.php';
        $tableGUI = new ilParticipantsTestResultsTableGUI($this, self::CMD_SHOW_PARTICIPANTS);
        $tableGUI->setTitle($DIC->language()->txt('tst_tbl_results_grades'));
        return $tableGUI;
    }
    
    /**
     * show participants command
     */
    protected function showParticipantsCmd()
    {
        global $DIC; /* @var ILIAS\DI\Container $DIC */
        
        if ($this->getQuestionSetConfig()->areDepenciesBroken()) {
            ilUtil::sendFailure(
                $this->getQuestionSetConfig()->getDepenciesBrokenMessage($DIC->language())
            );
        } elseif ($this->getQuestionSetConfig()->areDepenciesInVulnerableState()) {
            ilUtil::sendInfo(
                $this->questionSetConfig->getDepenciesInVulnerableStateMessage($DIC->language())
            );
        }
        
        $manageParticipantFilter = ilTestParticipantAccessFilter::getManageParticipantsUserFilter($this->getTestObj()->getRefId());
        $accessResultsFilter = ilTestParticipantAccessFilter::getAccessResultsUserFilter($this->getTestObj()->getRefId());
        
        $participantList = $this->getTestObj()->getActiveParticipantList();
        $participantList = $participantList->getAccessFilteredList($manageParticipantFilter);
        $participantList = $participantList->getAccessFilteredList($accessResultsFilter);
        
        $scoredParticipantList = $participantList->getScoredParticipantList();
        
        require_once 'Modules/Test/classes/tables/class.ilTestParticipantsTableGUI.php';
        $tableGUI = $this->buildTableGUI();

        if (!$this->getQuestionSetConfig()->areDepenciesBroken()) {
            $tableGUI->setAccessResultsCommandsEnabled(
                $this->getTestAccess()->checkParticipantsResultsAccess()
            );
            
            $tableGUI->setManageResultsCommandsEnabled(
                $this->getTestAccess()->checkManageParticipantsAccess()
            );
            
            if ($scoredParticipantList->hasScorings()) {
                $this->addDeleteAllTestResultsButton($DIC->toolbar());
                // fau: provideRecalc - add the button to recalc all test results
                $this->addRecalcAllTestResultsButton($DIC->toolbar());
                // fau.
            }
        }
        
        $tableGUI->setAnonymity($this->getTestObj()->getAnonymity());
        
        $tableGUI->initColumns();
        $tableGUI->initCommands();
        
        $tableGUI->setData($participantList->getScoringsTableRows());
        
        $DIC->ui()->mainTemplate()->setContent($tableGUI->getHTML());
    }

    // fau: provideRecalc - new functions to recalculate test results
    /**
     * @param ilToolbarGUI $toolbar
     */
    protected function addRecalcAllTestResultsButton(ilToolbarGUI $toolbar)
    {
        global $DIC; /* @var ILIAS\DI\Container $DIC */

        $delete_all_results_btn = ilLinkButton::getInstance();
        $delete_all_results_btn->setCaption('tst_recalculate_solutions');
        $delete_all_results_btn->setUrl($DIC->ctrl()->getLinkTarget($this, 'recalcAllTestResults'));
        $toolbar->addButtonInstance($delete_all_results_btn);
    }

    /**
     * Confirm the scoring recalculation of all participants
     */
    public function recalcAllTestResultsCmd()
    {
        global $DIC; /* @var ILIAS\DI\Container $DIC */

        $cgui = new ilConfirmationGUI();
        $cgui->setFormAction($DIC->ctrl()->getFormAction($this));
        $cgui->setHeaderText($DIC->language()->txt('tst_recalculate_solutions_confirm'));
        $cgui->setConfirm($DIC->language()->txt('tst_recalculate_solutions'), 'confirmedRecalcAllTestResults');
        $cgui->setCancel($DIC->language()->txt('cancel'), self::CMD_SHOW_PARTICIPANTS);
        $DIC->ui()->mainTemplate()->setContent($cgui->getHTML());
    }

    /**
     * Recalculate the scoring of all participants
     */
    public function confirmedRecalcAllTestResultsCmd()
    {
        global $DIC; /* @var ILIAS\DI\Container $DIC */

        $scorer = new ilTestScoring($this->getTestObj());
        $scorer->setPreserveManualScores(true);
        $scorer->recalculateSolutions();

        ilUtil::sendSuccess($DIC->language()->txt('tst_recalculated_solutions'), true);
        $DIC->ctrl()->redirect($this, self::CMD_SHOW_PARTICIPANTS);
    }

    /**
     * Recalculate the scoring of selected participants
     */
    public function recalcSelectedTestResultsCmd()
    {
        global $DIC; /* @var ILIAS\DI\Container $DIC */


        if (!is_array($_POST["chbUser"]) || count($_POST["chbUser"]) == 0) {
            ilUtil::sendInfo($DIC->language()->txt("select_one_user"), true);
            $DIC->ctrl()->redirect($this);
        }

        $cgui = new ilConfirmationGUI();
        $cgui->setFormAction($DIC->ctrl()->getFormAction($this));
        $cgui->setHeaderText($DIC->language()->txt('tst_recalculate_selected_solutions_confirm'));
        $cgui->setConfirm($DIC->language()->txt('tst_recalculate_solutions'), 'confirmedRecalcSelectedTestResults');
        $cgui->setCancel($DIC->language()->txt('cancel'), self::CMD_SHOW_PARTICIPANTS);

        $accessFilter = ilTestParticipantAccessFilter::getManageParticipantsUserFilter($this->getTestObj()->getRefId());
        $participantData = new ilTestParticipantData($DIC->database(), $DIC->language());
        $participantData->setParticipantAccessFilter($accessFilter);
        $participantData->setActiveIdsFilter((array) $_POST["chbUser"]);
        $participantData->load($this->getTestObj()->getTestId());

        foreach ($participantData->getActiveIds() as $activeId) {
            $cgui->addItem(
                "chbUser[]",
                $activeId,
                $participantData->getFormatedFullnameByActiveId($activeId),
                ilUtil::getImagePath("icon_usr.svg"),
                $DIC->language()->txt("usr")
            );
        }

        $DIC->ui()->mainTemplate()->setContent($cgui->getHTML());
    }


    /**
     * Recalculate the scoring of all participants
     */
    public function confirmedRecalcSelectedTestResultsCmd()
    {
        global $DIC; /* @var ILIAS\DI\Container $DIC */

        $scorer = new ilTestScoring($this->getTestObj());
        $scorer->setPreserveManualScores(true);
        $scorer->recalculateSolutions($_POST["chbUser"]);

        ilUtil::sendSuccess($DIC->language()->txt('tst_recalculated_solutions'), true);
        $DIC->ctrl()->redirect($this, self::CMD_SHOW_PARTICIPANTS);
    }
    // fau.


    /**
     * @param ilToolbarGUI $toolbar
     */
    protected function addDeleteAllTestResultsButton(ilToolbarGUI $toolbar)
    {
        global $DIC; /* @var ILIAS\DI\Container $DIC */
        
        require_once  'Services/UIComponent/Button/classes/class.ilLinkButton.php';
        $delete_all_results_btn = ilLinkButton::getInstance();
        $delete_all_results_btn->setCaption('delete_all_user_data');
        $delete_all_results_btn->setUrl($DIC->ctrl()->getLinkTarget($this, 'deleteAllUserResults'));
        $toolbar->addButtonInstance($delete_all_results_btn);
    }
    
    /**
     * Asks for a confirmation to delete all user data of the test object
     */
    protected function deleteAllUserResultsCmd()
    {
        global $DIC; /* @var ILIAS\DI\Container $DIC */
        
        // display confirmation message
        include_once("./Services/Utilities/classes/class.ilConfirmationGUI.php");
        $cgui = new ilConfirmationGUI();
        $cgui->setFormAction($DIC->ctrl()->getFormAction($this));
        $cgui->setHeaderText($DIC->language()->txt("delete_all_user_data_confirmation"));
        $cgui->setCancel($DIC->language()->txt("cancel"), self::CMD_SHOW_PARTICIPANTS);
        $cgui->setConfirm($DIC->language()->txt("proceed"), self::CMD_PERFORM_DELETE_ALL_USER_RESULTS);
        
        $DIC->ui()->mainTemplate()->setContent($cgui->getHTML());
    }
    
    /**
     * Deletes all user data for the test object
     */
    protected function confirmDeleteAllUserResultsCmd()
    {
        global $DIC; /* @var ILIAS\DI\Container $DIC */
        
        require_once 'Modules/Test/classes/class.ilTestParticipantAccessFilter.php';
        $accessFilter = ilTestParticipantAccessFilter::getManageParticipantsUserFilter(
            $this->getTestObj()->getRefId()
        );
        
        require_once 'Modules/Test/classes/class.ilTestParticipantData.php';
        $participantData = new ilTestParticipantData($DIC->database(), $DIC->language());
        //$participantData->setScoredParticipantsFilterEnabled(!$this->getTestObj()->isDynamicTest());
        $participantData->setParticipantAccessFilter($accessFilter);
        $participantData->load($this->getTestObj()->getTestId());
        
        $this->getTestObj()->removeTestResults($participantData);
        
        ilUtil::sendSuccess($DIC->language()->txt("tst_all_user_data_deleted"), true);
        $DIC->ctrl()->redirect($this, self::CMD_SHOW_PARTICIPANTS);
    }
    
    /**
     * Asks for a confirmation to delete selected user data of the test object
     */
    protected function deleteSingleUserResultsCmd()
    {
        global $DIC; /* @var ILIAS\DI\Container $DIC */
        
        if (!is_array($_POST["chbUser"]) || count($_POST["chbUser"]) == 0) {
            ilUtil::sendInfo($DIC->language()->txt("select_one_user"), true);
            $DIC->ctrl()->redirect($this);
        }
        
        include_once("./Services/Utilities/classes/class.ilConfirmationGUI.php");
        $cgui = new ilConfirmationGUI();
        $cgui->setHeaderText($DIC->language()->txt("confirm_delete_single_user_data"));
        
        $cgui->setFormAction($DIC->ctrl()->getFormAction($this));
        $cgui->setCancel($DIC->language()->txt("cancel"), self::CMD_SHOW_PARTICIPANTS);
        $cgui->setConfirm($DIC->language()->txt("confirm"), self::CMD_PERFORM_DELETE_SELECTED_USER_RESULTS);
        
        require_once 'Modules/Test/classes/class.ilTestParticipantAccessFilter.php';
        $accessFilter = ilTestParticipantAccessFilter::getManageParticipantsUserFilter($this->getTestObj()->getRefId());
        
        require_once 'Modules/Test/classes/class.ilTestParticipantData.php';
        $participantData = new ilTestParticipantData($DIC->database(), $DIC->language());
        //$participantData->setScoredParticipantsFilterEnabled(!$this->getTestObj()->isDynamicTest());
        $participantData->setParticipantAccessFilter($accessFilter);
        
        $participantData->setActiveIdsFilter((array) $_POST["chbUser"]);
        
        $participantData->load($this->getTestObj()->getTestId());
        
        foreach ($participantData->getActiveIds() as $activeId) {
            if ($this->testObj->getAnonymity()) {
                $username = $DIC->language()->txt('anonymous');
            } else {
                $username = $participantData->getFormatedFullnameByActiveId($activeId);
            }
            
            $cgui->addItem(
                "chbUser[]",
                $activeId,
                $username,
                ilUtil::getImagePath("icon_usr.svg"),
                $DIC->language()->txt("usr")
            );
        }
        
        $DIC->ui()->mainTemplate()->setContent($cgui->getHTML());
    }
    
    /**
     * Deletes the selected user data for the test object
     */
    protected function confirmDeleteSelectedUserDataCmd()
    {
        global $DIC; /* @var ILIAS\DI\Container $DIC */
        
        if (isset($_POST["chbUser"]) && is_array($_POST["chbUser"]) && count($_POST["chbUser"])) {
            require_once 'Modules/Test/classes/class.ilTestParticipantAccessFilter.php';
            $accessFilter = ilTestParticipantAccessFilter::getManageParticipantsUserFilter($this->getTestObj()->getRefId());
            
            require_once 'Modules/Test/classes/class.ilTestParticipantData.php';
            $participantData = new ilTestParticipantData($DIC->database(), $DIC->language());
            //$participantData->setScoredParticipantsFilterEnabled(!$this->getTestObj()->isDynamicTest());
            $participantData->setParticipantAccessFilter($accessFilter);
            $participantData->setActiveIdsFilter($_POST["chbUser"]);
            
            $participantData->load($this->getTestObj()->getTestId());
            
            $this->getTestObj()->removeTestResults($participantData);
            
            ilUtil::sendSuccess($DIC->language()->txt("tst_selected_user_data_deleted"), true);
        }
        
        $DIC->ctrl()->redirect($this, self::CMD_SHOW_PARTICIPANTS);
    }
    
    // fau: sendSimpleResults - new function confirmSendSimpleResultsToParticipantsCmd()
    /**
     * Show confirmation screen to send the results messages to the participants as e-mail
     */
    public function sendSimpleResultsToParticipantsCmd()
    {
        global $DIC;
        
        if (!isset($_POST["chbUser"]) || !is_array($_POST["chbUser"]) || !count($_POST["chbUser"])) {
            ilUtil::sendInfo($DIC->language()->txt("select_one_user"), true);
            $DIC->ctrl()->redirect($this, self::CMD_SHOW_PARTICIPANTS);
        }
        
        $cgui = new ilConfirmationGUI();
        $cgui->setFormAction($DIC->ctrl()->getFormAction($this));
        $cgui->setHeaderText($DIC->language()->txt('send_simple_results_to_participants_confirm'));
        $cgui->setCancel($DIC->language()->txt('cancel'), self::CMD_SHOW_PARTICIPANTS);
        $cgui->setConfirm($DIC->language()->txt('confirm'), 'confirmedSendSimpleResultsToParticipants');
        
        $accessFilter = ilTestParticipantAccessFilter::getManageParticipantsUserFilter($this->getTestObj()->getRefId());
        $participantData = new ilTestParticipantData($DIC->database(), $DIC->language());
        $participantData->setParticipantAccessFilter($accessFilter);
        $participantData->setActiveIdsFilter((array) $_POST["chbUser"]);
        $participantData->load($this->getTestObj()->getTestId());
        
        foreach ($participantData->getActiveIds() as $activeId) {
            $cgui->addItem(
                "chbUser[]",
                $activeId,
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
            ilUtil::sendInfo($DIC->language()->txt("select_one_user"), true);
            $DIC->ctrl()->redirect($this, self::CMD_SHOW_PARTICIPANTS);
        }
        
        // init remote notification
        if (ilCust::get('tst_notify_remote')) {
            include_once 'Services/WebServices/SOAP/classes/class.ilRemoteIliasClient.php';
            $soap_client = ilRemoteIliasClient::_getInstance();
            if (!$soap_sid = $soap_client->login()) {
                ilUtil::sendInfo($DIC->language()->txt("ilias_remote_soap_login_failed"), true);
                $DIC->ctrl()->redirect($this, self::CMD_SHOW_PARTICIPANTS);
            }
        }
        
        $accessFilter = ilTestParticipantAccessFilter::getManageParticipantsUserFilter($this->getTestObj()->getRefId());
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
            
            $gradingMessageBuilder = new ilTestGradingMessageBuilder($DIC->language(), $this->getTestObj());
            $gradingMessageBuilder->setActiveId($active_id);
            $gradingMessageBuilder->buildMessage();
            $message = $gradingMessageBuilder->buildGradingMarkMsg();
            
            // create a new factory because the session is a singleton in it
            $factory = new ilTestSessionFactory($this->getTestObj());
            $session = $factory->getSession($active_id);
            $timestamp = $session->getSubmittedTimestamp();
            if (!$timestamp) {
                $timestamp = $this->getTestObj()->_getLastAccess($active_id);
            }
            $time = strftime("%d.%m.%Y %H:%M", ilUtil::date_mysql2time($timestamp));
            
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
                $error = $mail->sendMail(
                    $user->getLogin(), 		// to
                    "", 					// cc
                    "", 					// bcc
                    $subject, 				// subject
                    $body, 					// message
                    array(), 				// attachments
                    0                       // use placholders
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
            ilUtil::sendFailure(sprintf(
                $DIC->language()->txt("send_simple_results_to_participants_failed"),
                implode(', ', $failed)
            ), true);
        }
        if (count($sent)) {
            ilUtil::sendSuccess(sprintf(
                $DIC->language()->txt("send_simple_results_to_participants_success"),
                implode(', ', $sent)
            ), true);
        }
        
        $DIC->ctrl()->redirect($this, self::CMD_SHOW_PARTICIPANTS);
    }
    // fau.
    
    
    /**
     * Shows the pass overview and the answers of one ore more users for the scored pass
     */
    protected function showDetailedResultsCmd()
    {
        if (is_array($_POST) && count($_POST)) {
            $_SESSION["show_user_results"] = $_POST["chbUser"];
        }
        $this->showUserResults($show_pass_details = true, $show_answers = true, $show_reached_points = true);
    }
    
    /**
     * Shows the answers of one ore more users for the scored pass
     */
    protected function showUserAnswersCmd()
    {
        if (is_array($_POST) && count($_POST)) {
            $_SESSION["show_user_results"] = $_POST["chbUser"];
        }
        $this->showUserResults($show_pass_details = false, $show_answers = true);
    }
    
    /**
     * Shows the pass overview of the scored pass for one ore more users
     */
    protected function showPassOverviewCmd()
    {
        if (is_array($_POST) && count($_POST)) {
            $_SESSION["show_user_results"] = $_POST["chbUser"];
        }
        $this->showUserResults($show_pass_details = true, $show_answers = false);
    }
    
    /**
     * Shows the pass overview of the scored pass for one ore more users
     *
     * @access	public
     */
    protected function showUserResults($show_pass_details, $show_answers, $show_reached_points = false)
    {
        global $DIC; /* @var ILIAS\DI\Container $DIC */
        
        $DIC->tabs()->clearTargets();
        $DIC->tabs()->clearSubTabs();
        
        $show_user_results = $_SESSION["show_user_results"];
        
        if (!is_array($show_user_results) || count($show_user_results) == 0) {
            ilUtil::sendInfo($DIC->language()->txt("select_one_user"), true);
            $DIC->ctrl()->redirect($this, self::CMD_SHOW_PARTICIPANTS);
        }
        
        
        $template = $this->createUserResults($show_pass_details, $show_answers, $show_reached_points, $show_user_results);
        
        if ($template instanceof ilTemplate) {
            $DIC->ui()->mainTemplate()->setVariable("ADM_CONTENT", $template->get());
            $DIC->ui()->mainTemplate()->addCss(ilUtil::getStyleSheetLocation("output", "test_print.css", "Modules/Test"), "print");
            if ($this->getTestObj()->getShowSolutionAnswersOnly()) {
                $DIC->ui()->mainTemplate()->addCss(ilUtil::getStyleSheetLocation("output", "test_print_hide_content.css", "Modules/Test"), "print");
            }
        }
    }
    
    /**
     * @param $show_pass_details
     * @param $show_answers
     * @param $show_reached_points
     * @param $show_user_results
     *
     * @return ilTemplate
     */
    public function createUserResults($show_pass_details, $show_answers, $show_reached_points, $show_user_results)
    {
        global $DIC; /* @var ILIAS\DI\Container $DIC */
        
        // prepare generation before contents are processed (needed for mathjax)
        if ($this->isPdfDeliveryRequest()) {
            ilPDFGeneratorUtils::prepareGenerationRequest("Test", PDF_USER_RESULT);
        }
        
        $DIC->tabs()->setBackTarget(
            $DIC->language()->txt('back'),
            $DIC->ctrl()->getLinkTarget($this, self::CMD_SHOW_PARTICIPANTS)
        );
        
        if ($this->getObjectiveParent()->isObjectiveOrientedPresentationRequired()) {
            require_once 'Services/Link/classes/class.ilLink.php';
            $courseLink = ilLink::_getLink($this->getObjectiveParent()->getRefId());
            $DIC->tabs()->setBack2Target($DIC->language()->txt('back_to_objective_container'), $courseLink);
        }
        
        $template = new ilTemplate("tpl.il_as_tst_participants_result_output.html", true, true, "Modules/Test");
        
        require_once 'Modules/Test/classes/toolbars/class.ilTestResultsToolbarGUI.php';
        $toolbar = new ilTestResultsToolbarGUI($DIC->ctrl(), $DIC->ui()->mainTemplate(), $DIC->language());
        
        $DIC->ctrl()->setParameter($this, 'pdf', '1');
        $toolbar->setPdfExportLinkTarget($DIC->ctrl()->getLinkTarget($this, $DIC->ctrl()->getCmd()));
        $DIC->ctrl()->setParameter($this, 'pdf', '');
        
        if ($show_answers) {
            if (isset($_GET['show_best_solutions'])) {
                $_SESSION['tst_results_show_best_solutions'] = true;
            } elseif (isset($_GET['hide_best_solutions'])) {
                $_SESSION['tst_results_show_best_solutions'] = false;
            } elseif (!isset($_SESSION['tst_results_show_best_solutions'])) {
                $_SESSION['tst_results_show_best_solutions'] = false;
            }
            
            if ($_SESSION['tst_results_show_best_solutions']) {
                $DIC->ctrl()->setParameter($this, 'hide_best_solutions', '1');
                $toolbar->setHideBestSolutionsLinkTarget($DIC->ctrl()->getLinkTarget($this, $DIC->ctrl()->getCmd()));
                $DIC->ctrl()->setParameter($this, 'hide_best_solutions', '');
            } else {
                $DIC->ctrl()->setParameter($this, 'show_best_solutions', '1');
                $toolbar->setShowBestSolutionsLinkTarget($DIC->ctrl()->getLinkTarget($this, $DIC->ctrl()->getCmd()));
                $DIC->ctrl()->setParameterByClass('', 'show_best_solutions', '');
            }
        }
        
        require_once 'Modules/Test/classes/class.ilTestParticipantData.php';
        require_once 'Modules/Test/classes/class.ilTestParticipantAccessFilter.php';
        
        $participantData = new ilTestParticipantData($DIC->database(), $DIC->language());
        
        $participantData->setParticipantAccessFilter(
            ilTestParticipantAccessFilter::getAccessResultsUserFilter($this->getTestObj()->getRefId())
        );
        
        $participantData->setActiveIdsFilter($show_user_results);
        
        $participantData->load($this->getTestObj()->getTestId());
        $toolbar->setParticipantSelectorOptions($participantData->getOptionArray());
        
        $toolbar->build();
        $template->setVariable('RESULTS_TOOLBAR', $toolbar->getHTML());
        
        include_once "./Modules/Test/classes/class.ilTestServiceGUI.php";
        $serviceGUI = new ilTestServiceGUI($this->getTestObj());
        $serviceGUI->setObjectiveOrientedContainer($this->getObjectiveParent());
        $serviceGUI->setParticipantData($participantData);
        
        require_once 'Modules/Test/classes/class.ilTestSessionFactory.php';
        $testSessionFactory = new ilTestSessionFactory($this->getTestObj());
        
        $count = 0;
        foreach ($show_user_results as $key => $active_id) {
            if (!in_array($active_id, $participantData->getActiveIds())) {
                continue;
            }
            
            $count++;
            $results = "";
            if ($active_id > 0) {
                $results = $serviceGUI->getResultsOfUserOutput(
                    $testSessionFactory->getSession($active_id),
                    $active_id,
                    $this->getTestObj()->_getResultPass($active_id),
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
        
        if ($this->isPdfDeliveryRequest()) {
            ilTestPDFGenerator::generatePDF(
                $template->get(),
                ilTestPDFGenerator::PDF_OUTPUT_DOWNLOAD,
                $this->getTestObj()->getTitleFilenameCompliant(),
                PDF_USER_RESULT
            );
        } else {
            return $template;
        }
    }
    
    /**
     * @return bool
     */
    protected function isPdfDeliveryRequest()
    {
        if (!isset($_GET['pdf'])) {
            return false;
        }
        
        if (!(bool) $_GET['pdf']) {
            return false;
        }
        
        return true;
    }
}
