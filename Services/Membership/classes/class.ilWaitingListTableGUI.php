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
use FAU\Ilias\Helper\WaitingListConstantsHelper;
use FAU\Ilias\Helper\WaitingListTableGUIHelper;
/**
 * GUI class for course/group waiting list
 * @author  Stefan Meyer <smeyer.ilias@gmx.de>
 * @ingroup ServicesMembership
 */
class ilWaitingListTableGUI extends ilTable2GUI
{
    use WaitingListTableGUIHelper;

    protected static ?array $all_columns = null;
    protected static bool $has_odf_definitions;
    protected array $wait = [];
    protected array $wait_user_ids = [];
    protected ilObject $rep_object;
    protected ilObjUser $user;
    protected ilWaitingList $waiting_list;

    public function __construct(
        object $a_parent_obj,
        ilObject $rep_object,
        ilWaitingList $waiting_list
    ) {
        global $DIC;

        $this->rep_object = $rep_object;
        $this->user = $DIC->user();

        $this->setId('crs_wait_' . $this->getRepositoryObject()->getId());
        parent::__construct($a_parent_obj, 'participants');
        $this->setFormName('waiting');
        $this->setPrefix('waiting');

        $this->lng->loadLanguageModule('grp');
        $this->lng->loadLanguageModule('crs');
        $this->lng->loadLanguageModule('sess');
        $this->lng->loadLanguageModule('ps');

        $this->setExternalSorting(false);
        $this->setExternalSegmentation(true);

        $this->setFormAction($this->ctrl->getFormAction($a_parent_obj, 'participants'));

        // fau: fairSub#98 - adjust waiting list columns
        $this->addColumn('', 'f', "1", true);
        $this->addColumn($this->lng->txt('name'), 'lastname', '10%');
        $all_cols = $this->getSelectableColumns();
        foreach ($this->getSelectedColumns() as $col) {
            $this->addColumn($all_cols[$col]['txt'], $col);
        }
        $this->addColumn($this->lng->txt('date'), 'sub_time', "15%");
        $this->addColumn($this->lng->txt('status'), 'to_confirm', '10%');

        $this->addColumn('', '', '10%');
        $this->setDefaultOrderField('sub_time');
        // fau.

        // begin-patch clipboard
        $this->lng->loadLanguageModule('user');
        $this->addMultiCommand('addToClipboard', $this->lng->txt('clipboard_add_btn'));
        // end-patch clipboard

        $this->setPrefix('waiting');
        $this->setSelectAllCheckbox('waiting', true);

        $this->setRowTemplate("tpl.show_waiting_list_row.html", "Services/Membership");

        $this->enable('sort');
        $this->enable('header');
        $this->enable('numinfo');
        $this->enable('select_all');

        $this->waiting_list = $waiting_list;

        self::$has_odf_definitions = (bool) ilCourseDefinedFieldDefinition::_hasFields($this->getRepositoryObject()->getId());

        // fau: fairSub#99 - adjust waiting list commands
        if ($DIC->fau()->ilias()->objects()->isRegistrationHandlerSupported($this->getRepositoryObject())) {
            $this->addMultiCommand('confirmAcceptOnList', $this->lng->txt('sub_confirm_requests'));
            $this->addMultiCommand('confirmAssignFromWaitingList', $this->lng->txt('sub_assign_waiting'));
        }
        else {
            $this->addMultiCommand('confirmAssignFromWaitingList', $this->lng->txt('assign'));
        }
        $this->addMultiCommand('confirmRefuseFromList', $this->lng->txt('sub_remove_waiting'));
        $this->addMultiCommand('sendMailToSelectedUsers', $this->lng->txt('crs_mem_send_mail'));

        $this->addToDos();
        // fau.        
    }

    protected function getWaitingList(): ilWaitingList
    {
        return $this->waiting_list;
    }

    protected function getRepositoryObject(): ilObject
    {
        return $this->rep_object;
    }

    /**
     * Set user ids
     * @param int[] $a_user_ids
     */
    public function setUserIds(array $a_user_ids): void
    {
        $this->wait_user_ids = $this->wait = [];
        foreach ($a_user_ids as $usr_id) {
            $this->wait_user_ids[] = $usr_id;
            $this->wait[$usr_id] = $this->getWaitingList()->getUser($usr_id);
        }
    }

    public function numericOrdering(string $a_field): bool
    {
        switch ($a_field) {
            case 'sub_time':
                return true;
        }
        return parent::numericOrdering($a_field);
    }

    public function getSelectableColumns(): array
    {
        if (self::$all_columns) {
            return self::$all_columns;
        }

        $ef = ilExportFieldsInfo::_getInstanceByType($this->getRepositoryObject()->getType());
        self::$all_columns = $ef->getSelectableFieldsInfo($this->getRepositoryObject()->getId());

        // #25215
        if (
            is_array(self::$all_columns) &&
            array_key_exists('consultation_hour', self::$all_columns)
        ) {
            unset(self::$all_columns['consultation_hour']);
        }

        if (
            !is_array(self::$all_columns) ||
            !array_key_exists('login', self::$all_columns)
        ) {
            self::$all_columns['login'] = [
                'default' => 1,
                'txt' => $this->lng->txt('login')
            ];
        }
        // fau: fairSub#100 - add subject column
        self::$all_columns['subject'] = [
            'default' => 1,
            'txt' => $this->lng->txt('message')
        ];
        // fau.        
        return self::$all_columns;
    }

    protected function fillRow(array $a_set): void
    {
        if (
            !ilObjCourseGrouping::_checkGroupingDependencies($this->getRepositoryObject(), (int) $a_set['usr_id']) &&
            ($ids = ilObjCourseGrouping::getAssignedObjects())
        ) {
            $prefix = $this->getRepositoryObject()->getType();
            $this->tpl->setVariable(
                'ALERT_MSG',
                sprintf(
                    $this->lng->txt($prefix . '_lim_assigned'),
                    ilObject::_lookupTitle(current($ids))
                )
            );
        }

        $this->tpl->setVariable('VAL_ID', $a_set['usr_id']);
        // fau: fairSub#101 - show waiting list position
        if(isset($a_set['wait_pos']) )
            $this->tpl->setVariable('VAL_POS', $a_set['wait_pos']);
        // fau.        
        $this->tpl->setVariable('VAL_NAME', $a_set['lastname'] . ', ' . $a_set['firstname']);

        foreach ($this->getSelectedColumns() as $field) {

            // fau: userData - generate cell_id for tooltip
            $cell_id =  rand(1000000,9999999);
            // fau.

            switch ($field) {
                case 'gender':
                    $a_set['gender'] = $a_set['gender'] ? $this->lng->txt('gender_' . $a_set['gender']) : '';
                    $this->tpl->setCurrentBlock('custom_fields');
                    $this->tpl->setVariable('VAL_CUST', $a_set[$field]);
                    $this->tpl->parseCurrentBlock();
                    break;

                case 'birthday':
                    $a_set['birthday'] = $a_set['birthday'] ? ilDatePresentation::formatDate(new ilDate(
                        $a_set['birthday'],
                        IL_CAL_DATE
                    )) : $this->lng->txt('no_date');
                    $this->tpl->setCurrentBlock('custom_fields');
                    $this->tpl->setVariable('VAL_CUST', $a_set[$field]);
                    $this->tpl->parseCurrentBlock();
                    break;

                case 'odf_last_update':
                    $this->tpl->setVariable('VAL_CUST', (string) $a_set['odf_info_txt']);
                    break;

                case 'org_units':
                    $this->tpl->setCurrentBlock('custom_fields');
                    $this->tpl->setVariable(
                        'VAL_CUST',
                        ilOrgUnitPathStorage::getTextRepresentationOfUsersOrgUnits((int) $a_set['usr_id'])
                    );
                    $this->tpl->parseCurrentBlock();
                    break;

                // fau: paraSub - fill module column
                case 'module':
                    $this->tpl->setCurrentBlock('custom_fields');
                    $this->tpl->setVariable('VAL_CUST', (string) $a_set['module']);
                    $this->tpl->parseCurrentBlock();
                    break;
                // fau.

                // fau: campoCheck - fill restrictions column
                case 'restrictions_passed':
                    $this->tpl->setCurrentBlock('custom_fields');
                    $this->tpl->setVariable('VAL_CUST', (string) fauHardRestrictionsGUI::getInstance()->getResultModalLink(
                        $a_set['restrictions'], $a_set['module_id']));
                    $this->tpl->parseCurrentBlock();
                    break;
                    // fau.

                // fau: paraSub - fill parallel groups column
                case 'groups':
                    $this->tpl->setCurrentBlock('custom_fields');
                    $this->tpl->setVariable('VAL_CUST', fauTextViewGUI::getInstance()->showWithModal(
                        nl2br($a_set['groups']),
                        $this->lng->txt('fau_selected_groups_of') . ' ' . $a_set['firstname'] . ' ' . $a_set['lastname'],
                        50
                    ));
                    $this->tpl->parseCurrentBlock();
                    break;
                // fau.

                // fau: paraSub - fill submission message
                case 'subject':
                    $this->tpl->setCurrentBlock('custom_fields');
                    $this->tpl->setVariable('VAL_CUST', fauTextViewGUI::getInstance()->showWithModal(
                        nl2br($a_set['subject']),
                        $this->lng->txt('fau_sub_message_of') . ' ' . $a_set['firstname'] . ' ' . $a_set['lastname'],
                        50
                    ));
                    $this->tpl->parseCurrentBlock();
                    break;
                // fau.

                // fau: userData - format table output of studydata and educations
                case 'studydata':
                    $this->tpl->setCurrentBlock('custom_fields');
                    $this->tpl->setVariable('VAL_CUST', nl2br($a_set['studydata']));
                    $this->tpl->parseCurrentBlock();
                    break;

                case 'educations':
                    //ilTooltipGUI::addTooltip($cell_id, nl2br($a_set['educations']),'','bottom center','top center',false);
                    $this->tpl->setCurrentBlock('custom_fields');
                    //$this->tpl->setVariable('ID_CUST', $cell_id);
                    $this->tpl->setVariable('VAL_CUST', fauTextViewGUI::getInstance()->showWithModal(
                        nl2br($a_set['educations']),
                        $this->lng->txt('fau_educations_of') . ' ' . $a_set['firstname'] . ' ' . $a_set['lastname'],
                        50
                    ));
                    $this->tpl->parseCurrentBlock();
                    break;
                // fau.
                                    
                default:
                    $this->tpl->setCurrentBlock('custom_fields');
                    $this->tpl->setVariable('VAL_CUST', isset($a_set[$field]) ? (string) $a_set[$field] : '');
                    $this->tpl->parseCurrentBlock();
                    break;
            }
        }
        // fau: fairSub #102- show date of registrations in fair time
        $time = ilDatePresentation::formatDate(new ilDateTime($a_set['sub_time'], IL_CAL_UNIX));
        if ($a_set['sub_time'] == $this->getRepositoryObject()->getSubscriptionFair()) {
            $time = '<em>' . sprintf($this->lng->txt('sub_fair_time_before'), $time) . '</em>';
        }
        $this->tpl->setVariable('VAL_SUBTIME', $time);
        // fau.        

        $this->tpl->setVariable(
            'VAL_SUBTIME',
            ilDatePresentation::formatDate(new ilDateTime($a_set['sub_time'], IL_CAL_UNIX))
        );
        $this->showActionLinks($a_set);

        // fau: fairSub#103 - add sinfo about needed confirmation to waiting list
        switch ($a_set['to_confirm']) {
            case WaitingListConstantsHelper::REQUEST_TO_CONFIRM:
                $this->tpl->setVariable('VAL_STATUS', '<b>' . $this->lng->txt('sub_status_request') . '</b>');
                break;
            case WaitingListConstantsHelper::REQUEST_CONFIRMED:
                $this->tpl->setVariable('VAL_STATUS', $this->lng->txt('sub_status_confirmed'));
                break;
            case WaitingListConstantsHelper::REQUEST_NOT_TO_CONFIRM:
            default:
                $this->tpl->setVariable('VAL_STATUS', $this->lng->txt('sub_status_normal'));
        }
        // fau.        
    }

    public function readUserData(): void
    {
        $this->determineOffsetAndOrder();

        $additional_fields = $this->getSelectedColumns();
        unset(
            $additional_fields["firstname"],
            $additional_fields["lastname"],
            $additional_fields["last_login"],
            $additional_fields["access_until"],
            $additional_fields['org_units']
        );
        
        // fau: fairSub#104 - don't query for subject by default
        unset($additional_fields["module"]);
        unset($additional_fields["restrictions_passed"]);
        unset($additional_fields["groups"]);
        unset($additional_fields["subject"]);
        // fau.

        $udf_ids = $usr_data_fields = $odf_ids = array();
        foreach ($additional_fields as $field) {
            if (strpos($field, 'udf') === 0) {
                $udf_ids[] = substr($field, 4);
                continue;
            }
            if (strpos($field, 'odf') === 0) {
                $odf_ids[] = substr($field, 4);
                continue;
            }

            $usr_data_fields[] = $field;
        }

        // fau: userData - add ref_id to filter the list of educations as parameter
        $usr_data = ilUserQuery::getUserListData(
            $this->getOrderField(),
            $this->getOrderDirection(),
            $this->getOffset(),
            $this->getLimit(),
            '',
            '',
            null,
            false,
            false,
            0,
            0,
            null,
            $usr_data_fields,
            $this->wait_user_ids,
            '',
            "",
            $this->getRepositoryObject()->getRefId()
        );
        if (0 === count($usr_data['set']) && $this->getOffset() > 0 && $this->getExternalSegmentation()) {
            $this->resetOffset();

            $usr_data = ilUserQuery::getUserListData(
                $this->getOrderField(),
                $this->getOrderDirection(),
                $this->getOffset(),
                $this->getLimit(),
                '',
                '',
                null,
                false,
                false,
                0,
                0,
                null,
                $usr_data_fields,
                $this->wait_user_ids,
                '',
                "",
                $this->getRepositoryObject()->getRefId()
            );
        }
        $usr_ids = [];
        foreach ((array) $usr_data['set'] as $user) {
            $usr_ids[] = (int) $user['usr_id'];
        }

        // merge course data
        $course_user_data = $this->getParentObject()->readMemberData($usr_ids, array());
        $a_user_data = array();
        foreach ((array) $usr_data['set'] as $ud) {
            $a_user_data[(int) $ud['usr_id']] = array_merge($ud, $course_user_data[(int) $ud['usr_id']]);
        }

        // Custom user data fields
        if ($udf_ids) {
            $data = ilUserDefinedData::lookupData($usr_ids, $udf_ids);
            foreach ($data as $usr_id => $fields) {
                if (!$this->checkAcceptance($usr_id)) {
                    continue;
                }

                foreach ($fields as $field_id => $value) {
                    $a_user_data[$usr_id]['udf_' . $field_id] = $value;
                }
            }
        }
        // Object specific user data fields
        if ($odf_ids) {
            $data = ilCourseUserData::_getValuesByObjId($this->getRepositoryObject()->getId());
            foreach ($data as $usr_id => $fields) {
                // #7264: as we get data for all course members filter against user data
                if (!$this->checkAcceptance($usr_id) || !in_array($usr_id, $usr_ids)) {
                    continue;
                }

                foreach ($fields as $field_id => $value) {
                    $a_user_data[$usr_id]['odf_' . $field_id] = $value;
                }
            }

            // add last edit date
            foreach (ilObjectCustomUserFieldHistory::lookupEntriesByObjectId($this->getRepositoryObject()->getId()) as $usr_id => $edit_info) {
                if (!isset($a_user_data[$usr_id])) {
                    continue;
                }

                if ($usr_id == $edit_info['update_user']) {
                    $a_user_data[$usr_id]['odf_last_update'] = '';
                    $a_user_data[$usr_id]['odf_info_txt'] = $GLOBALS['DIC']['lng']->txt('cdf_edited_by_self');
                    if (ilPrivacySettings::getInstance()->enabledAccessTimesByType($this->getRepositoryObject()->getType())) {
                        $a_user_data[$usr_id]['odf_last_update'] .= ('_' . $edit_info['editing_time']->get(IL_CAL_UNIX));
                        $a_user_data[$usr_id]['odf_info_txt'] .= (', ' . ilDatePresentation::formatDate($edit_info['editing_time']));
                    }
                } else {
                    $a_user_data[$usr_id]['odf_last_update'] = $edit_info['update_user'];
                    $a_user_data[$usr_id]['odf_last_update'] .= ('_' . $edit_info['editing_time']->get(IL_CAL_UNIX));

                    $name = ilObjUser::_lookupName($edit_info['update_user']);
                    $a_user_data[$usr_id]['odf_info_txt'] = ($name['firstname'] . ' ' . $name['lastname'] . ', ' . ilDatePresentation::formatDate($edit_info['editing_time']));
                }
            }
        }

        foreach ($usr_data['set'] as $user) {
            // Check acceptance
            if (!$this->checkAcceptance((int) $user['usr_id'])) {
                continue;
            }
            // DONE: accepted
            foreach ($usr_data_fields as $field) {
                $a_user_data[(int) $user['usr_id']][$field] = $user[$field] ?: '';
            }
        }

        // Waiting list subscription
        foreach ($this->wait as $usr_id => $wait_usr_data) {
            if (isset($a_user_data[$usr_id])) {
                $a_user_data[$usr_id]['sub_time'] = $wait_usr_data['time'];
            }
        }

        // fau: fairSub#105 - add further data to waiting list table
        // fau: paraSub - add selection of parallel groups to waiting list table
        // fau: campoSub - add selected module towaiting list table
        // fau: campoCheck - add restriction info to waiting list table
        global $DIC;
        $group_ids = [];
        if ($this->isColumnSelected('groups')) {
            $groups = $DIC->fau()->ilias()->objects()->getParallelGroupsInfos($this->getRepositoryObject()->getRefId());
            foreach ($groups as $group) {
                $group_ids[] = $group->getObjId();
            }
        }

        // Waiting list subscription
        foreach ($this->wait as $usr_id => $wait_usr_data) {
            if (isset($a_user_data[$usr_id])) {
                $a_user_data[$usr_id]['sub_time'] = $wait_usr_data['time'];
                $a_user_data[$usr_id]['subject'] = $wait_usr_data['subject'];
                $a_user_data[$usr_id]['to_confirm'] = $wait_usr_data['to_confirm'];
                $a_user_data[$usr_id]['module'] = '';
                if (!empty($wait_usr_data['module_id'])) {
                    $a_user_data[$usr_id]['module_id'] = (int) $wait_usr_data['module_id'];
                    foreach($DIC->fau()->study()->repo()->getModules([(int) $wait_usr_data['module_id']]) as $module) {
                        $a_user_data[$usr_id]['module'] =  $module->getLabel();
                    }
                }

                if (!empty($group_ids)) {
                    $titles = [];
                    $subscribed = $DIC->fau()->ilias()->repo()->getSubscribedObjectIds($usr_id, $group_ids);
                    foreach ($groups as $group) {
                        if (in_array($group->getObjId(), $subscribed)) {
                            $titles[] = $group->getTitle();
                        }
                    }
                    $a_user_data[$usr_id]['groups'] =
                        sprintf($this->lng->txt(count($titles) == 1 ? 'fau_selected_groups_1' : 'fau_selected_groups_x'), count($titles)) . ": \n" .
                        implode(", \n", $titles);
                }
                else {
                    $a_user_data[$usr_id]['groups'] = '';
                }

                if ($this->isColumnSelected('restrictions_passed')) {
                    $hard = $DIC->fau()->cond()->hardChecked($this->getRepositoryObject()->getId(), $usr_id);
                    $a_user_data[$usr_id]['restrictions'] = $hard;
                    $a_user_data[$usr_id]['restrictions_passed'] = $hard->getCheckPassed();
                }
            }
        }
        // fau.

        $this->setMaxCount((int) ($usr_data['cnt'] ?? 0));
        $this->setData($a_user_data);
    }

    public function showActionLinks(array $a_set): void
    {
        // fau: fairSub#106 - add options to confirm a subscription
        if (!self::$has_odf_definitions && $a_set['to_confirm'] != 1) {
            $this->ctrl->setParameterByClass(get_class($this->getParentObject()), 'member_id', $a_set['usr_id']);
            $link = $this->ctrl->getLinkTargetByClass(get_class($this->getParentObject()), 'sendMailToSelectedUsers');
            $this->tpl->setVariable('MAIL_LINK', $link);
            $this->tpl->setVariable('MAIL_TITLE', $this->lng->txt('crs_mem_send_mail'));
            $this->ctrl->setParameterByClass(get_class($this->getParentObject()), 'member_id', null);
        }

        // show action menu
        $list = new ilAdvancedSelectionListGUI();
        $list->setSelectionHeaderClass('small');
        $list->setItemLinkClass('small');
        $list->setId('actl_' . $a_set['usr_id'] . '_' . $this->getId());
        $list->setListTitle($this->lng->txt('actions'));

        $this->ctrl->setParameterByClass(get_class($this->getParentObject()), 'member_id', $a_set['usr_id']);
        $this->ctrl->setParameter($this->parent_obj, 'member_id', $a_set['usr_id']);
        $trans = $this->lng->txt($this->getRepositoryObject()->getType() . '_mem_send_mail');
        $link = $this->ctrl->getLinkTargetByClass(get_class($this->getParentObject()), 'sendMailToSelectedUsers');
        $list->addItem($trans, '', $link, 'sendMailToSelectedUsers');

        $this->ctrl->setParameterByClass('ilobjectcustomuserfieldsgui', 'member_id', $a_set['usr_id']);
        $trans = $this->lng->txt($this->getRepositoryObject()->getType() . '_cdf_edit_member');
        $list->addItem($trans, '', $this->ctrl->getLinkTargetByClass('ilobjectcustomuserfieldsgui', 'editMember'));
        $this->ctrl->setParameterByClass(get_class($this->getParentObject()), 'member_id', null);
        $this->tpl->setVariable('ACTION_USER', $list->getHTML());
    }

    protected function checkAcceptance(int $a_usr_id): bool
    {
        return true;
    }
}
