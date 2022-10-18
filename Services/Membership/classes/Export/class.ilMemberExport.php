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
include_once('Services/PrivacySecurity/classes/class.ilPrivacySettings.php');
include_once('Services/Membership/classes/class.ilMemberAgreement.php');
include_once('Modules/Course/classes/class.ilCourseParticipants.php');
include_once('Modules/Course/classes/Export/class.ilCourseDefinedFieldDefinition.php');
include_once('Services/User/classes/class.ilUserDefinedData.php');
include_once('Services/User/classes/class.ilUserFormSettings.php');

define("IL_MEMBER_EXPORT_CSV_FIELD_SEPERATOR", ',');
define("IL_MEMBER_EXPORT_CSV_STRING_DELIMITER", '"');


/**
* Class for generation of member export files
* @author Stefan Meyer <meyer@leifos.com>
* @version $Id$
*
*
* @ingroup Modules/Course
*/
class ilMemberExport
{
    const EXPORT_CSV = 1;
    const EXPORT_EXCEL = 2;
    
    
    private $ref_id;
    private $obj_id;
    private $type;
    private $members;
    private $groups = array();
    private $groups_participants = array();
    private $groups_rights = array();

    private $lng;
    
    private $settings;
    
    private $export_type = null;
    private $filename = null;
    
    private $user_ids = array();
    private $user_course_data = array();
    private $user_course_fields = array();
    private $user_profile_data = array();
    private $privacy;
    

    // fau: memberExport - flag for needed agreement
    private $agreement_needed = false;
    // fau.

    /**
     * Constructor
     *
     * @access public
     *
     */
    public function __construct($a_ref_id, $a_type = self::EXPORT_CSV)
    {
        global $DIC;

        $ilObjDataCache = $DIC['ilObjDataCache'];
        $lng = $DIC['lng'];
        
        $this->lng = $lng;
        
        $this->export_type = $a_type;
        
        $this->ref_id = $a_ref_id;
        $this->obj_id = $ilObjDataCache->lookupObjId($this->ref_id);
        $this->type = ilObject::_lookupType($this->obj_id);
        
        $this->initMembers();
        $this->initGroups();

        $this->agreement = ilMemberAgreement::_readByObjId($this->obj_id);
        $this->settings = new ilUserFormSettings('memexp');
        $this->privacy = ilPrivacySettings::_getInstance();

        // fau: memberExport - init flag for needed agreement
        $this->agreement_needed = $this->privacy->confirmationRequired($this->type)
                        or ilCourseDefinedFieldDefinition::_getFields($this->obj_id);
        // fau.

        // fau: memberExport - initialize arrays for events, groups and learning progress
        $this->events = array();
        $this->groups = array();
        $this->group_members = array();
        $this->group_waiting = array();
        $this->lp_data = array();
        $this->lp_keys = array();
        // fau.
    }
    

    public function filterUsers($a_usr_ids)
    {
        return $GLOBALS['DIC']->access()->filterUserIdsByRbacOrPositionOfCurrentUser(
            'manage_members',
            'manage_members',
            $this->ref_id,
            $a_usr_ids
        );
    }

    /**
     * set filename
     * @param object $a_file
     * @return
     */
    public function setFilename($a_file)
    {
        $this->filename = $a_file;
    }
    
    /**
     * get filename
     * @return
     */
    public function getFilename()
    {
        return $this->filename;
    }
    
    /**
     * get ref_id
     * @return
     */
    public function getRefId()
    {
        return $this->ref_id;
    }
    
    /**
     * get obj type
     * @return
     */
    public function getType()
    {
        return $this->type;
    }
    
    /**
     * get current export type
     * @return
     */
    public function getExportType()
    {
        return $this->export_type;
    }
    
    /**
     * Get obj id
     * @return
     */
    public function getObjId()
    {
        return $this->obj_id;
    }
    
    /**
     * Create Export File
     *
     * @access public
     *
     */
    public function create()
    {
        $this->fetchUsers();

        // fau: memberExport - fetch events, groups and learning progress
        if ($this->settings->enabled('events')) {
            $this->fetchEvents();
        }
        if ($this->settings->enabled('groups')) {
            $this->fetchGroups();
        }

        $this->fetchLPData();
        // fau.


        // DONE: Switch different export types
        switch ($this->getExportType()) {
            case self::EXPORT_CSV:
                $this->createCSV();
                break;
                
            case self::EXPORT_EXCEL:
                $this->createExcel();
                break;
        }
    }
    
    /**
     * toString method
     *
     * @access public
     * @param
     *
     */
    public function getCSVString()
    {
        return $this->csv->getCSVString();
    }
    
    
    /**
     *
     * @return
     */
    public function createExcel()
    {
        include_once "./Services/Excel/classes/class.ilExcel.php";
        $this->worksheet = new ilExcel();
        $this->worksheet->addSheet($this->lng->txt("members"));

        $this->write();

        $this->worksheet->writeToFile($this->getFilename());
    }
    
    /**
     * Create CSV File
     *
     * @access public
     *
     */
    public function createCSV()
    {
        include_once('Services/Utilities/classes/class.ilCSVWriter.php');
        $this->csv = new ilCSVWriter();
        
        $this->write();
    }
    
    
    
    /**
     * Write on column
     * @param object $a_value
     * @param object $a_row
     * @param object $a_col
     * @return
     */
    protected function addCol($a_value, $a_row, $a_col)
    {
        switch ($this->getExportType()) {
            case self::EXPORT_CSV:
                $this->csv->addColumn($a_value);
                break;
                
            case self::EXPORT_EXCEL:
                $this->worksheet->setCell($a_row + 1, $a_col, $a_value);
                break;
        }
    }
    
    /**
     * Add row
     * @return
     */
    protected function addRow()
    {
        switch ($this->getExportType()) {
            case self::EXPORT_CSV:
                $this->csv->addRow();
                break;
            
            case self::EXPORT_EXCEL:
                break;
        }
    }
    
    /**
     * Get ordered enabled fields
     *
     * @access public
     * @param
     *
     */
    protected function getOrderedExportableFields()
    {
        include_once('Services/PrivacySecurity/classes/class.ilPrivacySettings.php');
        include_once('Services/PrivacySecurity/classes/class.ilExportFieldsInfo.php');
        include_once('Modules/Course/classes/Export/class.ilCourseDefinedFieldDefinition.php');
        include_once('Services/User/classes/class.ilUserDefinedFields.php');

        $field_info = ilExportFieldsInfo::_getInstanceByType(ilObject::_lookupType($this->obj_id));
        $field_info->sortExportFields();
        $fields[] = 'role';
        // Append agreement info
        $privacy = ilPrivacySettings::_getInstance();
        // fau: memberExport - add registration field if agreement is not needed
        if ($this->agreement_needed) {
            $fields[] = 'agreement';
        } else {
            $fields[] = 'registration';
        }
        // fau.

        // fau: memberExport - add subscription message as field
        $fields[] = 'submessage';
        // fau.


        foreach ($field_info->getExportableFields() as $field) {
            if ($this->settings->enabled($field)) {
                $fields[] = $field;
            }
        }
        
        $udf = ilUserDefinedFields::_getInstance();
        foreach ($udf->getCourseExportableFields() as $field_id => $udf_data) {
            if ($this->settings->enabled('udf_' . $field_id)) {
                $fields[] = 'udf_' . $field_id;
            }
        }

        // Add course specific fields
        foreach (ilCourseDefinedFieldDefinition::_getFields($this->obj_id) as $field_obj) {
            if ($this->settings->enabled('cdf_' . $field_obj->getId())) {
                $fields[] = 'cdf_' . $field_obj->getId();
            }
        }
        if ($this->settings->enabled('group_memberships')) {
            $fields[] = 'crs_members_groups';
        }

        return $fields ? $fields : array();
    }
    
    /**
     * Write data
     * @return
     */
    protected function write()
    {
        // Add header line
        $row = 0;
        $col = 0;
        foreach ($all_fields = $this->getOrderedExportableFields() as $field) {
            switch ($field) {
                case 'role':
                    #$this->csv->addColumn($this->lng->txt($this->getType().'_role_status'));
                    $this->addCol($this->lng->txt($this->getType() . '_role_status'), $row, $col++);
                    break;
                case 'agreement':
                    #$this->csv->addColumn($this->lng->txt('ps_agreement_accepted'));
                    $this->addCol($this->lng->txt('ps_agreement_accepted'), $row, $col++);
                    break;
                case 'consultation_hour':
                    $this->lng->loadLanguageModule('dateplaner');
                    $this->addCol($this->lng->txt('cal_ch_field_ch'), $row, $col++);
                    break;

                case 'org_units':
                    $this->addCol($this->lng->txt('org_units'), $row, $col++);
                    break;

                // fau: memberExport - add registration header if agreement is not needed
                case 'registration':
                    $this->addCol($this->lng->txt('mem_registration_access_time'), $row, $col++);
                    break;
                // fau.

                // fau: memberExport - add subscription message header
                case 'submessage':
                    $this->addCol($this->lng->txt('message'), $row, $col++);
                    break;
                // fau.

                default:
                    if (substr($field, 0, 4) == 'udf_') {
                        $field_id = explode('_', $field);
                        include_once('Services/User/classes/class.ilUserDefinedFields.php');
                        $udf = ilUserDefinedFields::_getInstance();
                        $def = $udf->getDefinition($field_id[1]);
                        #$this->csv->addColumn($def['field_name']);
                        $this->addCol($def['field_name'], $row, $col++);
                    } elseif (substr($field, 0, 4) == 'cdf_') {
                        $field_id = explode('_', $field);
                        #$this->csv->addColumn(ilCourseDefinedFieldDefinition::_lookupName($field_id[1]));
                        $this->addCol(ilCourseDefinedFieldDefinition::_lookupName($field_id[1]), $row, $col++);
                    } elseif ($field == "username") {//User Name Presentation Guideline; username should be named login
                        $this->addCol($this->lng->txt("login"), $row, $col++);
                    } else {
                        #$this->csv->addColumn($this->lng->txt($field));
                        $this->addCol($this->lng->txt($field), $row, $col++);
                    }
                    break;
            }
        }

        // fau: campoCheck - add restrictions column
        if ($this->settings->enabled('restrictions')) {
            global $DIC;
            $object = ilObjectFactory::getInstanceByRefId($this->getRefId());
            $restriction_obj_ids = $DIC->fau()->ilias()->objects()->getParallelObjectIds($object);
            $restriction_module_ids = $DIC->fau()->user()->repo()->getSelectedModuleIdsOfMembers($restriction_obj_ids);
            $hardRestrictions = $DIC->fau()->cond()->hard();
            $this->addCol($this->lng->txt('fau_rest_hard_restrictions'), $row, $col++);
        }
        // fau.

        // fau: memberExport - add events in header row
        ilDatePresentation::setUseRelativeDates(false);
        foreach ($this->events as $event_obj) {
            $this->addCol($event_obj->getTitle() . ' (' . $event_obj->getFirstAppointment()->appointmentToString() . ')', $row, $col++);
        }
        // fau.

        // fau: memberExport - add groups in header row
        foreach ($this->groups as $group_obj) {
            $this->addCol($group_obj->getTitle(), $row, $col++);
        }
        // fau.

        // fau: memberExport - add learning progress titles in header row
        foreach ($this->lp_keys as $key) {
            $this->addCol($this->lp_data[$key]['title'], $row, $col++);
        }
        // fau.


        #$this->csv->addRow();
        $this->addRow();
        // Add user data
        foreach ($this->user_ids as $usr_id) {
            $row++;
            $col = 0;
            
            $udf_data = new ilUserDefinedData($usr_id);
            foreach ($all_fields as $field) {
                // Handle course defined fields
                if ($this->addUserDefinedField($udf_data, $field, $row, $col)) {
                    $col++;
                    continue;
                }
                
                if ($this->addCourseField($usr_id, $field, $row, $col)) {
                    $col++;
                    continue;
                }
                
                switch ($field) {
                    case 'role':
                        switch ($this->user_course_data[$usr_id]['role']) {
                            case IL_CRS_ADMIN:
                                #$this->csv->addColumn($this->lng->txt('crs_admin'));
                                $this->addCol($this->lng->txt('crs_admin'), $row, $col++);
                                break;
                                
                            case IL_CRS_TUTOR:
                                #$this->csv->addColumn($this->lng->txt('crs_tutor'));
                                $this->addCol($this->lng->txt('crs_tutor'), $row, $col++);
                                break;

                            case IL_CRS_MEMBER:
                                #$this->csv->addColumn($this->lng->txt('crs_member'));
                                $this->addCol($this->lng->txt('crs_member'), $row, $col++);
                                break;
                                
                            case IL_GRP_ADMIN:
                                #$this->csv->addColumn($this->lng->txt('il_grp_admin'));
                                $this->addCol($this->lng->txt('il_grp_admin'), $row, $col++);
                                break;
                                
                            case IL_GRP_MEMBER:
                                #$this->csv->addColumn($this->lng->txt('il_grp_member'));
                                $this->addCol($this->lng->txt('il_grp_member'), $row, $col++);
                                break;
                                
                            case 'subscriber':
                                #$this->csv->addColumn($this->lng->txt($this->getType().'_subscriber'));
                                $this->addCol($this->lng->txt($this->getType() . '_subscriber'), $row, $col++);
                                break;

                            // fau: memberExport - add waiting list as specific role
                            case 'waiting_list':
                                $this->addCol($this->lng->txt('crs_waiting_list'), $row, $col++);
                                break;

                            default:
                                #$this->csv->addColumn($this->lng->txt('crs_waiting_list'));
                                $this->addCol('', $row, $col++);
                                break;
                            // fau.
                        }
                        break;
                    
                    case 'agreement':
                        if (isset($this->agreement[$usr_id])) {
                            if ($this->agreement[$usr_id]['accepted']) {
                                #$this->csv->addColumn(il-Format::format-Unix-Time($this->agreement[$usr_id]['acceptance_time'],true));
                                $dt = new ilDateTime($this->agreement[$usr_id]['acceptance_time'], IL_CAL_UNIX);
                                $this->addCol($dt->get(IL_CAL_DATETIME), $row, $col++);
                            } else {
                                #$this->csv->addColumn($this->lng->txt('ps_not_accepted'));
                                $this->addCol($this->lng->txt('ps_not_accepted'), $row, $col++);
                            }
                        } else {
                            #$this->csv->addColumn($this->lng->txt('ps_not_accepted'));
                            $this->addCol($this->lng->txt('ps_not_accepted'), $row, $col++);
                        }
                        break;

                    // fau: memberExport - add registration column if agreement is not needed
                    case 'registration':
                        if ($this->agreement[$usr_id]['acceptance_time']) {
                            ilDatePresentation::setUseRelativeDates(false);
                            $this->addCol(ilDatePresentation::formatDate(new ilDateTime($this->agreement[$usr_id]['acceptance_time'], IL_CAL_UNIX)), $row, $col++);
                        } else {
                            $this->addCol('', $row, $col++);
                        }
                        break;
                    // fau.

                    // fau: memberExport - add subscription message column
                    case 'submessage':
                        $this->addCol($this->user_course_data[$usr_id]['submessage'], $row, $col++);
                        break;
                    // fau.

                    // These fields are always enabled
                    case 'username':
                        #$this->csv->addColumn($this->user_profile_data[$usr_id]['login']);
                        $this->addCol($this->user_profile_data[$usr_id]['login'], $row, $col++);
                        break;
                        
                    case 'firstname':
                    case 'lastname':
                        #$this->csv->addColumn($this->user_profile_data[$usr_id][$field]);
                        $this->addCol($this->user_profile_data[$usr_id][$field], $row, $col++);
                        break;
                    
                    case 'consultation_hour':
                        include_once './Services/Booking/classes/class.ilBookingEntry.php';
                        $bookings = ilBookingEntry::lookupManagedBookingsForObject($this->obj_id, $GLOBALS['DIC']['ilUser']->getId());
                        
                        $uts = array();
                        foreach ((array) $bookings[$usr_id] as $ut) {
                            ilDatePresentation::setUseRelativeDates(false);
                            $tmp = ilDatePresentation::formatPeriod(
                                new ilDateTime($ut['dt'], IL_CAL_UNIX),
                                new ilDateTime($ut['dtend'], IL_CAL_UNIX)
                            );
                            if (strlen($ut['explanation'])) {
                                $tmp .= ' ' . $ut['explanation'];
                            }
                            $uts[] = $tmp;
                        }
                        $uts_str = implode(',', $uts);
                        $this->addCol($uts_str, $row, $col++);
                        break;
                    case 'crs_members_groups':
                        $groups = array();

                        foreach (array_keys($this->groups) as $grp_ref) {
                            if (in_array($usr_id, $this->groups_participants[$grp_ref])
                                && $this->groups_rights[$grp_ref]) {
                                $groups[] = $this->groups[$grp_ref];
                            }
                        }
                        $this->addCol(implode(", ", $groups), $row, $col++);
                        break;

                    case 'org_units':
                        $this->addCol(ilObjUser::lookupOrgUnitsRepresentation($usr_id), $row, $col++);
                        break;


                    // fau: memberExport - add studydata and educations
                    // fau: userData - add studydata and educations
                    case 'studydata':
                        global $DIC;
                        if (!$this->agreement_needed or $this->agreement[$usr_id]['accepted']) {
                            $studydata = $DIC->fau()->user()->getStudiesAsText((int) $usr_id);
                            $studydata = $DIC->fau()->tools()->convert()->quoteForExport($studydata);
                            $this->addCol($studydata, $row, $col++);
                        } else {
                            $this->addCol('', $row, $col++);
                        }
                        break;

                    case 'educations':
                        global $DIC;
                        if (!$this->agreement_needed or $this->agreement[$usr_id]['accepted']) {
                            $educations = $DIC->fau()->user()->getEducationsAsText((int) $usr_id, (int) $this->getRefId());
                            $educations = $DIC->fau()->tools()->convert()->quoteForExport($educations);
                            $this->addCol($educations, $row, $col++);
                        } else {
                            $this->addCol('', $row, $col++);
                        }
                        break;
                    // fau.

                    default:
                        // Check aggreement
                        // fau: memberExport - use prechecked requirement for agreement
                        if (!$this->agreement_needed or $this->agreement[$usr_id]['accepted']) {
                            // fau.
                            #$this->csv->addColumn($this->user_profile_data[$usr_id][$field]);
                            $this->addCol($this->user_profile_data[$usr_id][$field], $row, $col++);
                        } else {
                            #$this->csv->addColumn('');
                            $this->addCol('', $row, $col++);
                        }
                        break;

                }
            }

            // fau: campoCheck - add restrictions data
            if ($this->settings->enabled('restrictions')) {
                if ($this->members->isMember($usr_id) || in_array($this->user_course_data[$usr_id]['role'], ['waiting_list','subscriber'])
                ) {
                    $hardRestrictions->checkObject($this->getObjId(), $usr_id);
                    if ($this->members->isMember($usr_id)) {
                        $module_id = $restriction_module_ids[$usr_id] ?? null;
                    }
                    else {
                        $module_id = $this->user_course_data[$usr_id]['module_id'] ?? null;
                    }
                    $this->addCol($hardRestrictions->getCheckResultInfo(false, $module_id), $row, $col++);
                }
                else {
                    $this->addCol('', $row, $col++);
                }
            }
            // fau.

            // fau: memberExport - add user participation for events
            foreach ($this->events as $event_obj) {
                $event_part = new ilEventParticipants((int) $event_obj->getId());

                if ($event_obj->enabledRegistration()
                and (!$event_part->isRegistered($usr_id))
                and (!$event_part->hasParticipated($usr_id))) {
                    $this->addCol($this->lng->txt('event_not_registered'), $row, $col++);
                } else {
                    $this->addCol($event_part->hasParticipated($usr_id) ?
                                        $this->lng->txt('event_participated') :
                                        $this->lng->txt('event_not_participated'), $row, $col++);
                }
            }
            // fau.

            // fau: memberExport - add user participation for groups
            foreach ($this->groups as $group_obj) {
                $member = $this->group_members[$group_obj->getId()];
                $waiting = $this->group_waiting[$group_obj->getId()];

                if ($member->isAdmin($usr_id)) {
                    $this->addCol($this->lng->txt('crs_admin'), $row, $col++);
                } elseif ($member->isMember($usr_id)) {
                    $this->addCol($this->lng->txt('crs_member'), $row, $col++);
                } elseif ($member->isBlocked($usr_id)) {
                    $this->addCol($this->lng->txt('crs_blocked'), $row, $col++);
                } elseif ($member->isSubscriber($usr_id)) {
                    $this->addCol($this->lng->txt('crs_subscriber'), $row, $col++);
                } elseif ($waiting->isOnList($usr_id)) {
                    $this->addCol($this->lng->txt('crs_waiting_list'), $row, $col++);
                } else {
                    $this->addCol($this->lng->txt('event_not_registered'), $row, $col++);
                }
            }
            // fau.

            // fau: memberExport - add learning progress data in header row
            foreach ($this->lp_keys as $key) {
                switch ($this->lp_data[$key]['lp_type']) {
                    case 'marks':
                        $this->addCol($this->lp_data[$key]['marks'][$usr_id]['mark'], $row, $col++);
                        break;

                    case 'status':
                        if (in_array($usr_id, $this->lp_data[$key][ilLPStatus::LP_STATUS_COMPLETED])) {
                            $status = ilLPStatus::LP_STATUS_COMPLETED;
                        } elseif (in_array($usr_id, $this->lp_data[$key][ilLPStatus::LP_STATUS_FAILED])) {
                            $status = ilLPStatus::LP_STATUS_FAILED;
                        } elseif (in_array($usr_id, $this->lp_data[$key][ilLPStatus::LP_STATUS_IN_PROGRESS])) {
                            $status = ilLPStatus::LP_STATUS_IN_PROGRESS;
                        } else {
                            $status = ilLPStatus::LP_STATUS_NOT_ATTEMPTED;
                        }
                        $this->addCol($this->lng->txt($status), $row, $col++);
                        break;

                    case 'comments':
                        $this->addCol($this->lp_data[$key]['comments'][$usr_id]['u_comment'], $row, $col++);
                        break;

                    default:
                        $this->addCol('', $row, $col++);
                        break;
                }
            }
            // fau.


            #$this->csv->addRow();
            $this->addRow();
        }
    }
    
    
    
    /**
     * Fetch all users that will be exported
     *
     * @access private
     *
     */
    private function fetchUsers()
    {
        $this->readCourseSpecificFieldsData();
        
        if ($this->settings->enabled('admin')) {
            $this->user_ids = $tmp_ids = $this->members->getAdmins();
            $this->readCourseData($tmp_ids);
        }
        if ($this->settings->enabled('tutor')) {
            $this->user_ids = array_merge($tmp_ids = $this->members->getTutors(), $this->user_ids);
            $this->readCourseData($tmp_ids);
        }
        if ($this->settings->enabled('member')) {
            $this->user_ids = array_merge($tmp_ids = $this->members->getMembers(), $this->user_ids);
            $this->readCourseData($tmp_ids);
        }
        if ($this->settings->enabled('subscribers')) {
            $this->user_ids = array_merge($tmp_ids = $this->members->getSubscribers(), $this->user_ids);
            $this->readCourseData($tmp_ids, 'subscriber');
        }
        if ($this->settings->enabled('waiting_list')) {
            include_once('Modules/Course/classes/class.ilCourseWaitingList.php');
            $waiting_list = new ilCourseWaitingList($this->obj_id);
            // fau: fairSub - set correct user status in export
            $this->user_ids = array_merge($tmp_ids = $waiting_list->getUserIds(), $this->user_ids);
            foreach ($tmp_ids as $tmp_id) {
                if ($waiting_list->isToConfirm($tmp_id)) {
                    $this->readCourseData(array($tmp_id), 'subscriber');
                } else {
                    $this->readCourseData(array($tmp_id), 'waiting_list');
                }
                // fau: memberExport - get subscription message
                $this->user_course_data[$tmp_id]['submessage'] = $waiting_list->getSubject($tmp_id);
                // fau.
                // fau: campoCheck - get the module id
                $this->user_course_data[$tmp_id]['module_id'] = $waiting_list->getModuleId($tmp_id);
                // fau.
            }
            // fau.
        }
        $this->user_ids = $this->filterUsers($this->user_ids);

        // Sort by lastname
        $this->user_ids = ilUtil::_sortIds($this->user_ids, 'usr_data', 'lastname', 'usr_id');
        
        // Finally read user profile data
        $this->user_profile_data = ilObjUser::_readUsersProfileData($this->user_ids);
    }


    // fau: memberExport - new function fetchEvents
    private function fetchEvents()
    {
        global $ilAccess, $tree;

        $events = array();
        foreach ($tree->getSubtree($tree->getNodeData($this->ref_id), false, 'sess') as $event_id) {
            $tmp_event = ilObjectFactory::getInstanceByRefId($event_id, false);
            if (!is_object($tmp_event) or !$ilAccess->checkAccess('write', '', $event_id)) {
                continue;
            }
            $sort = $tmp_event->getFirstAppointment()->getStart()->get(IL_CAL_DATETIME);
            $sort .= $tmp_event->getTitle();
            $sort .= " " . $tmp_event->getId();
            $events[$sort] = $tmp_event;
        }
        ksort($events);
        $this->events = array_values($events);
    }
    // fau.

    // fau: memberExport - new function fetchGroups
    private function fetchGroups()
    {
        global $ilAccess, $tree;

        $groups = array();
        foreach ($tree->getSubtree($tree->getNodeData($this->ref_id), false, 'grp') as $group_id) {
            $tmp_group = ilObjectFactory::getInstanceByRefId($group_id, false);
            if (!is_object($tmp_group) or !$ilAccess->checkAccess('write', '', $group_id)) {
                continue;
            }
            $sort = $tmp_group->getTitle() . " " . $tmp_group->getId();
            $groups[$sort] = $tmp_group;
        }
        ksort($groups);
        $this->groups = array_values($groups);

        foreach ($this->groups as $group) {
            $members = ilGroupParticipants::_getInstanceByObjId($group->getId());
            $this->group_members[$group->getId()] = $members;

            $waiting = new ilGroupWaitingList($group->getId());
            $this->group_waiting[$group->getId()] = $waiting;
        }
    }
    // fau.


    // fau: memberExport - new function fetchLPData()
    private function fetchLPData()
    {
        global $ilAccess, $tree;

        include_once 'Services/Tracking/classes/class.ilLPStatus.php';
        require_once('Services/Tracking/classes/class.ilLPMarks.php');
        require_once('Services/Tracking/classes/class.ilLPStatusWrapper.php');

        foreach ($tree->getSubtree($tree->getNodeData($this->ref_id), true) as $data) {
            if (!$this->settings->enabled($data['type'] . '_status')
                and !$this->settings->enabled($data['type'] . '_marks')
                and !$this->settings->enabled($data['type'] . '_comments')) {
                continue;
            }

            if (!$ilAccess->checkAccess('edit_learning_progress', '', $data['ref_id'], $data['type'], $data['obj_id'])) {
                continue;
            }

            if ($data['type'] == 'sess' and $data['title'] == '') {
                $tmp_sess = ilObjectFactory::getInstanceByRefId($data['ref_id'], false);
                $data['title'] = $tmp_sess->getTitle();
                unset($tmp_sess);
            }

            $basekey = utf8_decode($data['type']) . chr(255)
                     . utf8_decode($data['title']) . chr(255)
                     . $data['obj_id'] . chr(255);


            // get title of sessions
            ilDatePresentation::setUseRelativeDates(false);
            if ($data['type'] == 'sess' and $data['title'] == '') {
                $tmp_sess = ilObjectFactory::getInstanceByRefId($data['ref_id'], false);
                $data['title'] = $tmp_sess->getTitle() . ' (' . $tmp_sess->getFirstAppointment()->appointmentToString() . ')';
                unset($tmp_sess);
            }

            if ($this->settings->enabled($data['type'] . '_marks') || $this->settings->enabled($data['type'] . '_comments')) {
                $markData = ilLPMarks::_getMarkDataOfObject($data['obj_id']);
            }

            if ($this->settings->enabled($data['type'] . '_marks')) {
                $key = $basekey . "marks";
                $this->lp_data[$key]['lp_type'] = 'marks';
                $this->lp_data[$key]['title'] = $data['title'];
                $this->lp_data[$key]['type'] = $data['type'];
                $this->lp_data[$key]['marks'] = $markData;
            }

            if ($this->settings->enabled($data['type'] . '_status')) {
                $key = $basekey . "status";
                $this->lp_data[$key]['lp_type'] = 'status';
                $this->lp_data[$key]['title'] = $data['title'];
                $this->lp_data[$key]['type'] = $data['type'];
                $this->lp_data[$key][ilLPStatus::LP_STATUS_IN_PROGRESS] = ilLPStatusWrapper::_getInProgress($data['obj_id']);
                $this->lp_data[$key][ilLPStatus::LP_STATUS_COMPLETED] = ilLPStatusWrapper::_getCompleted($data['obj_id']);
                $this->lp_data[$key][ilLPStatus::LP_STATUS_FAILED] = ilLPStatusWrapper::_getFailed($data['obj_id']);
            }

            if ($this->settings->enabled($data['type'] . '_comments')) {
                $key = $basekey . "comments";
                $this->lp_data[$key]['lp_type'] = 'comments';
                $this->lp_data[$key]['title'] = $data['title'];
                $this->lp_data[$key]['type'] = $data['type'];
                $this->lp_data[$key]['comments'] = $markData;
            }
        }

        ksort($this->lp_data);
        $this->lp_keys = array_keys($this->lp_data);
    }
    // fau.


    /**
     * Read All User related course data
     *
     * @access private
     *
     */
    private function readCourseData($a_user_ids, $a_status = 'member')
    {
        foreach ($a_user_ids as $user_id) {
            // Read course related data
            if ($this->members->isAdmin($user_id)) {
                $this->user_course_data[$user_id]['role'] = $this->getType() == 'crs' ? IL_CRS_ADMIN : IL_GRP_ADMIN;
            } elseif ($this->members->isTutor($user_id)) {
                $this->user_course_data[$user_id]['role'] = IL_CRS_TUTOR;
            } elseif ($this->members->isMember($user_id)) {
                $this->user_course_data[$user_id]['role'] = $this->getType() == 'crs' ? IL_CRS_MEMBER : IL_GRP_MEMBER;
            } else {
                // fau: memberExport - use the parameter as default status
                $this->user_course_data[$user_id]['role'] = $a_status;
                // fau.
            }
        }
    }
    
    /**
     * Read course specific fields data
     *
     * @access private
     * @param
     *
     */
    private function readCourseSpecificFieldsData()
    {
        include_once('Modules/Course/classes/Export/class.ilCourseUserData.php');
        $this->user_course_fields = ilCourseUserData::_getValuesByObjId($this->obj_id);
    }
    
    /**
     * fill course specific fields
     *
     * @access private
     * @param int usr_id
     * @param string field
     * @return bool
     *
     */
    private function addCourseField($a_usr_id, $a_field, $row, $col)
    {
        if (substr($a_field, 0, 4) != 'cdf_') {
            return false;
        }
        // fau: memberExport - use prechecked requirement for agreement
        if (!$this->agreement_needed or $this->agreement[$a_usr_id]['accepted']) {
            // fau.
            $field_info = explode('_', $a_field);
            $field_id = $field_info[1];
            $value = $this->user_course_fields[$a_usr_id][$field_id];
            #$this->csv->addColumn($value);
            $this->addCol($value, $row, $col);
            return true;
        }
        #$this->csv->addColumn('');
        $this->addCol('', $row, $col);
        return true;
    }
    
    /**
     * Add user defined fields
     *
     * @access private
     * @param object user defined data object
     * @param int field
     *
     */
    private function addUserDefinedField($udf_data, $a_field, $row, $col)
    {
        if (substr($a_field, 0, 4) != 'udf_') {
            return false;
        }

        // fau: memberExport - use prechecked requirement for agreement
        if (!$this->agreement_needed or $this->agreement[$udf_data->getUserId()]['accepted']) {
            // fau.
            $field_info = explode('_', $a_field);
            $field_id = $field_info[1];
            $value = $udf_data->get('f_' . $field_id);
            #$this->csv->addColumn($value);
            $this->addCol($value, $row, $col);
            return true;
        }

        $this->addCol('', $row, $col);
        return true;
    }
    
    /**
     * Init member object
     * @return
     */
    protected function initMembers()
    {
        if ($this->getType() == 'crs') {
            $this->members = ilCourseParticipants::_getInstanceByObjId($this->getObjId());
        }
        if ($this->getType() == 'grp') {
            $this->members = ilGroupParticipants::_getInstanceByObjId($this->getObjId());
        }
        return true;
    }

    protected function initGroups()
    {
        global $DIC;

        $tree = $DIC['tree'];
        $ilAccess = $DIC['ilAccess'];
        $parent_node = $tree->getNodeData($this->ref_id);
        $groups = $tree->getSubTree($parent_node, true, "grp");
        if (is_array($groups) && sizeof($groups)) {
            include_once('./Modules/Group/classes/class.ilGroupParticipants.php');
            $this->groups_rights = array();
            foreach ($groups as $idx => $group_data) {
                // check for group in group
                if ($group_data["parent"] != $this->ref_id && $tree->checkForParentType($group_data["ref_id"], "grp", true)) {
                    unset($groups[$idx]);
                } else {
                    $this->groups[$group_data["ref_id"]] = $group_data["title"];
                    //TODO: change permissions from write to manage_members plus "|| ilObjGroup->getShowMembers()"----- uncomment below; testing required
                    //$obj = new ilObjGroup($group_data["ref_id"], true);
                    $this->groups_rights[$group_data["ref_id"]] = (bool) $ilAccess->checkAccess("write", "", $group_data["ref_id"])/* || $obj->getShowMembers()*/;
                    $gobj = ilGroupParticipants::_getInstanceByObjId($group_data["obj_id"]);
                    $this->groups_participants[$group_data["ref_id"]] = $gobj->getParticipants();
                }
            }
        }
    }
}
