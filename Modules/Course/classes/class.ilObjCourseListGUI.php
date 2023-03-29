<?php

/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once "Services/Object/classes/class.ilObjectListGUI.php";

/**
 * Class ilObjCourseListGUI
 *
 * @author Alex Killing <alex.killing@gmx.de>
 * $Id$
 *
 * @ingroup ModulesCourse
 */
class ilObjCourseListGUI extends ilObjectListGUI
{
    /**
     * @var \ilCertificateObjectsForUserPreloader
     */
    private $certificatePreloader;

    /**
    * initialisation
    */
    public function init()
    {
        $this->static_link_enabled = true;
        $this->delete_enabled = true;
        $this->cut_enabled = true;
        $this->copy_enabled = true;
        $this->subscribe_enabled = true;
        $this->link_enabled = false;
        $this->info_screen_enabled = true;
        $this->type = "crs";
        $this->gui_class_name = "ilobjcoursegui";
        
        $this->substitutions = ilAdvancedMDSubstitution::_getInstanceByObjectType($this->type);
        if ($this->substitutions->isActive()) {
            $this->substitutions_enabled = true;
        }

        // general commands array
        $this->commands = ilObjCourseAccess::_getCommands();
    }
    
    /**
     * @inheritdoc
     */
    public function initItem($a_ref_id, $a_obj_id, $type, $a_title = "", $a_description = "")
    {
        parent::initItem($a_ref_id, $a_obj_id, $type, $a_title, $a_description);

        $this->conditions_ok = ilConditionHandler::_checkAllConditionsOfTarget($a_ref_id, $this->obj_id);

        // fau: campoInfo - show info and links from campo
        // use custom property to hide the display in the result list of campo search
        global $DIC;
        $info_gui = $DIC->fau()->study()->info();
        $import_id = $DIC->fau()->study()->repo()->getImportId($this->obj_id);
        if ($import_id->isForCampo()) {
            if (!empty($line = $info_gui->getDatesLine($import_id))) {
                $this->addCustomProperty('', $line, false, true);
            }
            if (!empty($line = $info_gui->getResponsiblesLine($import_id))) {
                $this->addCustomProperty('', $line, false, true);
            }
            if (!empty($line = $info_gui->getLinksLine($import_id, $this->ref_id))) {
                $this->addCustomProperty('', $line, false, true);
            }
        }
        // fau.
    }

    /**
     * @return \ilCertificateObjectsForUserPreloader
     */
    protected function getCertificatePreloader() : \ilCertificateObjectsForUserPreloader
    {
        if (null === $this->certificatePreloader) {
            $repository = new ilUserCertificateRepository();
            $this->certificatePreloader = new ilCertificateObjectsForUserPreloader($repository);
        }
        
        return $this->certificatePreloader;
    }

    /**
    * Get item properties
    *
    * @return	array		array of property arrays:
    *						"alert" (boolean) => display as an alert property (usually in red)
    *						"property" (string) => property name
    *						"value" (string) => property value
    */
    public function getProperties()
    {
        global $DIC;

        $lng = $DIC['lng'];
        $ilUser = $DIC['ilUser'];

        $props = parent::getProperties();
        
        // check activation
        if (
            !ilObjCourseAccess::_isActivated($this->obj_id) &&
            !ilObject::lookupOfflineStatus($this->obj_id)
        ) {
            $showRegistrationInfo = false;
            $props[] = array(
                "alert" => true,
                "property" => $lng->txt("status"),
                "value" => $lng->txt("offline")
            );
        }

        // blocked
        include_once 'Modules/Course/classes/class.ilCourseParticipant.php';
        $members = ilCourseParticipant::_getInstanceByObjId($this->obj_id, $ilUser->getId());
        if ($members->isBlocked($ilUser->getId()) and $members->isAssigned($ilUser->getId())) {
            $props[] = array("alert" => true, "property" => $lng->txt("member_status"),
                "value" => $lng->txt("crs_status_blocked"));
        }

        // fau: showMemLimit - adapted info about registration, membership limit and status
        include_once './Modules/Course/classes/class.ilObjCourseAccess.php';
        $info = ilObjCourseAccess::lookupRegistrationInfo($this->obj_id, $this->ref_id);
        if ($info['reg_info_list_prop']) {
            $props[] = array(
                'alert' => false,
                'newline' => true,
                'property' => $info['reg_info_list_prop']['property'],
                'value' => $info['reg_info_list_prop']['value']
            );
        }
        if ($info['reg_info_list_prop_limit']) {
            $props[] = array(
                'alert' => false,
                'newline' => true,
                'property' => $info['reg_info_list_prop_limit']['property'],
                'propertyNameVisible' => strlen($info['reg_info_list_prop_limit']['property']) ? true : false,
                'value' => $info['reg_info_list_prop_limit']['value']
            );
        }
        if ($info['reg_info_list_prop_status']) {
            $props[] = array(
                'alert' => true,
                'newline' => true,
                'property' => $info['reg_info_list_prop_status']['property'],
                'propertyNameVisible' => strlen($info['reg_info_list_prop_status']['property']) ? true : false,
                'value' => $info['reg_info_list_prop_status']['value']
            );
        }
        // fau.


        // course period
        $info = ilObjCourseAccess::lookupPeriodInfo($this->obj_id);
        if (is_array($info)) {
            $props[] = array(
                'alert' => false,
                'newline' => true,
                'property' => $info['property'],
                'value' => $info['value']
            );
        }
        
        // check for certificates
        $hasCertificate = $this->getCertificatePreloader()->isPreloaded($ilUser->getId(), $this->obj_id);
        if (true === $hasCertificate) {
            $lng->loadLanguageModule('certificate');
            $cmd_link = "ilias.php?baseClass=ilRepositoryGUI&ref_id=" . $this->ref_id . "&cmd=deliverCertificate";
            $props[] = [
                'alert' => false,
                'property' => $lng->txt('certificate'),
                'value' => $DIC->ui()->renderer()->render(
                    $DIC->ui()->factory()->link()->standard($lng->txt('download_certificate'), $cmd_link)
                )
            ];
        }

        // booking information
        $repo = ilObjCourseAccess::getBookingInfoRepo();
        $book_info = new ilBookingInfoListItemPropertiesAdapter($repo);
        $props = $book_info->appendProperties($this->obj_id, $props);

        return $props;
    }
    
    
    /**
     * Workaround for course titles (linked if join or read permission is granted)
     * @param type $a_permission
     * @param type $a_cmd
     * @param type $a_ref_id
     * @param type $a_type
     * @param type $a_obj_id
     * @return type
     */
    public function checkCommandAccess($a_permission, $a_cmd, $a_ref_id, $a_type, $a_obj_id = "")
    {
        // Only check cmd access for cmd 'register' and 'unregister'
        // fau: joinAsGuest - add 'join_as_guest' as possible command
        // fau: preventCampoDelete - add 'cut' command to distinct cut from delete in modified ilObjCourseAccess
        // moving courses with campo connection should be allowed
        // normally just the delete permission is checked for moving objects
        if ($a_cmd != 'view' and $a_cmd != 'leave' and $a_cmd != 'join' and $a_cmd != 'joinAsGuest' and $a_cmd != 'cut') {

            $a_cmd = '';
        }
        // fau.
        if ($a_permission == 'crs_linked') {
            return
                parent::checkCommandAccess('read', $a_cmd, $a_ref_id, $a_type, $a_obj_id) ||
                parent::checkCommandAccess('join', $a_cmd, $a_ref_id, $a_type, $a_obj_id);
        }
        return parent::checkCommandAccess($a_permission, $a_cmd, $a_ref_id, $a_type, $a_obj_id);
    }
} // END class.ilObjCategoryGUI
