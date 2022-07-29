<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

// fau: paraSub - import of registration class
use FAU\Ilias\Registration;
// fau.

include_once('./Services/Membership/classes/class.ilRegistrationGUI.php');

/**
* GUI class for course registrations
*
* @author Stefan Meyer <smeyer.ilias@gmx.de>
* @version $Id$
*
* @ingroup ModulesCourse
*
* @ilCtrl_Calls ilCourseRegistrationGUI:
*/
class ilCourseRegistrationGUI extends ilRegistrationGUI
{
    // fau: fairSub - added type hints
    /** @var ilRegistrationGUI $parent_gui */
    private $parent_gui = null;

    /** @var ilObjCourse $container */
    protected $container = null;

    /** @var  ilCourseParticipants  $participants*/
    protected $participants;

    /** @var int $subscription_type */
    protected $subscription_type;
    // fau.

    /**
     * Constructor
     *
     * @access public
     * @param object course object
     */
    public function __construct($a_container, $a_parent_gui)
    {
        parent::__construct($a_container);
        
        $this->parent_gui = $a_parent_gui;

        // fau: studyCond - set the actual subscription type according to the studydata condition
        if ($this->matches_studycond || $this->container->getActivationUnlimitedStatus() == IL_CRS_SUBSCRIPTION_DEACTIVATED) {
            $this->subscription_type = $this->container->getSubscriptionType();
        } else {
            $this->subscription_type = IL_CRS_SUBSCRIPTION_CONFIRMATION;
            $this->registration->setSubType(Registration::subConfirmation);
        }
        // fau.
    }
    
    /**
     * Execute command
     *
     * @access public
     */
    public function executeCommand()
    {
        global $DIC;
        
        $ilTabs = $DIC['ilTabs'];
        $ilUser = $DIC['ilUser'];
        
        
        // fau: changeSub - do the permission check with a fitting command
        // fau: joinAsGuest - do the permission check with a fitting command
        $cmd = $this->ctrl->getCmd("show");
        switch ($cmd) {
            case 'joinAsGuest':
            case 'joinAsGuestConfirmed':
                // don't check permission
                $this->$cmd();
                return true;

             // action buttons on registration screen
            case 'updateWaitingList':
            case 'leaveWaitingList':
            case 'updateSubscriptionRequest':
            case 'cancelSubscriptionRequest':
                $checkCmd = 'leave';
                break;

            // called for updating scubscription requests
            case 'leave':
                $checkCmd = 'leave';
                $cmd = 'show';
                break;

            // called for joining
            default:
                $checkCmd = '';
        }
        if (!$DIC->access()->checkAccess('join', $checkCmd, $this->getRefId())) {
            $this->ctrl->setReturn($this->parent_gui, 'infoScreen');
            $this->ctrl->returnToParent($this);
            return false;
        }
        
        $next_class = $this->ctrl->getNextClass($this);
        switch ($next_class) {
            default:
                // command is already prepared
                // $cmd = $this->ctrl->getCmd("show");
                $this->$cmd();
                break;
        }
        // fau.
        return true;
    }
    
    /**
     * get form title
     *
     * @access protected
     * @return string title
     */
    protected function getFormTitle()
    {
        global $DIC;

        $ilUser = $DIC['ilUser'];
        
        if ($this->getWaitingList()->isOnList($ilUser->getId())) {
            return $this->lng->txt('member_status');
        }
        return $this->lng->txt('crs_registration');
    }
    
    /**
     * fill informations
     * @access protected
     * @param
     * @return
     */
    protected function fillInformations()
    {
        if ($this->container->getImportantInformation()) {
            // fau: univisInfo - user custom input for info
            $imp = new ilCustomInputGUI($this->lng->txt('crs_important_info'), "", true);
            $html = nl2br(ilUtil::makeClickable($this->container->getImportantInformation(), true));
            $imp->setHtml($html);
            $this->form->addItem($imp);
            // fau.
        }
        
        /* fau: univisInfo - don't show syllabus
        if($this->container->getSyllabus())
        {
            $syl = new ilNonEditableValueGUI($this->lng->txt('crs_syllabus'), "", true);
            $value = nl2br(ilUtil::makeClickable ($this->container->getSyllabus(), true));
            $syl->setValue($value);
            $this->form->addItem($syl);
        }
        fau. */
    }
    
    /**
     * show informations about the registration period
     *
     * @access protected
     */
    protected function fillRegistrationPeriod()
    {
        include_once('./Services/Calendar/classes/class.ilDateTime.php');
        $now = new ilDateTime(time(), IL_CAL_UNIX, 'UTC');

        // fau: campusSub - no registration period for subscription by my campus
        if ($this->container->getSubscriptionLimitationType() == IL_CRS_SUBSCRIPTION_MYCAMPUS
            or $this->container->getSubscriptionType() == IL_CRS_SUBSCRIPTION_MYCAMPUS) {
            return true;
        }
        // fau.

        // fau: objectSub - no registration period for subscription by object
        if ($this->container->getSubscriptionType() == IL_CRS_SUBSCRIPTION_OBJECT) {
            return true;
        }
        // fau.
        if ($this->container->getSubscriptionUnlimitedStatus()) {
            // fau: fairSub	- add info about fair time for unlimited subscription
            if ($this->container->inSubscriptionFairTime()) {
                $suffix = " | " . $this->lng->txt('sub_fair_date') . ': ' . $this->container->getSubscriptionFairDisplay(false);
            }
            $reg = new ilNonEditableValueGUI($this->lng->txt('mem_reg_period'));
            $reg->setValue($this->lng->txt('mem_unlimited') . $suffix);
            $this->form->addItem($reg);
            // fau.
            return true;
        } elseif ($this->container->getSubscriptionLimitationType() == IL_CRS_SUBSCRIPTION_DEACTIVATED) {
            return true;
        }
        
        $start = new ilDateTime($this->container->getSubscriptionStart(), IL_CAL_UNIX, 'UTC');
        $end = new ilDateTime($this->container->getSubscriptionEnd(), IL_CAL_UNIX, 'UTC');
        
        if (ilDateTime::_before($now, $start)) {
            $tpl = new ilTemplate('tpl.registration_period_form.html', true, true, 'Services/Membership');
            $tpl->setVariable('TXT_FIRST', $this->lng->txt('mem_start'));
            $tpl->setVariable('FIRST', ilDatePresentation::formatDate($start));
            
            $tpl->setVariable('TXT_END', $this->lng->txt('mem_end'));
            $tpl->setVariable('END', ilDatePresentation::formatDate($end));
            
            $warning = $this->lng->txt('mem_reg_not_started');
        } elseif (ilDateTime::_after($now, $end)) {
            $tpl = new ilTemplate('tpl.registration_period_form.html', true, true, 'Services/Membership');
            $tpl->setVariable('TXT_FIRST', $this->lng->txt('mem_start'));
            $tpl->setVariable('FIRST', ilDatePresentation::formatDate($start));
            
            $tpl->setVariable('TXT_END', $this->lng->txt('mem_end'));
            $tpl->setVariable('END', ilDatePresentation::formatDate($end));
            
            
            $warning = $this->lng->txt('mem_reg_expired');
        } else {
            $tpl = new ilTemplate('tpl.registration_period_form.html', true, true, 'Services/Membership');
            $tpl->setVariable('TXT_FIRST', $this->lng->txt('mem_end'));
            $tpl->setVariable('FIRST', ilDatePresentation::formatDate($end));
        }

        // fau: fairSub	- add info about fair time for limited subscription
        // fau: paraSub	- treat course with parallel groups like limitated
        if (($this->container->isSubscriptionMembershipLimited() && $this->container->getSubscriptionMaxMembers()) || $this->container->hasParallelGroups()) {
            if ($this->container->getSubscriptionFair() >= 0) {
                $tpl->setVariable('TXT_FAIR', $this->lng->txt('sub_fair_date') . ': ');
                $tpl->setVariable('FAIR', $this->container->getSubscriptionFairDisplay(false));
            } else {
                $tpl->setVariable('TXT_FAIR', $this->lng->txt('sub_fair_inactive_short'));
            }
        }
        // fau.

        $reg = new ilCustomInputGUI($this->lng->txt('mem_reg_period'));
        $reg->setHtml($tpl->get());
        if (strlen($warning)) {
            // Disable registration
            $this->enableRegistration(false);
            ilUtil::sendFailure($warning);
            #$reg->setAlert($warning);
        }
        $this->form->addItem($reg);
        return true;
    }
    
    
    /**
     * fill max members
     *
     * @access protected
     * @param
     * @return
     */
    protected function fillMaxMembers()
    {
        global $DIC;
        
        $ilUser = $DIC['ilUser'];
        
        // fau: campusSub - no membership info for subscription by my campus
        if ($this->container->getSubscriptionLimitationType() == IL_CRS_SUBSCRIPTION_MYCAMPUS
            or $this->container->getSubscriptionType() == IL_CRS_SUBSCRIPTION_MYCAMPUS) {
            return true;
        }
        // fau.

        // fau: objectSub - no max members for subscription by object
        if ($this->container->getSubscriptionType() == IL_CRS_SUBSCRIPTION_OBJECT) {
            return true;
        }
        // fau.

        if (!$this->container->isSubscriptionMembershipLimited()) {
            return true;
        }
        $tpl = new ilTemplate('tpl.max_members_form.html', true, true, 'Services/Membership');
        
        if ($this->container->getSubscriptionMinMembers()) {
            $tpl->setVariable('TXT_MIN', $this->lng->txt('mem_min_users') . ':');
            $tpl->setVariable('NUM_MIN', $this->container->getSubscriptionMinMembers());
        }
        
        if ($this->container->getSubscriptionMaxMembers()) {
            $tpl->setVariable('TXT_MAX', $this->lng->txt('mem_max_users'));
            $tpl->setVariable('NUM_MAX', $this->container->getSubscriptionMaxMembers());

            $tpl->setVariable('TXT_FREE', $this->lng->txt('mem_free_places') . ":");
            $free = max(0, $this->container->getSubscriptionMaxMembers() - $this->participants->getCountMembers());

            if ($free) {
                $tpl->setVariable('NUM_FREE', $free);
            } else {
                $tpl->setVariable('WARN_FREE', $free);
            }

            // fau: fairSub - get already instantiated waiting list and use own check function
            $waiting_list = $this->getWaitingList();
            if ($this->isWaitingListActive()) {
                // fau.
                if ($waiting_list->isOnList($ilUser->getId())) {
                    $tpl->setVariable('TXT_WAIT', $this->lng->txt('mem_waiting_list_position'));
                    // fau: fairSub - show effective position and other sharing users
                    $tpl->setVariable('NUM_WAIT', $waiting_list->getPositionInfo($ilUser->getId()));
                // fau.
                } else {
                    $tpl->setVariable('TXT_WAIT', $this->lng->txt('mem_waiting_list'));
                    if ($free and $waiting_list->getCountUsers()) {
                        $tpl->setVariable('WARN_WAIT', $waiting_list->getCountUsers());
                    } else {
                        $tpl->setVariable('NUM_WAIT', $waiting_list->getCountUsers());
                    }
                }
            }

            $alert = '';
            // fau: fairSub - add message and adjust label for fair subscription
            if ($this->container->getSubscriptionFair() < 0) {
                ilUtil::sendInfo($this->lng->txt('sub_fair_inactive_message'));
            }

            if ($this->container->inSubscriptionFairTime()) {
                ilUtil::sendInfo(sprintf($this->lng->txt('sub_fair_subscribe_message'), $this->container->getSubscriptionFairDisplay(true)));
            } elseif (
            // fau.
                    !$free and
                    !$this->container->enabledWaitingList()) {
                // Disable registration
                $this->enableRegistration(false);
                ilUtil::sendFailure($this->lng->txt('mem_alert_no_places'));
            #$alert = $this->lng->txt('mem_alert_no_places');
            } elseif (
                    $this->container->enabledWaitingList() and
                    $this->container->isSubscriptionMembershipLimited() and
                    $waiting_list->isOnList($ilUser->getId())
            ) {
                // fau: fairSub - allow to change a registration
                $this->enableRegistration(true);
            }
            // fau.
            elseif (
                    !$free and
                    $this->container->enabledWaitingList() and
                    $this->container->isSubscriptionMembershipLimited()) {
                ilUtil::sendFailure($this->lng->txt('crs_warn_no_max_set_on_waiting_list'));
                #$alert = $this->lng->txt('crs_warn_no_max_set_on_waiting_list');
            }
            // fau: fairSub - add to waiting list if free places are needed for already waiting users (see also add() function)
            elseif (
                    $free and
                    $this->container->enabledWaitingList() and
                    $this->container->isSubscriptionMembershipLimited() and
                    ($this->getWaitingList()->getCountUsers() >= $free)) {
                ilUtil::sendFailure($this->lng->txt('crs_warn_wl_set_on_waiting_list'));
                #$alert = $this->lng->txt('crs_warn_wl_set_on_waiting_list');
            }
            // fau.
        }
        
        $max = new ilCustomInputGUI($this->lng->txt('mem_participants'));
        $max->setHtml($tpl->get());
        if (strlen($alert)) {
            $max->setAlert($alert);
        }
        $this->form->addItem($max);
        return true;
    }
    
    /**
     * fill registration type
     *
     * @access protected
     * @return
     */
    protected function fillRegistrationType()
    {
        global $DIC;
        
        $ilUser = $DIC['ilUser'];
        
        // fau: campusSub - handle subscription by my campus
        if ($this->container->getSubscriptionType() == IL_CRS_SUBSCRIPTION_MYCAMPUS
            or $this->container->getSubscriptionLimitationType() == IL_CRS_SUBSCRIPTION_MYCAMPUS) {
            $reg = new ilCustomInputGUI($this->lng->txt('mem_reg_type'));

            $reg->setHtml(sprintf(
                $this->lng->txt('crs_subscription_mycampus_registration'),
                ilUtil::getImagePath('studon/meinCampusSmall.gif'),
                sprintf(ilCust::get('mycampus_reg_url'), $this->container->getImportId())
            ));
            $this->form->addItem($reg);

            // Disable registration
            $this->enableRegistration(false);
            return true;
        }
        // fau.

        // fau: objectSub - fill registration by separate object
        if ($this->container->getSubscriptionType() == IL_CRS_SUBSCRIPTION_OBJECT) {
            return $this->fillRegistrationTypeObject($this->container->getSubscriptionRefId());
        }
        // fau.

        if ($this->container->getSubscriptionLimitationType() == IL_CRS_SUBSCRIPTION_DEACTIVATED) {
            $reg = new ilCustomInputGUI($this->lng->txt('mem_reg_type'));
            #$reg->setHtml($this->lng->txt('crs_info_reg_deactivated'));
            $reg->setAlert($this->lng->txt('crs_info_reg_deactivated'));
            #ilUtil::sendFailure($this->lng->txt('crs_info_reg_deactivated'));
            #$reg = new ilNonEditableValueGUI($this->lng->txt('mem_reg_type'));
            #$reg->setValue($this->lng->txt('crs_info_reg_deactivated'));
            #$reg->setAlert($this->lng->txt('grp_reg_deactivated_alert'));
            $this->form->addItem($reg);
        
            // Disable registration
            $this->enableRegistration(false);
            return true;
        }

        // fau: studyCond - check actual subscription type
        switch ($this->subscription_type) {
// fau.
            case IL_CRS_SUBSCRIPTION_DIRECT:

// fau: fairSub - allow "request" info if waiting list is active
// fau.

// fau: studyCond - set direct subscription info for studycond
                $txt = new ilCustomInputGUI($this->lng->txt('mem_reg_type'));
                if ($this->has_studycond) {
                    $txt->setHtml(sprintf($this->lng->txt('crs_subscription_options_direct_studycond'), $this->describe_studycond));
                } else {
                    $txt->setHtml($this->lng->txt('crs_subscription_options_direct'));
                }
// fau.

                $this->form->addItem($txt);
                break;

            case IL_CRS_SUBSCRIPTION_PASSWORD:
// fau: studyCond - set password subscription info for studycond
                $txt = new ilCustomInputGUI($this->lng->txt('mem_reg_type'));
                if ($this->has_studycond) {
                    $txt->setHtml(sprintf($this->lng->txt('crs_subscription_options_password_studycond'), $this->describe_studycond));
                } else {
                    $txt->setHtml($this->lng->txt('crs_subscription_options_password'));
                }
// fau.

                $pass = new ilTextInputGUI($this->lng->txt('passwd'), 'grp_passw');
                $pass->setInputType('password');
                $pass->setSize(12);
                $pass->setMaxLength(32);
                #$pass->setRequired(true);
                $pass->setInfo($this->lng->txt('crs_info_reg_password'));
                
                $txt->addSubItem($pass);
                $this->form->addItem($txt);
                break;
                
            case IL_CRS_SUBSCRIPTION_CONFIRMATION:

// fau: fairSub - allow "request" info if waiting list is active
// fau.
// fau: studyCond - set confirmation subscription info for studycond
                $txt = new ilCustomInputGUI($this->lng->txt('mem_reg_type'));
                if ($this->has_studycond and $this->container->getSubscriptionType() == IL_CRS_SUBSCRIPTION_DIRECT) {
                    $txt->setHtml(sprintf($this->lng->txt('crs_subscription_options_direct_studycond'), $this->describe_studycond));
                } elseif ($this->has_studycond and $this->container->getSubscriptionType() == IL_CRS_SUBSCRIPTION_PASSWORD) {
                    $txt->setHtml(sprintf($this->lng->txt('crs_subscription_options_password_studycond'), $this->describe_studycond));
                } else {
                    $txt->setHtml($this->lng->txt('crs_subscription_options_confirmation'));
                }
// fau.
            
                $sub = new ilTextAreaInputGUI($this->lng->txt('crs_reg_subject'), 'subject');
                $sub->setValue($_POST['subject']);
                $sub->setInfo($this->lng->txt('crs_info_reg_confirmation'));
// fau: fairSub - extend size of subject field
                $sub->setRows(10);
// fau.
// fau: fairSub - treat existing subscription on waiting list
                if ($this->getWaitingList()->isToConfirm($ilUser->getId())) {
                    $sub->setValue($this->getWaitingList()->getSubject($ilUser->getId()));
                    $sub->setInfo('');
                    ilUtil::sendQuestion('mem_user_already_subscribed');
                    //$this->enableRegistration(true);
                }
// fau.
                $txt->addSubItem($sub);
                $this->form->addItem($txt);

                break;
                

            default:
                return true;
        }
        
        return true;
    }


    // fau: paraSub - fill form with the selection of parallel groups
    protected function fillGroupSelection()
    {
        if (empty($this->registration->getParallelGroupsInfos())) {
            return;
        }

        $head = new ilFormSectionHeaderGUI();
        $head->setTitle($this->lng->txt('fau_sub_select_groups'));
        $head->setInfo($this->lng->txt('fau_sub_select_groups_info'));
        $this->form->addItem($head);

        $cb = new ilCheckboxGroupInputGUI($this->lng->txt('fau_sub_select_groups'), 'group_ref_ids');
        $cb->setRequired(true);
        $selected = [];
        foreach ($this->registration->getParallelGroupsInfos() as $group) {
            if ($group->isOnWaitingList()) {
                $selected[] = $group->getRefId();
            }
            if ($this->registration->isDirectJoinPossibleForGroup($group)) {
                $group = $group->withProperty((new \FAU\Ilias\Data\ListProperty(null, $this->lng->txt('fau_sub_direct_possible')))->withAlert(true));
            }
            else {
                $group = $group->withProperty((new \FAU\Ilias\Data\ListProperty(null, $this->lng->txt('mem_request_waiting')))->withAlert(true));
            }
            $option = new ilCheckboxOption($group->getTitle(), $group->getRefId());
            $option->setInfo($group->getInfoHtml());
            $option->setDisabled(!$group->isSubscriptionPossible());
            $cb->addOption($option);
        }
        $cb->setValue($selected);
        $this->form->addItem($cb);

    }
    // fau.

    /**
     * Add course specific command buttons
     * @return
     */
    protected function addCommandButtons()
    {
        global $DIC;
        
        $ilUser = $DIC['ilUser'];

        // fau: fairSub - use parent addCommandButtons()
        parent::addCommandButtons();
        return true;
        // fau.
    }

    /**
     * Validate subscription request
     *
     * @access protected
     * @param
     * @return
     */
    protected function validate()
    {
        global $DIC;

        $ilUser = $DIC['ilUser'];
        
        if ($ilUser->getId() == ANONYMOUS_USER_ID) {
            $this->join_error = $this->lng->txt('permission_denied');
            return false;
        }
        
        // Set aggrement to not accepted
        $this->setAccepted(false);
        
        if (!$this->isRegistrationPossible()) {
            $this->join_error = $this->lng->txt('mem_error_preconditions');
            return false;
        }
        // fau: studyCond - check actual subscription type
        if ($this->subscription_type == IL_CRS_SUBSCRIPTION_PASSWORD) {
            // fau.
            if (!strlen($pass = ilUtil::stripSlashes($_POST['grp_passw']))) {
                $this->join_error = $this->lng->txt('crs_password_required');
                return false;
            }
            if (strcmp($pass, $this->container->getSubscriptionPassword()) !== 0) {
                $this->join_error = $this->lng->txt('crs_password_not_valid');
                return false;
            }
        }

        // fau: courseUdf - custom fields are validate with the form
        //		if(!$this->validateCustomFields())
        //		{
        //			$this->join_error = $this->lng->txt('fill_out_all_required_fields');
        //			return false;
        //		}
        // fau.

        if (!$this->validateAgreement()) {
            $this->join_error = $this->lng->txt('crs_agreement_required');
            return false;
        }
        
        return true;
    }


    // fau: heavySub - avoid failures on heavy concurrency
    // fau: fairSub - add subscription requests and requests in fair time to waiting list
    // fau: studyCond - use condition based subscription type
    // fau: paraSub - handle subscription to parallel groups and use for updating requests
    /**
     * add user
     *
     * @access protected
     * @param
     * @return
     */
    protected function add()
    {
        global $DIC;
        $ilUser = $DIC->user();
        $tree = $DIC->repositoryTree();
        $ilCtrl = $DIC->ctrl();

        /////////////////////////////////////////////////////////////
        // FAKES SIMULATING PARALLEL REQUESTS

        // global $ilDB;

        // ADD AS MEMBER
        /*
            global $ilDB;
            $mem_rol_id = $this->participants->getRoleId(IL_CRS_MEMBER);
            $query = "INSERT INTO rbac_ua (rol_id, usr_id) ".
                "VALUES (".
                $ilDB->quote($mem_rol_id ,'integer').", ".
                $ilDB->quote($ilUser->getId() ,'integer').
                ")";
            $res = $ilDB->manipulate($query);
        */

        // ADD TO WAITING LIST
        /*
           global $ilDB;
           $query = "INSERT INTO crs_waiting_list (obj_id, usr_id, sub_time, subject) ".
                "VALUES (".
                $ilDB->quote($this->container->getId() ,'integer').", ".
                $ilDB->quote($ilUser->getId() ,'integer').", ".
                $ilDB->quote(time() ,'integer').", ".
                $ilDB->quote($_POST['subject'] ,'text')." ".
                ")";
            $res = $ilDB->manipulate($query);
        */

        ////////////////////////////////////////////////////////////////

        // set agreement accepted
        $this->setAccepted(true);

        // perform the registration (result determines the next action)
        $this->registration->doRegistration(ilUtil::stripSlashes($_POST['subject']), (array) $_POST['group_ref_ids'], (int) 0);

        // get the link to the upper container
        $ilCtrl->setParameterByClass(
            "ilrepositorygui",
            "ref_id",
            $tree->getParentId($this->container->getRefId())
        );

        switch ($this->registration->getNextAction()) {
            case Registration::notifyAdded:

                $this->participants->sendNotification($this->participants->NOTIFY_ADMINS, $ilUser->getId());
                $this->participants->sendNotification($this->participants->NOTIFY_REGISTERED, $ilUser->getId());
                //fau: courseUdf - send external notifications
                $this->participants->sendExternalNotifications($this->container, $ilUser);
                // fau.
                ilForumNotification::checkForumsExistsInsert($this->container->getRefId(), $ilUser->getId());
                                
                if ($this->container->getType() == "crs") {
                    $this->container->checkLPStatusSync($ilUser->getId());
                }

                if (!$_SESSION["pending_goto"]) {
                    ilUtil::sendSuccess($this->lng->txt("crs_subscription_successful"), true);
                    $this->ctrl->returnToParent($this);
                } else {
                    $tgt = $_SESSION["pending_goto"];
                    unset($_SESSION["pending_goto"]);
                    ilUtil::redirect($tgt);
                }
                break;

            case Registration::notifyAddedToWaitingList:
                $this->participants->sendAddedToWaitingList($ilUser->getId(), $this->getWaitingList());	// mail to user
                if ($this->subscription_type == IL_CRS_SUBSCRIPTION_CONFIRMATION) {
                    $this->participants->sendSubscriptionRequestToAdmins($ilUser->getId());				// mail to admins
                }
                // fau: courseUdf - send external notifications
                $this->participants->sendExternalNotifications($this->container, $ilUser);
                // fau.

                $info = sprintf($this->lng->txt('sub_added_to_waiting_list'), $this->getWaitingList()->getPositionInfo($ilUser->getId()));
                ilUtil::sendSuccess($info, true);
                $ilCtrl->redirectByClass("ilrepositorygui");
                break;

            case Registration::showAddedToWaitingListFair:
                // no e-mail to subscriber needed because the place on the lst is not relevant
                // fau: courseUdf - send external notifications
                $this->participants->sendExternalNotifications($this->container, $ilUser);
                // fau.

                ilUtil::sendSuccess($this->lng->txt("sub_fair_added_to_waiting_list"), true);
                $ilCtrl->redirectByClass("ilrepositorygui");
                break;

            case Registration::showUpdatedWaitingList:
                ilUtil::sendSuccess($this->lng->txt('sub_request_saved'), true);
                $ilCtrl->redirectByClass("ilrepositorygui");
                break;

            case Registration::showLimitReached:
                ilUtil::sendSuccess($this->lng->txt("crs_reg_limit_reached"), true);
                $ilCtrl->redirectByClass("ilrepositorygui");
                break;

            case Registration::showAlreadyMember:
                ilUtil::sendInfo($this->lng->txt("crs_reg_user_already_assigned"), true);
                $ilCtrl->redirectByClass("ilrepositorygui");
                break;

            case Registration::showGenericFailure:
                ilUtil::sendFailure($this->lng->txt("crs_reg_user_generic_failure"), true);
                $ilCtrl->redirectByClass("ilrepositorygui");
                break;
        }
    }
    // fau.
    
    /**
     * Init course participants
     *
     * @access protected
     */
    protected function initParticipants()
    {
        include_once('./Modules/Course/classes/class.ilCourseParticipants.php');
        $this->participants = ilCourseParticipants::_getInstanceByObjId($this->obj_id);
    }
    

    /**
     * @see ilRegistrationGUI::initWaitingList()
     * @access protected
     */
    protected function initWaitingList()
    {
        include_once './Modules/Course/classes/class.ilCourseWaitingList.php';
        $this->waiting_list = new ilCourseWaitingList($this->container->getId());
    }
    
    /**
     * @see ilRegistrationGUI::isWaitingListActive()
     */
    protected function isWaitingListActive()
    {
        // fau: paraSub - use own function isWaitingListActive()
        return $this->registration->isWaitingListActive();
        // fau.
    }
}
