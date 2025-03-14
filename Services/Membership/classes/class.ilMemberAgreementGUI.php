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

use ILIAS\HTTP\GlobalHttpState;
use ILIAS\Refinery\Factory;

/**
 * @author       Stefan Meyer <meyer@leifos.com>
 * @ilCtrl_Calls ilMemberAgreementGUI:
 * @ingroup      ModulesCourse
 */
class ilMemberAgreementGUI
{
    private int $ref_id;
    private int $obj_id;
    private string $type;
    protected GlobalHttpState $http;
    protected Factory $refinery;
    private ilCtrlInterface $ctrl;
    private ilLanguage $lng;
    private ilGlobalTemplateInterface $tpl;
    private ilObjUser $user;
    private ilPrivacySettings $privacy;
    private ilMemberAgreement $agreement;
    private bool $required_fullfilled = false;
    private bool $agreement_required = false;

    public function __construct(int $a_ref_id)
    {
        global $DIC;

        $this->http = $DIC->http();
        $this->refinery = $DIC->refinery();

        $this->ref_id = $a_ref_id;
        $this->obj_id = ilObject::_lookupObjId($this->ref_id);
        $this->type = ilObject::_lookupType($this->obj_id);
        $this->ctrl = $DIC->ctrl();
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->lng = $DIC->language();
        $this->lng->loadLanguageModule('ps');
        $this->user = $DIC->user();

        $this->privacy = ilPrivacySettings::getInstance();
        $this->agreement = new ilMemberAgreement($DIC->user()->getId(), $this->obj_id);
        $this->init();
    }

    public function executeCommand(): void
    {
        $next_class = $this->ctrl->getNextClass($this);
        $cmd = $this->ctrl->getCmd();

        switch ($next_class) {
            default:
                if (!$cmd || $cmd === 'view') {
                    $cmd = 'showAgreement';
                }
                $this->$cmd();
                break;
        }
    }

    public function getPrivacy(): ilPrivacySettings
    {
        return $this->privacy;
    }

    public function getAgreement(): ilMemberAgreement
    {
        return $this->agreement;
    }

    /**
     * Show agreement form
     */
    protected function showAgreement(?ilPropertyFormGUI $form = null): void
    {
        if ($form === null) {
            $form = $this->initFormAgreement();
            self::setCourseDefinedFieldValues($form, $this->obj_id, $this->user->getId());
        }
        $this->tpl->setContent($form->getHTML());
    }

    protected function initFormAgreement(): ilPropertyFormGUI
    {
        $form = new ilPropertyFormGUI();
        $form->setTitle($this->lng->txt($this->type . '_agreement_header'));
        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->addCommandButton('save', $this->lng->txt('save'));

        $form = self::addExportFieldInfo($form, $this->obj_id, $this->type);
        $form = self::addCustomFields($form, $this->obj_id, $this->type);

        if ($this->getPrivacy()->confirmationRequired($this->type)) {
            $form = self::addAgreement($form, $this->obj_id, $this->type);
        }
        return $form;
    }

    public static function addExportFieldInfo(
        ilPropertyFormGUI $form,
        int $a_obj_id,
        string $a_type
    ): ilPropertyFormGUI {
        global $DIC;

        $lng = $DIC->language();

        $fields_info = ilExportFieldsInfo::_getInstanceByType(ilObject::_lookupType($a_obj_id));

        $fields = new ilCustomInputGUI($lng->txt($a_type . '_user_agreement'), '');
        $tpl = new ilTemplate('tpl.agreement_form.html', true, true, 'Services/Membership');
        $tpl->setVariable('TXT_INFO_AGREEMENT', $lng->txt($a_type . '_info_agreement'));
        foreach ($fields_info->getExportableFields() as $field) {
            $tpl->setCurrentBlock('field_item');
            $tpl->setVariable('FIELD_NAME', $lng->txt($field));
            $tpl->parseCurrentBlock();
        }

        // #17609 - not part of ilExportFieldsInfo::getExportableFields()
        // see ilExportFieldsInfo::getSelectableFieldsInfo()
        foreach (ilUserDefinedFields::_getInstance()->getExportableFields($a_obj_id) as $field) {
            $tpl->setCurrentBlock('field_item');
            $tpl->setVariable('FIELD_NAME', $field['field_name']);
            $tpl->parseCurrentBlock();
        }
        $fields->setHtml($tpl->get());
        $form->addItem($fields);
        return $form;
    }

    public static function addAgreement(ilPropertyFormGUI $form, int $a_obj_id, string $a_type): ilPropertyFormGUI
    {
        global $DIC;

        $lng = $DIC['lng'];

        $agreement = new ilCheckboxInputGUI($lng->txt($a_type . '_agree'), 'agreement');
        $agreement->setRequired(true);
        $agreement->setOptionTitle($lng->txt($a_type . '_info_agree'));
        $agreement->setValue('1');
        $form->addItem($agreement);

        return $form;
    }

    // fau: courseUdf - add custom fields without parent directly
        public static function addCustomFields(
        ilPropertyFormGUI $form,
        int $a_obj_id,
        string $a_type,
        string $a_mode = 'user'
    ): ilPropertyFormGUI {
        global $DIC;

        $lng = $DIC['lng'];

        if (!count($cdf_fields = ilCourseDefinedFieldDefinition::_getFields($a_obj_id))) {
            return $form;
        }
        if ($a_mode == 'user') {
            $cdf = new ilNonEditableValueGUI($lng->txt('ps_' . $a_type . '_user_fields'));
            $cdf->setValue($lng->txt($a_type . '_ps_cdf_info'));
            $cdf->setRequired(true);
        }

        /** @var ilCourseDefinedFieldDefinition $field_obj */
        foreach ($cdf_fields as $field_obj) {
            if (empty($field_obj->getParentFieldId())) {
                $field_gui = self::getCustomFieldGUI($field_obj, $cdf_fields);

                if ($a_mode == 'user') {
                    $cdf->addSubItem($field_gui);
                } else {
                    $form->addItem($field_gui);
                }
            }
        }

        if ($a_mode == 'user') {
            $form->addItem($cdf);
        }
        return $form;
    }
    // fau.    
    // fau: courseUdf - new function getCustomFieldGUI()
    /**
     * Get the property form gui for a custom field
     * This will add sub fields to the select fields
     *
     * @param ilCourseDefinedFieldDefinition $field_obj
     * @param ilCourseDefinedFieldDefinition[] $cdf_fields
     * @return ilFormPropertyGUI
     */
    public static function getCustomFieldGUI($field_obj, $cdf_fields)
    {
        global $lng;

        switch ($field_obj->getType()) {
                case ilCourseDefinedFieldDefinition::IL_CDF_TYPE_SELECT:

                    $sub_fields = [];
                    foreach ($cdf_fields as $sub_field) {
                        if ($sub_field->getParentFieldId() == $field_obj->getId()) {
                            $sub_fields[$sub_field->getParentValueId()][] = $sub_field;
                        }
                    }

                    if ($field_obj->getValueOptions() || !empty($sub_field)) {
                        // Show as radio group
                        $option_radios = new ilRadioGroupInputGUI($field_obj->getName(), 'cdf_' . $field_obj->getId());
                        $option_radios->setInfo($field_obj->getDescription());
                        if ($field_obj->isRequired()) {
                            $option_radios->setRequired(true);
                        }
                        
                        $open_answer_indexes = (array) $field_obj->getValueOptions();
                        foreach ($field_obj->getValues() as $key => $val) {
                            $option_radio = new ilRadioOption($val, $field_obj->getId() . '_' . $key);
                            
                            // open answers
                            if (in_array($key, $open_answer_indexes)) {
                                $open_answer = new ilTextInputGUI($lng->txt("form_open_answer"), 'cdf_oa_' . $field_obj->getId() . '_' . $key);
                                $open_answer->setRequired(true);
                                $option_radio->addSubItem($open_answer);
                            }

                            // sub fields for the radio option
                            if (!empty($sub_fields[$key])) {
                                foreach ($sub_fields[$key] as $sub_field) {
                                    $sub_gui = self::getCustomFieldGUI($sub_field, $cdf_fields);
                                    $option_radio->addSubItem($sub_gui);
                                }
                            }

                            $option_radios->addOption($option_radio);
                        }
                        return $option_radios;
                    } else {
                        // Show as select box
                        $select = new ilSelectInputGUI($field_obj->getName(), 'cdf_' . $field_obj->getId());
                        $select->setInfo($field_obj->getDescription());
                        $select->setOptions($field_obj->prepareSelectBox());
                        if ($field_obj->isRequired()) {
                            $select->setRequired(true);
                        }
                        return $select;
                    }
                    break;

                case ilCourseDefinedFieldDefinition::IL_CDF_TYPE_TEXT:
                    $text = new ilTextInputGUI($field_obj->getName(), 'cdf_' . $field_obj->getId());
                    $text->setInfo($field_obj->getDescription());
                    $text->setSize(32);
                    $text->setMaxLength(255);
                    if ($field_obj->isRequired()) {
                        $text->setRequired(true);
                    }
                    return $text;


                case ilCourseDefinedFieldDefinition::IL_CDF_TYPE_EMAIL:
                    $email = new ilEMailInputGUI($field_obj->getName(), 'cdf_' . $field_obj->getId());
                    $email->setInfo($field_obj->getDescription());
                    $email->setSize(32);
                    $email->setMaxLength(255);
                    if ($field_obj->isRequired()) {
                        $email->setRequired(true);
                    }
                    return $email;

                case ilCourseDefinedFieldDefinition::IL_CDF_TYPE_CHECKBOX:
                    $checkbox = new ilCheckboxInputGUI($field_obj->getName(), 'cdf_' . $field_obj->getId());
                    $checkbox->setInfo($field_obj->getDescription());
                    $checkbox->setValue((string) 1);
                    if ($field_obj->isRequired()) {
                        $checkbox->setCheckRequired(true);
                        $checkbox->setRequired(true);
                    }
                    return $checkbox;
            }
    }
    // fau.


    private function save(): bool
    {
        $form = $this->initFormAgreement();

        // #14715 - checkInput() does not work for checkboxes
        if ($this->checkAgreement() && $form->checkInput()) {
            self::saveCourseDefinedFields($form, $this->obj_id);

            $this->getAgreement()->setAccepted(true);
            $this->getAgreement()->setAcceptanceTime(time());
            $this->getAgreement()->save();

            $history = new ilObjectCustomUserFieldHistory($this->obj_id, $this->user->getId());
            $history->setUpdateUser($this->user->getId());
            $history->setEditingTime(new ilDateTime(time(), IL_CAL_UNIX));
            $history->save();

            $this->ctrl->returnToParent($this);
        } elseif (!$this->checkAgreement()) {
            $this->tpl->setOnScreenMessage('failure', $this->lng->txt($this->type . '_agreement_required'));
            $form->setValuesByPost();
            $this->showAgreement($form);
            return false;
        } else {
            $this->tpl->setOnScreenMessage('failure', $this->lng->txt('fill_out_all_required_fields'));
            $form->setValuesByPost();
            $this->showAgreement($form);
            return false;
        }
        return true;
    }

    public static function setCourseDefinedFieldValues(
        ilPropertyFormGUI $form,
        int $a_obj_id,
        int $a_usr_id = 0
    ): void {
        global $DIC;

        $ilUser = $DIC['ilUser'];

        if (!$a_usr_id) {
            $a_usr_id = $ilUser->getId();
        }

        $ud = ilCourseUserData::_getValuesByObjId($a_obj_id);

        foreach (ilCourseDefinedFieldDefinition::_getFields($a_obj_id) as $field_obj) {
            $current_value = (string) ($ud[$a_usr_id][$field_obj->getId()] ?? '');
            if (!$current_value) {
                continue;
            }

            switch ($field_obj->getType()) {
                case ilCourseDefinedFieldDefinition::IL_CDF_TYPE_SELECT:

                    $id = $field_obj->getIdByValue($current_value);

                    if ($id >= 0) {
                        $item = $form->getItemByPostVar('cdf_' . $field_obj->getId());
                        $item->setValue($field_obj->getId() . '_' . $id);
                    } else {
                        // open answer
                        $open_answer_indexes = $field_obj->getValueOptions();
                        $open_answer_index = end($open_answer_indexes);
                        $item = $form->getItemByPostVar('cdf_' . $field_obj->getId());
                        $item->setValue($field_obj->getId() . '_' . $open_answer_index);
                        $item_txt = $form->getItemByPostVar('cdf_oa_' . $field_obj->getId() . '_' . $open_answer_index);
                        if ($item_txt) {
                            $item_txt->setValue($current_value);
                        }
                    }
                    break;

                case ilCourseDefinedFieldDefinition::IL_CDF_TYPE_TEXT:
                    $item = $form->getItemByPostVar('cdf_' . $field_obj->getId());
                    $item->setValue($current_value);
                    break;
                // fau: courseUdf - load email and checkbox values
                case ilCourseDefinedFieldDefinition::IL_CDF_TYPE_EMAIL:
                    $item = $form->getItemByPostVar('cdf_' . $field_obj->getId());
                    $item->setValue($current_value);
                    break;

                case ilCourseDefinedFieldDefinition::IL_CDF_TYPE_CHECKBOX:
                    $item = $form->getItemByPostVar('cdf_' . $field_obj->getId());
                    $item->setChecked((bool) $current_value);
                    break;
                // fau.                    
            }
        }
    }

    public static function saveCourseDefinedFields(ilPropertyFormGUI $form, int $a_obj_id, int $a_usr_id = 0): void
    {
        global $DIC;

        $ilUser = $DIC['ilUser'];
        if (!$a_usr_id) {
            $a_usr_id = $ilUser->getId();
        }

        // fau: courseUdf - collect fields by id
        $fields = array();
        /** @var ilCourseDefinedFieldDefinition $field_obj */
        foreach (ilCourseDefinedFieldDefinition::_getFields($a_obj_id) as $field_obj) {
            $fields[$field_obj->getId()] = $field_obj;
        }
        
        foreach ($fields as $field_obj) {
            // fau.
            switch ($field_obj->getType()) {
                case ilCourseDefinedFieldDefinition::IL_CDF_TYPE_SELECT:
                    // Split value id from post
                    $exp = explode('_', $form->getInput('cdf_' . $field_obj->getId()));
                    $field_id = $exp[0];
                    $option_id = $exp[1] ?? null;
                    $open_answer_indexes = $field_obj->getValueOptions();
                    if (in_array($option_id, $open_answer_indexes)) {
                        $value = $form->getInput('cdf_oa_' . $field_obj->getId() . '_' . $option_id);
                    } else {
                        $value = $field_obj->getValueById((int) $option_id);
                    }
                    break;

                case ilCourseDefinedFieldDefinition::IL_CDF_TYPE_TEXT:
                    $value = $form->getInput('cdf_' . $field_obj->getId());
                    break;
// fau: courseUdf - save email and checkbox value from agreement
                case ilCourseDefinedFieldDefinition::IL_CDF_TYPE_EMAIL:
                        $value = $form->getInput('cdf_' . $field_obj->getId());
                        break;
    
                case ilCourseDefinedFieldDefinition::IL_CDF_TYPE_CHECKBOX:
                        $value = $form->getInput('cdf_' . $field_obj->getId());
                        break;
// fau.                    
            }

            // fau: courseUdf - clear value if parent option is not selected
            if (isset($fields[$field_obj->getParentFieldId()])) {
                list($field_id, $option_id) = explode('_', $form->getInput('cdf_' . $field_obj->getParentFieldId()));
                if (empty($field_id) || $option_id != $field_obj->getParentValueId()) {
                    $value = "";
                }
            }
            // fau.
            $course_user_data = new ilCourseUserData($a_usr_id, $field_obj->getId());
            $course_user_data->setValue($value);
            $course_user_data->update();
        }
    }

    private function checkAgreement(): bool
    {
        $agreement = false;
        if ($this->http->wrapper()->post()->has('agreement')) {
            $agreement = $this->http->wrapper()->post()->retrieve(
                'agreement',
                $this->refinery->kindlyTo()->bool()
            );
        }
        if ($agreement) {
            return true;
        }
        if ($this->privacy->confirmationRequired($this->type)) {
            return false;
        }
        return true;
    }

    private function init(): void
    {
        $this->required_fullfilled = ilCourseUserData::_checkRequired($this->user->getId(), $this->obj_id);
        $this->agreement_required = $this->getAgreement()->agreementRequired();
    }

    private function sendInfoMessage(): void
    {
        $message = '';
        if ($this->agreement_required) {
            $message = $this->lng->txt($this->type . '_ps_agreement_req_info');
        }
        if (!$this->required_fullfilled) {
            if ($message !== '') {
                $message .= '<br />';
            }
            $message .= $this->lng->txt($this->type . '_ps_required_info');
        }
        if ($message !== '') {
            $this->tpl->setOnScreenMessage('failure', $message);
        }
    }
}
