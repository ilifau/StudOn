<?php

declare(strict_types=1);

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
// fau: paraSub - import of registration class
// fau: fairSub#53 - import of registration class
use FAU\Ilias\Registration;
// fau.

/**
 * Base class for Course and Group registration
 * @author  Stefan Meyer <smeyer.ilias@gmx.de>
 * @ingroup ServicesRegistration
 */
abstract class ilRegistrationGUI
{
    protected int $ref_id;
    protected int $obj_id;
    protected string $type;
    protected string $join_error = '';
    protected bool $registration_possible = true;
    protected \ILIAS\HTTP\GlobalHttpState $http;
    protected \ILIAS\Refinery\Factory $refinery;
    protected ilPrivacySettings $privacy;
    protected ilObject $container;
    protected ilParticipants $participants;
    protected ilWaitingList $waiting_list;
    protected ?ilPropertyFormGUI $form = null;
    protected ilObjUser $user;
    protected ilTabsGUI $tabs;
    protected ilTree $tree;
    protected ilRbacReview $rbacreview;
    protected ilGlobalTemplateInterface $tpl;
    protected ilLanguage $lng;
    protected ilCtrl $ctrl;
    protected ilAccessHandler $access;

    // fau: paraSub - property for registration object
    // fau: fairSub#52 - property for registration object
    protected Registration $registration;
    // fau.

    // fau: studyCond - class variables
    protected $matches_studycond = true;
    protected $describe_studycond = "";
    // fau.

    // fau: campoCheck - class vatiable
    protected $matches_restrictions = true;
    // fau.    

    public function __construct(ilObject $a_container)
    {
        global $DIC;

        $this->user = $DIC->user();
        $this->tabs = $DIC->tabs();
        $this->tree = $DIC->repositoryTree();
        $this->rbacreview = $DIC->rbac()->review();

        $this->lng = $DIC->language();
        $this->lng->loadLanguageModule('crs');
        $this->lng->loadLanguageModule('grp');
        $this->lng->loadLanguageModule('ps');
        $this->lng->loadLanguageModule('membership');

        $this->ctrl = $DIC->ctrl();
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->access = $DIC->access();

        $this->container = $a_container;
        $this->ref_id = $this->container->getRefId();
        $this->obj_id = ilObject::_lookupObjId($this->ref_id);
        $this->type = ilObject::_lookupType($this->obj_id);

        // fau: studyCond - define matches_studycond, describe_studycond
        $this->has_studycond = $DIC->fau()->cond()->repo()->checkObjectHasSoftCondition($this->obj_id);
        if ($this->has_studycond) {
            $this->matches_studycond = $DIC->fau()->cond()->soft()->check($this->obj_id, $DIC->user()->getId());
            $this->describe_studycond = $DIC->fau()->cond()->soft()->getConditionsAsText($this->obj_id);
        } else {
            $this->matches_studycond = true;
            $this->describe_studycond = "";
        }
        // fau.        
        $this->initParticipants();
        $this->initWaitingList();

        // fau: paraSub - init the registration object
        // fau: fairSub#59 - init the registration object
        $this->registration = $DIC->fau()->ilias()->getRegistration($this->container, $this->participants, $this->waiting_list);
        // fau.        
        $this->privacy = ilPrivacySettings::getInstance();
        $this->http = $DIC->http();
        $this->refinery = $DIC->refinery();
    }

    // fau: studyCond - adjust the subscription type based on soft conditions
    // fau: campoCheck - adjust the subscription type based on soft conditions
    protected function adjustSubType()
    {
        // implement in child classes
    }
    // fau.
    
    public function getContainer(): ilObject
    {
        return $this->container;
    }

    public function getRefId(): int
    {
        return $this->ref_id;
    }

    protected function isRegistrationPossible(): bool
    {
        return $this->registration_possible;
    }

    protected function enableRegistration(bool $a_status): void
    {
        $this->registration_possible = $a_status;
    }

    /**
     * Init participants object (course or group participants)
     */
    abstract protected function initParticipants(): ilParticipants;

    /**
     * Init waiting list (course or group waiting list)
     */
    abstract protected function initWaitingList(): ilWaitingList;

    /**
     * Check if the waiting list is active
     * Maximum of members exceeded or
     * any user on the waiting list
     */
    abstract protected function isWaitingListActive(): bool;

    /**
     * Get waiting list object
     */
    protected function getWaitingList(): ilWaitingList
    {
        return $this->waiting_list;
    }

    protected function leaveWaitingList(): void
    {
        // fau: paraSub - call new function to remove a user from the waiting list
        // fau: fairSub#60 - call new function to remove a user from the waiting list
        global $DIC;
        $this->registration->removeUserSubscription($DIC->user()->getId());
        // fau.
        // fau: fairSub#54 - trigger filling a course
        $this->registration->doAutoFill();
        // fau.        
        $parent = $this->tree->getParentId($this->container->getRefId());

        $message = sprintf(
            $this->lng->txt($this->container->getType() . '_removed_from_waiting_list'),
            $this->container->getTitle()
        );
        $this->tpl->setOnScreenMessage('success', $message, true);
        $this->ctrl->setParameterByClass("ilrepositorygui", "ref_id", $parent);
        $this->ctrl->redirectByClass("ilrepositorygui", "");
    }

    /**
     * Get title for property form
     */
    abstract protected function getFormTitle(): string;

    /**
     * fill informations
     */
    abstract protected function fillInformations(): void;

    /**
     * show informations about the registration period
     */
    abstract protected function fillRegistrationPeriod(): void;

    /**
     * show informations about the maximum number of user.
     */
    abstract protected function fillMaxMembers(): void;

    /**
     * show informations about registration procedure
     */
    abstract protected function fillRegistrationType(): void;
    
    // fau: objectSub - new function fillRegistrationTypeObject()
    protected function fillRegistrationTypeObject($a_ref_id)
    {
        require_once('Services/Link/classes/class.ilLink.php');
        $obj_id = ilObject::_lookupObjId($a_ref_id);
        $link = ilLink::_getLink($a_ref_id);

        require_once('Services/Locator/classes/class.ilLocatorGUI.php');
        $locator = new ilLocatorGUI();
        $locator->addRepositoryItems($a_ref_id);

        $tpl = new ilTemplate('tpl.sub_object_link.html', true, true, 'Services/Membership');
        $tpl->setVariable('TXT_INFO', $this->lng->txt('sub_separate_object_reg_info'));
        $tpl->setVariable('IMG_TYPE', ilObject::_getIcon($obj_id, 'small'));
        $tpl->setVariable('URL_OBJECT', $link);
        $tpl->setVariable('TITLE_OBJECT', ilObject::_lookupTitle($obj_id));
        $tpl->setVariable('TXT_PATH', $locator->getTextVersion());

        $input = new ilCustomInputGUI($this->lng->txt('mem_reg_type'));
        $input->setHtml($tpl->get());
        $this->form->addItem($input);

        // Disable registration
        $this->enableRegistration(false);
        return true;
    }
    // fau.    

    /**
     * Show membership limitations
     */
    protected function fillMembershipLimitation(): void
    {
        if (!$items = ilObjCourseGrouping::_getGroupingItems($this->container)) {
            return;
        }
        $mem = new ilCustomInputGUI($this->lng->txt('groupings'));
        $tpl = new ilTemplate('tpl.membership_limitation_form.html', true, true, 'Services/Membership');
        $tpl->setVariable('LIMIT_INTRO', $this->lng->txt($this->type . '_grp_info_reg'));
        foreach ($items as $ref_id) {
            $obj_id = ilObject::_lookupObjId($ref_id);
            $type = ilObject::_lookupType($obj_id);
            $title = ilObject::_lookupTitle($obj_id);

            if ($this->access->checkAccess('visible', '', $ref_id, $type)) {
                $this->ctrl->setParameterByClass("ilrepositorygui", "ref_id", $ref_id);
                $tpl->setVariable(
                    'LINK_ITEM',
                    $this->ctrl->getLinkTargetByClass("ilrepositorygui", "")
                );
                $get_ref_id = $this->http->wrapper()->query()->retrieve(
                    'ref_id',
                    $this->refinery->byTrying([
                        $this->refinery->kindlyTo()->int(),
                        $this->refinery->always(0)
                    ])
                );

                $this->ctrl->setParameterByClass("ilrepositorygui", "ref_id", $get_ref_id);
                $tpl->setVariable('ITEM_LINKED_TITLE', $title);
            } else {
                $tpl->setVariable('ITEM_TITLE');
            }
            $tpl->setCurrentBlock('items');
            $tpl->setVariable('TYPE_ICON', ilObject::_getIcon($obj_id, 'tiny', $type));
            $tpl->setVariable('ALT_ICON', $this->lng->txt('obj_' . $type));
            $tpl->parseCurrentBlock();
        }
        $mem->setHtml($tpl->get());
        if (!ilObjCourseGrouping::_checkGroupingDependencies($this->container)) {
            $mem->setAlert($this->container->getMessage());
            $this->enableRegistration(false);
        }
        $this->form->addItem($mem);
    }

    protected function fillAgreement(): void
    {
        if (!$this->isRegistrationPossible()) {
            return;
        }

        if (!$this->privacy->confirmationRequired($this->type) && !ilCourseDefinedFieldDefinition::_hasFields($this->container->getId())) {
            return;
        }

        $this->lng->loadLanguageModule('ps');

        $fields_info = ilExportFieldsInfo::_getInstanceByType(ilObject::_lookupType($this->container->getId()));

        if (!count($fields_info->getExportableFields())) {
            return;
        }
        $section = new ilFormSectionHeaderGUI();
        $section->setTitle($this->lng->txt($this->type . '_usr_agreement'));
        $this->form->addItem($section);

        ilMemberAgreementGUI::addExportFieldInfo($this->form, $this->obj_id, $this->type);

        ilMemberAgreementGUI::addCustomFields($this->form, $this->obj_id, $this->type);

        // Checkbox agreement
        if ($this->privacy->confirmationRequired($this->type)) {
            ilMemberAgreementGUI::addAgreement($this->form, $this->obj_id, $this->type);
        }
    }

    protected function showCustomFields(): void
    {
        if (!count($cdf_fields = ilCourseDefinedFieldDefinition::_getFields($this->container->getId()))) {
            return;
        }

        $cdf_values = $this->http->wrapper()->post()->retrieve(
            'cdf',
            $this->refinery->byTrying([
                $this->refinery->kindlyTo()->dictOf(
                    $this->refinery->kindlyTo()->string()
                ),
                $this->refinery->always([])
            ])
        );

        $cdf = new ilNonEditableValueGUI($this->lng->txt('ps_crs_user_fields'));
        $cdf->setValue($this->lng->txt($this->type . '_ps_cdf_info'));
        $cdf->setRequired(true);
        foreach ($cdf_fields as $field_obj) {
            switch ($field_obj->getType()) {
                case ilCourseDefinedFieldDefinition::IL_CDF_TYPE_SELECT:
                    $select = new ilSelectInputGUI($field_obj->getName(), 'cdf[' . $field_obj->getId() . ']');
                    $select->setValue($cdf_values[$field_obj->getId()] ?? '');
                    $select->setOptions($field_obj->prepareSelectBox());
                    if ($field_obj->isRequired()) {
                        $select->setRequired(true);
                    }
                    $cdf->addSubItem($select);
                    break;

                case ilCourseDefinedFieldDefinition::IL_CDF_TYPE_TEXT:
                    $text = new ilTextInputGUI($field_obj->getName(), 'cdf[' . $field_obj->getId() . ']');
                    $text->setValue($cdf_values[$field_obj->getId()] ?? '');
                    $text->setSize(32);
                    $text->setMaxLength(255);
                    if ($field_obj->isRequired()) {
                        $text->setRequired(true);
                    }
                    $cdf->addSubItem($text);
                    break;
            }
        }
        $this->form->addItem($cdf);
    }

    protected function validateAgreement(): bool
    {
        $agreement = $this->http->wrapper()->post()->retrieve(
            'agreement',
            $this->refinery->byTrying([
                $this->refinery->kindlyTo()->string(),
                $this->refinery->always(null)
            ])
        );

        if ($agreement) {
            return true;
        }
        if (!$this->privacy->confirmationRequired($this->type)) {
            return true;
        }
        return false;
    }

    protected function validateCustomFields(): bool
    {
        $required_fullfilled = true;
        $value = '';
        foreach (ilCourseDefinedFieldDefinition::_getFields($this->container->getId()) as $field_obj) {
            switch ($field_obj->getType()) {
                case ilCourseDefinedFieldDefinition::IL_CDF_TYPE_SELECT:
                    $cdf_value = $this->http->wrapper()->post()->retrieve(
                        'cdf_' . $field_obj->getId(),
                        $this->refinery->byTrying([
                            $this->refinery->kindlyTo()->string(),
                            $this->refinery->always('')
                        ])
                    );

                    // Split value id from post
                    $cdf_parts = explode('_', $cdf_value);
                    $option_id = (int) ($cdf_parts[1] ?? 0);

                    $open_answer_indexes = $field_obj->getValueOptions();
                    if (in_array($option_id, $open_answer_indexes)) {
                        $value = $this->http->wrapper()->post()->retrieve(
                            'cdf_oa_' . $field_obj->getId() . '_' . $option_id,
                            $this->refinery->byTrying([
                                $this->refinery->kindlyTo()->string(),
                                $this->refinery->always('')
                            ])
                        );
                    } else {
                        $value = $field_obj->getValueById((int) $option_id);
                    }
                    break;

                case ilCourseDefinedFieldDefinition::IL_CDF_TYPE_TEXT:
                    $value = $this->http->wrapper()->post()->retrieve(
                        'cdf_' . $field_obj->getId(),
                        $this->refinery->byTrying([
                            $this->refinery->kindlyTo()->string(),
                            $this->refinery->always('')
                        ])
                    );
                    break;
            }

            $course_user_data = new ilCourseUserData($this->user->getId(), $field_obj->getId());
            $course_user_data->setValue($value);
            $course_user_data->update();

            // #14220
            if ($field_obj->isRequired() && $value === "") {
                $required_fullfilled = false;
            }
        }
        return $required_fullfilled;
    }

    protected function setAccepted(bool $a_status): void
    {
        if (!$this->privacy->confirmationRequired($this->type) && !ilCourseDefinedFieldDefinition::_hasFields($this->container->getId())) {
            return;
        }

        $agreement = new ilMemberAgreement($this->user->getId(), $this->container->getId());
        $agreement->setAccepted($a_status);
        $agreement->setAcceptanceTime(time());
        $agreement->save();
    }

    /**
     * cancel subscription
     */
    public function cancel(): void
    {
        $this->ctrl->setParameterByClass(
            "ilrepositorygui",
            "ref_id",
            $this->tree->getParentId($this->container->getRefId())
        );
        $this->ctrl->redirectByClass("ilrepositorygui", "");
    }

    public function show(?ilPropertyFormGUI $form = null): void
    {
        if (!$form instanceof ilPropertyFormGUI) {
            $this->initForm();
        }
        $pending_goto = (string) ilSession::get('pending_goto');
        if ($pending_goto) {
            $this->tpl->setOnScreenMessage('info', $this->lng->txt("reg_goto_parent_membership_info"));
        }
        $this->tpl->setContent($this->form->getHTML());
    }

    public function join(): void
    {
        $form = $this->initForm();
        if (!$form->checkInput() || !$this->validate()) {
            $form->setValuesByPost();
            if ($this->join_error) {
                $this->tpl->setOnScreenMessage('failure', $this->join_error);
            } else {
                $this->tpl->setOnScreenMessage('failure', $this->lng->txt('err_check_input'));
            }
            $this->show($form);
            return;
        }
        $this->add();
    }

    protected function validate(): bool
    {
        return true;
    }

    /**
     * @todo get rid $this->form
     */
    protected function initForm(): ilPropertyFormGUI
    {
        if (is_object($this->form)) {
            return $this->form;
        }

        $this->form = new ilPropertyFormGUI();
        $this->form->setFormAction($this->ctrl->getFormAction($this, 'join'));
        $this->form->setTitle($this->getFormTitle());

        $this->fillInformations();
        $this->fillMembershipLimitation();
        if ($this->isRegistrationPossible()) {
            $this->fillRegistrationPeriod();
        }
        // fau: fairSub#55 - fill registration type if user is to confirm on waiting list
        if ($this->isRegistrationPossible()
            || $this->participants->isSubscriber($this->user->getId())
            || $this->getWaitingList()->isToConfirm($this->user->getId())) {
            // fau.
            $this->fillRegistrationType();
        }
        if ($this->isRegistrationPossible()) {
            $this->fillMaxMembers();
        }
        if ($this->isRegistrationPossible()) {
            $this->fillAgreement();
        }
        $this->addCommandButtons();
        return $this->form;
    }

    /**
     * @todo get rid of $this->form
     */
    protected function addCommandButtons(): void
    {
        // fau: fairSub#56 - use prepared join button text if existing
        if ($this->isRegistrationPossible() && !$this->getWaitingList()->isOnList($this->user->getId())) {
            $this->form->addCommandButton('join', $this->lng->txt('mem_register'));
            $this->form->addCommandButton('cancel', $this->lng->txt('cancel'));
        }
        // fau.

        if ($this->getWaitingList()->isOnList($this->user->getId())) {
            // fau: fairSub#57 - allow to update the subscription_request
            if ($this->getWaitingList()->isToConfirm($this->user->getId())) {
                ilUtil::sendQuestion($this->lng->txt('mem_user_already_subscribed'));
                $this->form->addCommandButton('updateWaitingList', $this->lng->txt('crs_update_subscr_request'));
            }
            // fau: paraSub - allow to change the group selection
            elseif ($this->container->hasParallelGroups()) {
                $this->form->addCommandButton('updateWaitingList', $this->lng->txt('mem_edit_request'));
            }
            // fau.
            $this->form->addCommandButton('leaveWaitingList', $this->lng->txt('leave_waiting_list'));
            $this->form->addCommandButton('cancel', $this->lng->txt('cancel'));
        }
    }

    protected function updateSubscriptionRequest(): void
    {
        $subject = $this->http->wrapper()->post()->retrieve(
            'subject',
            $this->refinery->byTrying([
                $this->refinery->kindlyTo()->string(),
                $this->refinery->always('')
            ])
        );

        $this->participants->updateSubject($this->user->getId(), ilUtil::stripSlashes($subject));
        $this->tpl->setOnScreenMessage('success', $this->lng->txt('sub_request_saved'), true);
        $this->ctrl->setParameterByClass(
            "ilrepositorygui",
            "ref_id",
            $this->tree->getParentId($this->container->getRefId())
        );
        $this->ctrl->redirectByClass("ilrepositorygui", "");
    }

    // fau: fairSub#58 - new function 	updateWaitingList()
    /**
     * Update the subscription message when being on the waiting list
     * @return void
     */
    protected function updateWaitingList()
    {
        global $tree, $ilCtrl;

        // fau: courseUdf - save the user defined values when waiting list is updated
        // fau: paraSub - save group selections
        $this->initForm();
        if ($this->form->checkInput()) {
            include_once './Services/Membership/classes/class.ilMemberAgreementGUI.php';
            ilMemberAgreementGUI::saveCourseDefinedFields($this->form, $this->obj_id);

            // treat update like a join in courses with parallel groups
            // this allows to directly join another group
            if ($this->container->hasParallelGroups()) {
                $this->add();
                return;
            }

            $this->registration->doUpdate(ilUtil::stripSlashes($_POST['subject']), (array) $_POST['group_ref_ids'], (int) $_POST['selected_module']);
            $this->participants->sendExternalNotifications($this->container, $this->user, true);

            $DIC->ui()->mainTemplate()->setOnScreenMessage('success', $this->lng->txt('sub_request_saved'), true);
            $ilCtrl->setParameterByClass(
                "ilrepositorygui",
                "ref_id",
                $tree->getParentId($this->container->getRefId())
            );
            $ilCtrl->redirectByClass("ilrepositorygui", "");
        }
        {
            $this->form->setValuesByPost();
            $this->tpl->setContent($this->form->getHTML());
        }
    }
    // fau.

    protected function cancelSubscriptionRequest(): void
    {
        $this->participants->deleteSubscriber($this->user->getId());
        $this->tpl->setOnScreenMessage('success', $this->lng->txt('sub_request_deleted'), true);

        $this->ctrl->setParameterByClass(
            "ilrepositorygui",
            "ref_id",
            $this->tree->getParentId($this->container->getRefId())
        );
        $this->ctrl->redirectByClass("ilrepositorygui", "");
    }
}
