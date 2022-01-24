<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once('Services/PrivacySecurity/classes/class.ilPrivacySettings.php');
include_once('Services/Membership/classes/class.ilMemberAgreement.php');
include_once('Modules/Course/classes/Export/class.ilCourseUserData.php');
include_once('Modules/Course/classes/Export/class.ilCourseDefinedFieldDefinition.php');

/**
*
* @author Stefan Meyer <meyer@leifos.com>
* @version $Id$
*
*
* @ilCtrl_Calls ilMemberAgreementGUI:
* @ingroup ModulesCourse
*/
class ilMemberAgreementGUI
{
    private $ref_id;
    private $obj_id;
    private $type;
    
    private $db;
    private $ctrl;
    private $lng;
    private $tpl;
    
    private $privacy;
    private $agreement;
    
    private $required_fullfilled = false;
    private $agrement_required = false;
    
    /**
     * Constructor
     *
     * @access public
     *
     */
    public function __construct($a_ref_id)
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];
        $ilCtrl = $DIC['ilCtrl'];
        $lng = $DIC['lng'];
        $tpl = $DIC['tpl'];
        $ilUser = $DIC['ilUser'];
        $ilObjDataCache = $DIC['ilObjDataCache'];
        
        $this->ref_id = $a_ref_id;
        $this->obj_id = $ilObjDataCache->lookupObjId($this->ref_id);
        $this->type = ilObject::_lookupType($this->obj_id);
        $this->ctrl = $ilCtrl;
        $this->tpl = $tpl;
        $this->lng = $lng;
        $this->lng->loadLanguageModule('ps');
        
        $this->privacy = ilPrivacySettings::_getInstance();
        $this->agreement = new ilMemberAgreement($ilUser->getId(), $this->obj_id);
        $this->init();
    }
    
    /**
     * Execute Command
     *
     * @access public
     *
     */
    public function executeCommand()
    {
        $next_class = $this->ctrl->getNextClass($this);
        $cmd = $this->ctrl->getCmd();

        switch ($next_class) {
            default:
                if (!$cmd or $cmd == 'view') {
                    $cmd = 'showAgreement';
                }
                $this->$cmd();
                break;
        }
    }

    /**
     * Get privycy settings
     * @return ilPrivacySettings
     */
    public function getPrivacy()
    {
        return $this->privacy;
    }
    
    /**
     * @return ilMemberAgreement
     */
    public function getAgreement()
    {
        return $this->agreement;
    }
    
    /**
     * Show agreement form
     * @param ilPropertyFormGUI $form
     * @return bool
     */
    protected function showAgreement(ilPropertyFormGUI $form = null)
    {
        global $DIC;

        $ilUser = $DIC['ilUser'];
        
        if (!$form instanceof ilPropertyFormGUI) {
            $form = $this->initFormAgreement($form);
            self::setCourseDefinedFieldValues($form, $this->obj_id, $ilUser->getId());
        }
        
        $this->tpl->setContent($form->getHTML());
        return true;
    }
    
    
    
    protected function initFormAgreement()
    {
        include_once './Services/Form/classes/class.ilPropertyFormGUI.php';
        $form = new ilPropertyFormGUI();
        $form->setTitle($this->lng->txt($this->type . '_agreement_header'));
        $form->setFormAction($GLOBALS['DIC']['ilCtrl']->getFormAction($this));
        $form->addCommandButton('save', $this->lng->txt('save'));
        
        $form = self::addExportFieldInfo($form, $this->obj_id, $this->type);
        $form = self::addCustomFields($form, $this->obj_id, $this->type);
        
        if ($this->getPrivacy()->confirmationRequired($this->type)) {
            $form = self::addAgreement($form, $this->obj_id, $this->type);
        }
        
        return $form;
    }
    
    /**
     * Add export field info to form
     * @global type $lng
     * @param type $form
     * @param type $a_obj_id
     * @param type $a_type
     * @return type
     */
    public static function addExportFieldInfo($form, $a_obj_id, $a_type)
    {
        global $DIC;

        $lng = $DIC['lng'];
        
        include_once('Services/PrivacySecurity/classes/class.ilExportFieldsInfo.php');
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
        include_once('Services/User/classes/class.ilUserDefinedFields.php');
        foreach (ilUserDefinedFields::_getInstance()->getExportableFields($a_obj_id) as $field) {
            $tpl->setCurrentBlock('field_item');
            $tpl->setVariable('FIELD_NAME', $field['field_name']);
            $tpl->parseCurrentBlock();
        }
        
        $fields->setHtml($tpl->get());
        $form->addItem($fields);
        
        return $form;
    }
    
    /**
     * Add agreement to form
     * @param type $form
     * @param type $a_obj_id
     * @param type $a_type
     */
    public static function addAgreement($form, $a_obj_id, $a_type)
    {
        global $DIC;

        $lng = $DIC['lng'];
        
        $agreement = new ilCheckboxInputGUI($lng->txt($a_type . '_agree'), 'agreement');
        $agreement->setRequired(true);
        $agreement->setOptionTitle($lng->txt($a_type . '_info_agree'));
        $agreement->setValue(1);
        $form->addItem($agreement);
        
        return $form;
    }

    // fau: courseUdf - add custom fields without parent directly
    /**
     * Add custom course fields
     * @param ilPropertyFormGUI $form
     * @param int $a_obj_id
     * @param string $a_type
     * @param string $a_mode
     * @return ilPropertyFormGUI
     */
    public static function addCustomFields($form, $a_obj_id, $a_type, $a_mode = 'user')
    {
        global $DIC;

        $lng = $DIC['lng'];
        
        include_once('Modules/Course/classes/Export/class.ilCourseDefinedFieldDefinition.php');
        include_once('Modules/Course/classes/Export/class.ilCourseUserData.php');

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
                case IL_CDF_TYPE_SELECT:

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

                case IL_CDF_TYPE_TEXT:
                    $text = new ilTextInputGUI($field_obj->getName(), 'cdf_' . $field_obj->getId());
                    $text->setInfo($field_obj->getDescription());
                    $text->setSize(32);
                    $text->setMaxLength(255);
                    if ($field_obj->isRequired()) {
                        $text->setRequired(true);
                    }
                    return $text;


                case IL_CDF_TYPE_EMAIL:
                    $email = new ilEMailInputGUI($field_obj->getName(), 'cdf_' . $field_obj->getId());
                    $email->setInfo($field_obj->getDescription());
                    $email->setSize(32);
                    $email->setMaxLength(255);
                    if ($field_obj->isRequired()) {
                        $email->setRequired(true);
                    }
                    return $email;

                case IL_CDF_TYPE_CHECKBOX:
                    $checkbox = new ilCheckboxInputGUI($field_obj->getName(), 'cdf_' . $field_obj->getId());
                    $checkbox->setInfo($field_obj->getDescription());
                    $checkbox->setValue(1);
                    if ($field_obj->isRequired()) {
                        $checkbox->setCheckRequired(true);
                        $checkbox->setRequired(true);
                    }
                    return $checkbox;
            }
    }
    // fau.

    
    /**
     * Save
     *
     * @access private
     * @param
     *
     */
    private function save()
    {
        global $DIC;

        $ilUser = $DIC['ilUser'];
        
        $form = $this->initFormAgreement();
        
        // #14715 - checkInput() does not work for checkboxes
        if ($this->checkAgreement() && $form->checkInput()) {
            self::saveCourseDefinedFields($form, $this->obj_id);

            $this->getAgreement()->setAccepted(true);
            $this->getAgreement()->setAcceptanceTime(time());
            $this->getAgreement()->save();
            
            include_once './Services/Membership/classes/class.ilObjectCustomUserFieldHistory.php';
            $history = new ilObjectCustomUserFieldHistory($this->obj_id, $ilUser->getId());
            $history->setUpdateUser($ilUser->getId());
            $history->setEditingTime(new ilDateTime(time(), IL_CAL_UNIX));
            $history->save();
            
            $this->ctrl->returnToParent($this);
        } elseif (!$this->checkAgreement()) {
            ilUtil::sendFailure($this->lng->txt($this->type . '_agreement_required'));
            $form->setValuesByPost();
            $this->showAgreement($form);
            return false;
        } else {
            ilUtil::sendFailure($this->lng->txt('fill_out_all_required_fields'));
            $form->setValuesByPost();
            $this->showAgreement($form);
            return false;
        }
    }
    
    public static function setCourseDefinedFieldValues(ilPropertyFormGUI $form, $a_obj_id, $a_usr_id = 0)
    {
        global $DIC;

        $ilUser = $DIC['ilUser'];
        
        if (!$a_usr_id) {
            $a_usr_id = $ilUser->getId();
        }
        
        $ud = ilCourseUserData::_getValuesByObjId($a_obj_id);
        
        foreach (ilCourseDefinedFieldDefinition::_getFields($a_obj_id) as $field_obj) {
            $current_value = $ud[$a_usr_id][$field_obj->getId()];
            if (!$current_value) {
                continue;
            }
            
            switch ($field_obj->getType()) {
                case IL_CDF_TYPE_SELECT:
                    
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
                    
                case IL_CDF_TYPE_TEXT:
                    $item = $form->getItemByPostVar('cdf_' . $field_obj->getId());
                    $item->setValue($current_value);
                    break;

// fau: courseUdf - load email and checkbox values
                case IL_CDF_TYPE_EMAIL:
                    $item = $form->getItemByPostVar('cdf_' . $field_obj->getId());
                    $item->setValue($current_value);
                    break;

                case IL_CDF_TYPE_CHECKBOX:
                    $item = $form->getItemByPostVar('cdf_' . $field_obj->getId());
                    $item->setChecked((bool) $current_value);
                    break;
// fau.
            }
        }
    }
    
    
    /**
     * Save course defined fields
     * @param ilPropertyFormGUI $form
     */
    public static function saveCourseDefinedFields(ilPropertyFormGUI $form, $a_obj_id, $a_usr_id = 0)
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
                case IL_CDF_TYPE_SELECT:
                    
                    // Split value id from post
                    list($field_id, $option_id) = explode('_', $form->getInput('cdf_' . $field_obj->getId()));
                    $open_answer_indexes = (array) $field_obj->getValueOptions();
                    if (in_array($option_id, $open_answer_indexes)) {
                        $value = $form->getInput('cdf_oa_' . $field_obj->getId() . '_' . $option_id);
                    } else {
                        $value = $field_obj->getValueById($option_id);
                    }
                    break;
                    
                case IL_CDF_TYPE_TEXT:
                    $value = $form->getInput('cdf_' . $field_obj->getId());
                    break;

// fau: courseUdf - save email and checkbox value from agreement
                case IL_CDF_TYPE_EMAIL:
                    $value = $form->getInput('cdf_' . $field_obj->getId());
                    break;

                case IL_CDF_TYPE_CHECKBOX:
                    $value = $form->getInput('cdf_' . $field_obj->getId());
                    break;
// fau.
            }

            // fau: courseUdf - clear value if parent option is not selected
            if (isset($fields[$field_obj->getParentFieldId()])) {
                list($field_id, $option_id) = explode('_', $form->getInput('cdf_' . $field_obj->getParentFieldId()));
                if (empty($field_id) || $option_id != $field_obj->getParentValueId()) {
                    $value = null;
                }
            }
            // fau.
            
            $course_user_data = new ilCourseUserData($a_usr_id, $field_obj->getId());
            $course_user_data->setValue($value);
            $course_user_data->update();
        }
    }
    
    
    /**
     * Check Agreement
     *
     * @access private
     *
     */
    private function checkAgreement()
    {
        global $DIC;

        $ilUser = $DIC['ilUser'];
        
        if ($_POST['agreement']) {
            return true;
        }
        if ($this->privacy->confirmationRequired($this->type)) {
            return false;
        }
        return true;
    }
    
    
    
    /**
     * Read setting
     *
     * @access private
     * @return void
     */
    private function init()
    {
        global $DIC;

        $ilUser = $DIC['ilUser'];
        
        $this->required_fullfilled = ilCourseUserData::_checkRequired($ilUser->getId(), $this->obj_id);
        $this->agreement_required = $this->getAgreement()->agreementRequired();
    }
    
    /**
     * Send info message
     *
     * @access private
     */
    private function sendInfoMessage()
    {
        $message = '';
        if ($this->agreement_required) {
            $message = $this->lng->txt($this->type . '_ps_agreement_req_info');
        }
        if (!$this->required_fullfilled) {
            if (strlen($message)) {
                $message .= '<br />';
            }
            $message .= $this->lng->txt($this->type . '_ps_required_info');
        }
        
        if (strlen($message)) {
            ilUtil::sendFailure($message);
        }
    }
}
