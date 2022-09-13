<?php
/*
        +-----------------------------------------------------------------------------+
        | ILIAS open source                                                           |
        +-----------------------------------------------------------------------------+
        | Copyright (c) 1998-2006 ILIAS open source, University of Cologne            |
        |                                                                             |
        | This program is free software; you can redistribute it and/or               |
        | modify it under the terms of the GNU General Public License                 |
        | as published by the Free Software Foundation; either version 2              |
        | of the License, or (at your option) any later version.                      |
        |                                                                             |
        | This program is distributed in the hope that it will be useful,             |
        | but WITHOUT ANY WARRANTY; without even the implied warranty of              |
        | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
        | GNU General Public License for more details.                                |
        |                                                                             |
        | You should have received a copy of the GNU General Public License           |
        | along with this program; if not, write to the Free Software                 |
        | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
        +-----------------------------------------------------------------------------+
*/

// fau: paraSub - import of registration class
use FAU\Ilias\Registration;
// fau.

include_once('./Services/Membership/classes/class.ilRegistrationGUI.php');
include_once './Modules/Group/classes/class.ilGroupMembershipMailNotification.php';

/**
* GUI class for group registrations
*
*
* @author Stefan Meyer <smeyer.ilias@gmx.de>
* @version $Id$
*
* @ingroup ModulesGroup
*/
class ilGroupRegistrationGUI extends ilRegistrationGUI
{

// fau: fairSub - added type hints
    /** @var ilRegistrationGUI $parent_gui */
    private $parent_gui = null;

    /** @var ilObjGroup $container */
    protected $container = null;

    /** @var  ilGroupParticipants  $participants*/
    protected $participants;

    /** @var int $registration_type */
    protected $registration_type;
    // fau.

    /**
     * Constructor
     *
     * @access public
     * @param object container object
     */
    public function __construct($a_container)
    {
        parent::__construct($a_container);

        // fau: studyCond - call adjustSubType
        $this->adjustSubType();
        // fau.
    }

    // fau: studyCond - adjust the subscription type based on soft conditions
    // fau: campoCheck - adjust the subscription type based on soft conditions
    protected function adjustSubType()
    {
        if (($this->matches_studycond && $this->matches_restrictions) || $this->container->getRegistrationType() == GRP_REGISTRATION_DEACTIVATED) {
            $this->registration_type = $this->container->getRegistrationType();
        } else {
            $this->registration_type = GRP_REGISTRATION_REQUEST;
            $this->registration->setSubType(Registration::subConfirmation);
        }
    }
    // fau.
    
    /**
     * Execute command
     *
     * @access public
     */
    public function executeCommand()
    {
        global $DIC;

        $ilUser = $DIC['ilUser'];
        $ilTabs = $DIC['ilTabs'];
        
        $next_class = $this->ctrl->getNextClass($this);
        
        if ($this->getWaitingList()->isOnList($ilUser->getId())) {
            $ilTabs->activateTab('leave');
        }

        switch ($next_class) {
            default:
                $cmd = $this->ctrl->getCmd("show");
                $this->$cmd();
                break;
        }
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
        return $this->lng->txt('grp_registration');
    }
    
    /**
     * fill informations
     *
     * @access protected
     * @param
     * @return
     */
    protected function fillInformations()
    {
        if ($this->container->getInformation()) {
            $imp = new ilNonEditableValueGUI($this->lng->txt('crs_important_info'), '', true);
            $value = nl2br(ilUtil::makeClickable($this->container->getInformation(), true));
            $imp->setValue($value);
            $this->form->addItem($imp);
        }
    }
    
    /**
     * show informations about the registration period
     *
     * @access protected
     */
    protected function fillRegistrationPeriod()
    {

// fau: objectSub - no registration period for subscription by object
        if ($this->container->getRegistrationType() == GRP_REGISTRATION_OBJECT) {
            return true;
        }
        // fau.

        include_once('./Services/Calendar/classes/class.ilDateTime.php');
        $now = new ilDateTime(time(), IL_CAL_UNIX, 'UTC');

        if ($this->container->isRegistrationUnlimited()) {
            // fau: fairSub	- add info about fair time for unlimited subscription
            if ($this->container->inSubscriptionFairTime()) {
                $suffix = " | " . $this->lng->txt('sub_fair_date') . ': ' . $this->container->getSubscriptionFairDisplay(false);
            }
            $reg = new ilNonEditableValueGUI($this->lng->txt('mem_reg_period'));
            $reg->setValue($this->lng->txt('mem_unlimited') . $suffix);
            $this->form->addItem($reg);
            // fau.
            return true;
        }
        
        $start = $this->container->getRegistrationStart();
        $end = $this->container->getRegistrationEnd();
        
        
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
        if ($this->container->isMembershipLimited() && $this->container->getMaxMembers()) {
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
            #$reg->setAlert($warning);
            ilUtil::sendFailure($warning);
        }
        $this->form->addItem($reg);
        return true;
    }
    
    /**
     * fill max member informations
     *
     * @access protected
     * @return
     */
    protected function fillMaxMembers()
    {
        global $DIC;

        $ilUser = $DIC['ilUser'];
        
        // fau: objectSub - no max members for subscription by object
        if ($this->container->getRegistrationType() == GRP_REGISTRATION_OBJECT) {
            return true;
        }
        // fau.

        if (!$this->container->isMembershipLimited()) {
            return true;
        }
        
        $tpl = new ilTemplate('tpl.max_members_form.html', true, true, 'Services/Membership');

        if ($this->container->getMinMembers()) {
            $tpl->setVariable('TXT_MIN', $this->lng->txt('mem_min_users'));
            $tpl->setVariable('NUM_MIN', $this->container->getMinMembers());
        }
        
        if ($this->container->getMaxMembers()) {
            $tpl->setVariable('TXT_MAX', $this->lng->txt('mem_max_users'));
            $tpl->setVariable('NUM_MAX', $this->container->getMaxMembers());
            $tpl->setVariable('TXT_FREE', $this->lng->txt('mem_free_places') . ":");

            include_once './Modules/Group/classes/class.ilObjGroupAccess.php';
            $reg_info = ilObjGroupAccess::lookupRegistrationInfo($this->getContainer()->getId());
            $free = $reg_info['reg_info_free_places'];

            $tpl->setVariable('TXT_FREE', $this->lng->txt('mem_free_places'));
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
                    $tpl->setVariable('TXT_WAIT', $this->lng->txt('subscribers_or_waiting_list'));
                    if ($free and $waiting_list->getCountUsers()) {
                        $tpl->setVariable('WARN_WAIT', $waiting_list->getCountUsers());
                    } else {
                        $tpl->setVariable('NUM_WAIT', $waiting_list->getCountUsers());
                    }
                }
            }

            $alert = '';
            // fau: fairSub - add message
            if ($this->container->getSubscriptionFair() < 0) {
                ilUtil::sendInfo($this->lng->txt('sub_fair_inactive_message'));
            }
            if ($this->container->inSubscriptionFairTime()) {
                ilUtil::sendInfo(sprintf($this->lng->txt('sub_fair_subscribe_message'), $this->container->getSubscriptionFairDisplay(true)));
            } elseif (
// fau.
                !$free and
                !$this->container->isWaitingListEnabled()) {
                // Disable registration
                $this->enableRegistration(false);
                $alert = $this->lng->txt('mem_alert_no_places');
            } elseif (
                    $this->container->isWaitingListEnabled() and
                    $this->container->isMembershipLimited() and
                    $waiting_list->isOnList($ilUser->getId())) {
                // Disable registration
                $this->enableRegistration(false);
            } elseif (
                    !$free and
                    $this->container->isWaitingListEnabled() and
                    $this->container->isMembershipLimited()) {
                $alert = $this->lng->txt('grp_warn_no_max_set_on_waiting_list');
            }
            // fau: fairSub - add to waiting list if free places are needed for already waiting users (see also add() function)
            elseif (
                $free and
                $this->container->isWaitingListEnabled() and
                $this->container->isMembershipLimited() and
                ($this->getWaitingList()->getCountUsers() >= $free)) {
                $waiting_list = $this->getWaitingList();
                $waiting = $waiting_list->getCountUsers();

                ilUtil::sendFailure($this->lng->txt('grp_warn_wl_set_on_waiting_list'));
                #$alert = $this->lng->txt('grp_warn_wl_set_on_waiting_list');
            }
            // fau.
        }
        
        $max = new ilCustomInputGUI($this->lng->txt('mem_participants'));
        $max->setHtml($tpl->get());
        if (strlen($alert)) {
            #$max->setAlert($alert);
            ilUtil::sendFailure($alert);
        }
        $this->form->addItem($max);
    }
    
    /**
     * fill registration procedure
     *
     * @access protected
     * @param
     * @return
     */
    protected function fillRegistrationType()
    {
        global $DIC;

        $ilUser = $DIC['ilUser'];

        // fau: objectSub - fill registration by separate object
        if ($this->container->getRegistrationType() == GRP_REGISTRATION_OBJECT) {
            return $this->fillRegistrationTypeObject($this->container->getRegistrationRefId());
        }
        // fau.

        // fau: studyCond - check actual registration type
        switch ($this->registration_type) {
// fau.
            case GRP_REGISTRATION_DEACTIVATED:
                $reg = new ilNonEditableValueGUI($this->lng->txt('mem_reg_type'));
                $reg->setValue($this->lng->txt('grp_reg_disabled'));
                #$reg->setAlert($this->lng->txt('grp_reg_deactivated_alert'));
                $this->form->addItem($reg);
        
                // Disable registration
                $this->enableRegistration(false);
                
                break;
                
            case GRP_REGISTRATION_PASSWORD:
// fau: studyCond - set password subscription info for studycond
                $txt = new ilCustomInputGUI($this->lng->txt('mem_reg_type'));
                if ($this->has_studycond) {
                    $txt->setHtml(sprintf($this->lng->txt('grp_pass_request_studycond'), $this->describe_studycond));
                } else {
                    $txt->setHtml($this->lng->txt('grp_pass_request'));
                }
// fau.

                $pass = new ilTextInputGUI($this->lng->txt('passwd'), 'grp_passw');
                $pass->setInputType('password');
                $pass->setSize(12);
                $pass->setMaxLength(32);
                #$pass->setRequired(true);
                $pass->setInfo($this->lng->txt('group_password_registration_msg'));
                
                $txt->addSubItem($pass);
                $this->form->addItem($txt);
                break;
                
            case GRP_REGISTRATION_REQUEST:

// fau: fairSub - allow "request" info if waiting list is active
// fau.

// fau: studyCond - set confirmation subscription info for studycond
                $txt = new ilCustomInputGUI($this->lng->txt('mem_reg_type'));
                if ($this->has_studycond and $this->container->getRegistrationType() == GRP_REGISTRATION_DIRECT) {
                    $txt->setHtml(sprintf($this->lng->txt('group_req_direct_studycond'), $this->describe_studycond));
                } elseif ($this->has_studycond and $this->container->getRegistrationType() == GRP_REGISTRATION_PASSWORD) {
                    $txt->setHtml(sprintf($this->lng->txt('grp_pass_request_studycond'), $this->describe_studycond));
                } else {
                    $txt->setHtml($this->lng->txt('grp_reg_request'));
                }
// fau.

                $sub = new ilTextAreaInputGUI($this->lng->txt('grp_reg_subject'), 'subject');
                $sub->setValue($_POST['subject']);
                $sub->setInfo($this->lng->txt('group_req_registration_lot'));

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
// fim.
                $txt->addSubItem($sub);
                $this->form->addItem($txt);

                break;
                
            case GRP_REGISTRATION_DIRECT:

// fau: fairSub - allow "request" info if waiting list is active
// fau.
// fau: studyCond - set subscription subscription info for studycond
                $txt = new ilCustomInputGUI($this->lng->txt('mem_reg_type'));
                if ($this->has_studycond) {
                    $txt->setHtml(sprintf($this->lng->txt('group_req_direct_studycond'), $this->describe_studycond));
                } else {
                    $txt->setHtml($this->lng->txt('group_req_direct'));
                }
                $txt->setInfo($this->lng->txt('grp_reg_direct_info_screen'));
// fau.
                
                $this->form->addItem($txt);
                break;

            default:
                return true;
        }
        
        return true;
    }
    
    /**
     * Add group specific command buttons
     * @return
     */
    protected function addCommandButtons()
    {
        global $DIC;

        $ilUser = $DIC['ilUser'];
        
        parent::addCommandButtons();
        

        // fau: fairSub - use parent addCommandButtons()
        return true;
        // fau.
    }
    
    
    /**
     * validate join request
     *
     * @access protected
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
        
        if (!$this->isRegistrationPossible()) {
            $this->join_error = $this->lng->txt('mem_error_preconditions');
            return false;
        }
        // fau: studyCond - check actual registration type
        if ($this->registration_type == GRP_REGISTRATION_PASSWORD) {
            // fau.
            if (!strlen($pass = ilUtil::stripSlashes($_POST['grp_passw']))) {
                $this->join_error = $this->lng->txt('err_wrong_password');
                return false;
            }
            if (strcmp($pass, $this->container->getPassword()) !== 0) {
                $this->join_error = $this->lng->txt('err_wrong_password');
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
            $this->join_error = $this->lng->txt($this->type . '_agreement_required');
            return false;
        }
        
        return true;
    }

    // fau: heavySub - avoid failures on heavy concurrency
    // fau: fairSub - add subscription requests and requests in fair time to waiting list
    // fau: studyCond - use condition based subscription type
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

        // set agreement accepted
        $this->setAccepted(true);

        $this->registration->doRegistration(ilUtil::stripSlashes($_POST['subject']), (array) $_POST['group_ref_ids'], (int) (int) $_POST['selected_module']);

        // get the link to the upper container
        $this->ctrl->setParameterByClass("ilrepositorygui", "ref_id",
            $DIC->repositoryTree()->getParentId($this->container->getRefId())
        );

        switch ($this->registration->getRegistrationAction()) {
            case Registration::notifyAdded:
                if (!$_SESSION["pending_goto"]) {
                    ilUtil::sendSuccess($this->lng->txt("grp_registration_completed"), true);
                    $this->ctrl->returnToParent($this);
                } else {
                    $tgt = $_SESSION["pending_goto"];
                    unset($_SESSION["pending_goto"]);
                    ilUtil::redirect($tgt);
                }
                break;

            case Registration::notifyAddedToWaitingList:
                $info = sprintf($this->lng->txt('sub_added_to_waiting_list'), $this->getWaitingList()->getPositionInfo($DIC->user()->getId()));
                ilUtil::sendSuccess($info, true);
                $this->ctrl->redirectByClass("ilrepositorygui");
                break;

            case Registration::showAddedToWaitingListFair:
                ilUtil::sendSuccess($this->lng->txt("sub_fair_added_to_waiting_list"), true);
                $this->ctrl->redirectByClass("ilrepositorygui");
                break;

            case Registration::showUpdatedWaitingList:
                ilUtil::sendSuccess($this->lng->txt('sub_request_saved'), true);
                $this->ctrl->redirectByClass("ilrepositorygui");
                break;

            case Registration::showLimitReached:
                ilUtil::sendFailure($this->lng->txt("grp_reg_limit_reached"), true);
                $this->ctrl->redirectByClass("ilrepositorygui");
                break;

            case Registration::showAlreadyMember:
                ilUtil::sendFailure($this->lng->txt("grp_reg_user_already_assigned"), true);
                $this->ctrl->redirectByClass("ilrepositorygui");
                break;

            case Registration::showGenericFailure:
                ilUtil::sendFailure($this->lng->txt("grp_reg_user_generic_failure"), true);
                $this->ctrl->redirectByClass("ilrepositorygui");
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
        include_once('./Modules/Group/classes/class.ilGroupParticipants.php');
        $this->participants = ilGroupParticipants::_getInstanceByObjId($this->obj_id);
    }
    
    /**
     * @see ilRegistrationGUI::initWaitingList()
     * @access protected
     */
    protected function initWaitingList()
    {
        include_once './Modules/Group/classes/class.ilGroupWaitingList.php';
        $this->waiting_list = new ilGroupWaitingList($this->container->getId());
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
