<?php

declare(strict_types=0);
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

use FAU\Ilias\Registration;
use FAU\Ilias\Helper\CourseConstantsHelper;
/**
 * GUI class for course registrations
 * @author       Stefan Meyer <smeyer.ilias@gmx.de>
 * @version      $Id$
 * @ingroup      ModulesCourse
 * @ilCtrl_Calls ilCourseRegistrationGUI:
 */
class ilCourseRegistrationGUI extends ilRegistrationGUI
{
    private object $parent_gui;

    public function __construct(ilObject $a_container, object $a_parent_gui)
    {
        parent::__construct($a_container);
        $this->parent_gui = $a_parent_gui;
    }

    public function executeCommand()
    {
        if ($this->getWaitingList()->isOnList($this->user->getId())) {
            $this->tabs->activateTab('leave');
        }
        
        // fau: changeSub - do the permission check with a fitting command
        // fau: joinAsGuest - do the permission check with a fitting command
        $cmd = $this->ctrl->getCmd("show");
        switch ($cmd) {
            case 'joinAsGuest':
            case 'joinAsGuestConfirmed':
                // don't check permission
                $this->$cmd();
                return;

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
        if (!$this->access->checkAccess('join', $checkCmd, $this->getRefId())) {
            $this->ctrl->setReturn($this->parent_gui, 'infoScreen');
            $this->ctrl->returnToParent($this);
            return;
        }

        $next_class = $this->ctrl->getNextClass($this);
        switch ($next_class) {
            default:
                $cmd = $this->ctrl->getCmd("show");
                $this->$cmd();
                break;
        }
        // fau.
        return;
    }
    
    protected function getFormTitle(): string
    {
        global $DIC;

        $ilUser = $DIC['ilUser'];
        
        if ($this->getWaitingList()->isOnList($ilUser->getId())) {
            return $this->lng->txt('member_status');
        }
        return $this->lng->txt('crs_registration');
    }

    protected function fillInformations(): void
    {
        if ($this->container->getImportantInformation()) {
            $imp = new ilNonEditableValueGUI($this->lng->txt('crs_important_info'), "", true);
            $value = nl2br(ilUtil::makeClickable($this->container->getImportantInformation(), true));
            $imp->setValue($value);
            $this->form->addItem($imp);
        }

        if ($this->container->getSyllabus()) {
            $syl = new ilNonEditableValueGUI($this->lng->txt('crs_syllabus'), "", true);
            $value = nl2br(ilUtil::makeClickable($this->container->getSyllabus(), true));
            $syl->setValue($value);
            $this->form->addItem($syl);
        }
    }

    protected function fillRegistrationPeriod(): void
    {
        $now = new ilDateTime(time(), IL_CAL_UNIX, 'UTC');

        // fau: objectSub - no registration period for subscription by object
        if ($this->container->getSubscriptionType() == CourseConstantsHelper::IL_CRS_SUBSCRIPTION_OBJECT) {
            return;
        }
        // fau.
        if ($this->container->getSubscriptionUnlimitedStatus()) {
            // fau: fairSub#32	- add info about fair time for unlimited subscription
            $suffix = "";
            if ($this->container->inSubscriptionFairTime()) {
                $suffix = " | " . $this->lng->txt('sub_fair_date') . ': ' . $this->container->getSubscriptionFairDisplay(false);
            }
            $reg = new ilNonEditableValueGUI($this->lng->txt('mem_reg_period'));
            $reg->setValue($this->lng->txt('mem_unlimited') . $suffix);
            $this->form->addItem($reg);
            // fau.
            return;
        } elseif ($this->container->getSubscriptionLimitationType() == ilCourseConstants::IL_CRS_SUBSCRIPTION_DEACTIVATED) {
            return;
        }

        $start = new ilDateTime($this->container->getSubscriptionStart(), IL_CAL_UNIX, 'UTC');
        $end = new ilDateTime($this->container->getSubscriptionEnd(), IL_CAL_UNIX, 'UTC');

        $warning = '';
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

        // fau: fairSub#33	- add info about fair time for limited subscription
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
            $this->tpl->setOnScreenMessage('failure', $warning);
            #$reg->setAlert($warning);
        }
        $this->form->addItem($reg);
    }

    protected function fillMaxMembers(): void
    {
        // fau: objectSub - no max members for subscription by object
        if ($this->container->getSubscriptionType() == CourseConstantsHelper::IL_CRS_SUBSCRIPTION_OBJECT) {
            return;
        }
        // fau.

        if (!$this->container->isSubscriptionMembershipLimited()) {
            return;
        }
        $tpl = new ilTemplate('tpl.max_members_form.html', true, true, 'Services/Membership');

        $alert = '';
        if ($this->container->getSubscriptionMinMembers()) {
            $tpl->setVariable('TXT_MIN', $this->lng->txt('mem_min_users') . ':');
            $tpl->setVariable('NUM_MIN', $this->container->getSubscriptionMinMembers());
        }

        if ($this->container->getSubscriptionMaxMembers()) {
            $tpl->setVariable('TXT_MAX', $this->lng->txt('mem_max_users'));
            $tpl->setVariable('NUM_MAX', $this->container->getSubscriptionMaxMembers());

            $tpl->setVariable('TXT_FREE', $this->lng->txt('mem_free_places') . ":");
            $reg_info = ilObjCourseAccess::lookupRegistrationInfo($this->getContainer()->getId());
            $free = $reg_info['reg_info_free_places'];

            if ($free) {
                $tpl->setVariable('NUM_FREE', $free);
            } else {
                $tpl->setVariable('WARN_FREE', $free);
            }

            // fau: fairSub#34 - get already instantiated waiting list and use own check function
            $waiting_list = $this->getWaitingList();
            if ($this->isWaitingListActive()) {
                // fau.
                if ($waiting_list->isOnList($this->user->getId())) {
                    $tpl->setVariable('TXT_WAIT', $this->lng->txt('mem_waiting_list_position'));
                    // fau: fairSub#35 - show effective position and other sharing users
                    $tpl->setVariable('NUM_WAIT', $waiting_list->getPositionInfo($this->user->getId()));
                // fau.
                } else {
                    $tpl->setVariable('TXT_WAIT', $this->lng->txt('mem_waiting_list'));
                    if ($free && $waiting_list->getCountUsers()) {
                        $tpl->setVariable('WARN_WAIT', $waiting_list->getCountUsers());
                    } else {
                        $tpl->setVariable('NUM_WAIT', $waiting_list->getCountUsers());
                    }
                }
            }
            // fau: fairSub#36 - add message and adjust label for fair subscription
            if ($this->container->getSubscriptionFair() < 0) {
                $this->tpl->setOnScreenMessage('info', $this->lng->txt('sub_fair_inactive_message'));
            }

            if ($this->container->inSubscriptionFairTime()) {
                $this->tpl->setOnScreenMessage('info', sprintf($this->lng->txt('sub_fair_subscribe_message'), $this->container->getSubscriptionFairDisplay(true)));
            } elseif (
            // fau.
                !$free && !$this->container->enabledWaitingList()) {
                // Disable registration
                $this->enableRegistration(false);
                $this->tpl->setOnScreenMessage('failure', $this->lng->txt('mem_alert_no_places'));
            #$alert = $this->lng->txt('mem_alert_no_places');
            } elseif (
                $this->container->enabledWaitingList() && $this->container->isSubscriptionMembershipLimited() && $waiting_list->isOnList($this->user->getId())
            ) {
                // fau: fairSub#37 - allow to change a registration
                $this->enableRegistration(true);
            }
            // fau.
            elseif (
                !$free && $this->container->enabledWaitingList() && $this->container->isSubscriptionMembershipLimited()) {
                $this->tpl->setOnScreenMessage('failure', $this->lng->txt('crs_warn_no_max_set_on_waiting_list'));
            #$alert = $this->lng->txt('crs_warn_no_max_set_on_waiting_list');
            }
            // fau: fairSub#38 - add to waiting list if free places are needed for already waiting users (see also add() function)
            elseif (
                $free and
                $this->container->enabledWaitingList() and
                $this->container->isSubscriptionMembershipLimited() and
                ($this->getWaitingList()->getCountUsers() >= $free)) {
                    $this->tpl->setOnScreenMessage('failure', $this->lng->txt('crs_warn_wl_set_on_waiting_list'));
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
    }

    protected function fillRegistrationType(): void    {
       
            // fau: objectSub - fill registration by separate object
            if ($this->container->getSubscriptionType() == CourseConstantsHelper::IL_CRS_SUBSCRIPTION_OBJECT) {
               $this->fillRegistrationTypeObject((int) $this->container->getSubscriptionRefId());
               return;
            }
            // fau. 
            if ($this->container->getSubscriptionLimitationType() == ilCourseConstants::IL_CRS_SUBSCRIPTION_DEACTIVATED) {
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
            return;
        }

        switch ($this->container->getSubscriptionType()) {
            case ilCourseConstants::IL_CRS_SUBSCRIPTION_DIRECT:

// fau: fairSub#39 - allow "request" info if waiting list is active
// fau.

                $txt = new ilNonEditableValueGUI($this->lng->txt('mem_reg_type'));
                $txt->setValue($this->lng->txt('crs_info_reg_direct'));

                $this->form->addItem($txt);
                break;

            case ilCourseConstants::IL_CRS_SUBSCRIPTION_PASSWORD:
                $txt = new ilNonEditableValueGUI($this->lng->txt('mem_reg_type'));
                $txt->setValue($this->lng->txt('crs_subscription_options_password'));

                $pass = new ilTextInputGUI($this->lng->txt('passwd'), 'grp_passw');
                $pass->setInputType('password');
                $pass->setSize(12);
                $pass->setMaxLength(32);
                #$pass->setRequired(true);
                $pass->setInfo($this->lng->txt('crs_info_reg_password'));

                $txt->addSubItem($pass);
                $this->form->addItem($txt);
                break;

            case ilCourseConstants::IL_CRS_SUBSCRIPTION_CONFIRMATION:

// fau: fairSub#40 - allow "request" info if waiting list is active
// fau.

                $txt = new ilNonEditableValueGUI($this->lng->txt('mem_reg_type'));
                $txt->setValue($this->lng->txt('crs_subscription_options_confirmation'));

                $sub = new ilTextAreaInputGUI($this->lng->txt('crs_reg_subject'), 'subject');
                $sub->setInfo($this->lng->txt('crs_info_reg_confirmation'));
                $sub->setCols(40);
                // fau: fairSub#41 - extend size of subject field
                $sub->setRows(10);
                // fau.
            // fau: fairSub#42 - treat existing subscription on waiting list
            if ($this->getWaitingList()->isOnList($this->user->getId())) {
                $sub->setValue($this->getWaitingList()->getSubject($this->user->getId()));
                if ($this->getWaitingList()->isToConfirm($this->user->getId())) {
                    $sub->setInfo($this->lng->txt('crs_info_reg_confirmation'));
                }
                else {
                    $sub->setInfo($this->lng->txt('sub_status_confirmed'));
                }
            }
            // fau.
                $txt->addSubItem($sub);
                $this->form->addItem($txt);
                break;

            default:
        }
    }

    protected function addCommandButtons(): void
    {
        // fau: fairSub#43 - use parent addCommandButtons()
        parent::addCommandButtons();
        // fau.
    }

    protected function validate(): bool
    {
        if ($this->user->getId() == ANONYMOUS_USER_ID) {
            $this->join_error = $this->lng->txt('permission_denied');
            return false;
        }

        // Set aggrement to not accepted
        $this->setAccepted(false);

        if (!$this->isRegistrationPossible()) {
            $this->join_error = $this->lng->txt('mem_error_preconditions');
            return false;
        }
        if ($this->container->getSubscriptionType() == ilCourseConstants::IL_CRS_SUBSCRIPTION_PASSWORD) {
            $pass = $this->http->wrapper()->post()->retrieve(
                'grp_passw',
                $this->refinery->kindlyTo()->string()
            );
            if ((string) $pass === '') {
                $this->join_error = $this->lng->txt('crs_password_required');
                return false;
            }
            if (strcmp($pass, $this->container->getSubscriptionPassword()) !== 0) {
                $this->join_error = $this->lng->txt('crs_password_not_valid');
                return false;
            }
        }
        if (!$this->validateCustomFields()) {
            $this->join_error = $this->lng->txt('fill_out_all_required_fields');
            return false;
        }
        if (!$this->validateAgreement()) {
            $this->join_error = $this->lng->txt('crs_agreement_required');
            return false;
        }

        return true;
    }

    // fau: heavySub - avoid failures on heavy concurrency
    // fau: fairSub#44 - add subscription requests and requests in fair time to waiting list
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

        $this->setAccepted(true);

        // perform the registration (result determines the next action)
        $this->registration->doRegistration(ilUtil::stripSlashes((string) $_POST['subject']), (array) $_POST['group_ref_ids'], (int) $_POST['selected_module']);

        // get the link to the upper container
        $this->ctrl->setParameterByClass("ilrepositorygui", "ref_id",
            $DIC->repositoryTree()->getParentId($this->container->getRefId())
        );

        switch ($this->registration->getRegistrationAction()) {
            case Registration::notifyAdded:
                if (!isset($_SESSION["pending_goto"]) || !$_SESSION["pending_goto"]) {
                    $this->tpl->setOnScreenMessage('success', $this->lng->txt("crs_subscription_successful"), true);
                    $this->ctrl->returnToParent($this);
                } else {
                    $tgt = $_SESSION["pending_goto"];
                    unset($_SESSION["pending_goto"]);
                    ilUtil::redirect($tgt);
                }
                break;

            case Registration::notifyAddedToWaitingList:
                $info = sprintf($this->lng->txt('sub_added_to_waiting_list'), $this->getWaitingList()->getPositionInfo($DIC->user()->getId()));
                $this->tpl->setOnScreenMessage('success', $info, true);
                $this->ctrl->redirectByClass("ilrepositorygui");
                break;

            case Registration::showAddedToWaitingListFair:
                $this->tpl->setOnScreenMessage('success', $this->lng->txt("sub_fair_added_to_waiting_list"), true);
                $this->ctrl->redirectByClass("ilrepositorygui");
                break;

            case Registration::showUpdatedWaitingList:
                $this->tpl->setOnScreenMessage('success', $this->lng->txt('sub_request_saved'), true);
                $this->ctrl->redirectByClass("ilrepositorygui");
                break;

            case Registration::showLimitReached:
                $this->tpl->setOnScreenMessage('success', $this->lng->txt("crs_reg_limit_reached"), true);
                $this->ctrl->redirectByClass("ilrepositorygui");
                break;

            case Registration::showAlreadyMember:
                $this->tpl->setOnScreenMessage('info', $this->lng->txt("crs_reg_user_already_assigned"), true);
                $this->ctrl->redirectByClass("ilrepositorygui");
                break;

            case Registration::showGenericFailure:
                $this->tpl->setOnScreenMessage('failure', $this->lng->txt("crs_reg_user_generic_failure"), true);
                $this->ctrl->redirectByClass("ilrepositorygui");
                break;
        }
    }
    // fau.
    

    protected function initParticipants(): ilParticipants
    {
        $this->participants = ilCourseParticipants::_getInstanceByObjId($this->obj_id);
        return $this->participants;
    }

    protected function initWaitingList(): ilWaitingList
    {
        $this->waiting_list = new ilCourseWaitingList($this->container->getId());
        return $this->waiting_list;
    }

    protected function isWaitingListActive(): bool
    {
        static $active = null;

        if ($active !== null) {
            return $active;
        }
        if (!$this->container->enabledWaitingList() || !$this->container->isSubscriptionMembershipLimited()) {
            return $active = false;
        }
        if (!$this->container->getSubscriptionMaxMembers()) {
            return $active = false;
        }

        $free = max(0, $this->container->getSubscriptionMaxMembers() - $this->participants->getCountMembers());
        return $active = (!$free || $this->getWaitingList()->getCountUsers());
    }
}
