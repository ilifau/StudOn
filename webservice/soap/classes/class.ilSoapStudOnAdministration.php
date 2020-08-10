<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* fim: [soap] Soap administration methods for StudOn.
*
* @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
* @version $Id: class.ilSoapStudOnAdministration.php $
*
* @package studon
*/
include_once './webservice/soap/classes/class.ilSoapAdministration.php';

class ilSoapStudOnAdministration extends ilSoapAdministration
{
    public function __construct($use_nusoap = true)
    {
        parent::__construct($use_nusoap);
    }

    
    public function studonGetResources($sid, $semester)
    {
        $this->initAuth($sid);
        $this->initIlias();

        // basic check of arguments
        if (!$this->__checkSession($sid)) {
            return $this->__raiseError($this->sauth->getMessage(), $this->sauth->getMessageCode());
        }
        if (!$semester) {
            return $this->__raiseError('No semester given.', 'semester');
        }
        if (!$this->checkPermission('studonHasResource')) {
            return $this->__raiseError('No permision.', 'permission');
        }
        require_once './Services/UnivIS/classes/class.ilUnivis.php';
        require_once './Services/Link/classes/class.ilLink.php';
        $objects = ilUnivis::_getUntrashedObjectsForSemester($semester);
        $resources = array();
        foreach ($objects as $data) {
            $resource = array();
            $resource['univis_id'] = $data['import_id'];
            $resource['perma_link'] = ilLink::_getStaticLink($data['ref_id']);
            $resources[] = $resource;
        }
        return $resources;
    }
    
    public function studonHasResource($sid, $univis_id)
    {
        $this->initAuth($sid);
        $this->initIlias();

        // basic check of arguments
        if (!$this->__checkSession($sid)) {
            return $this->__raiseError($this->sauth->getMessage(), $this->sauth->getMessageCode());
        }
        if (!$univis_id) {
            return $this->__raiseError('No univis_id given.', 'univis_id');
        }
        if (!$this->checkPermission('studonHasResource')) {
            return $this->__raiseError('No permision.', 'permission');
        }

        require_once './Services/UnivIS/classes/class.ilUnivis.php';
        $objects = ilUnivis::_getUntrashedObjectsForUnivisId($univis_id);
        if (count($objects) > 1) {
            return $this->__raiseError('More than one reference found for the given univis_id', 'univis_id');
        } elseif (count($objects) == 1) {
            return true;
        } else {
            return false;
        }
    }


    public function studonGetPermaLink($sid, $univis_id)
    {
        $this->initAuth($sid);
        $this->initIlias();

        // basic check of arguments
        if (!$this->__checkSession($sid)) {
            return $this->__raiseError($this->sauth->getMessage(), $this->sauth->getMessageCode());
        }
        if (!$univis_id) {
            return $this->__raiseError('No univis_id given.', 'univis_id');
        }
        if (!$this->checkPermission('studonGetPermaLink')) {
            return $this->__raiseError('No permision.', 'permission');
        }

        // Find object and reference
        require_once './Services/UnivIS/classes/class.ilUnivis.php';
        $objects = ilUnivis::_getUntrashedObjectsForUnivisId($univis_id);
        if (count($objects) == 0) {
            return $this->__raiseError('No reference found for the given univis_id', 'univis_id');
        } elseif (count($objects) > 1) {
            return $this->__raiseError('More than one reference found for the given univis_id', 'univis_id');
        }
        $object = current($objects);
        $obj_id = $object['obj_id'];
        $ref_id = $object['ref_id'];

        // Get and return the link
        include_once './Services/Link/classes/class.ilLink.php';
        return ilLink::_getStaticLink($ref_id);
    }

    public function studonGetMembers($sid, $univis_id)
    {
        $this->initAuth($sid);
        $this->initIlias();

        // basic check of arguments
        if (!$this->__checkSession($sid)) {
            return $this->__raiseError($this->sauth->getMessage(), $this->sauth->getMessageCode());
        }
        if (!$univis_id) {
            return $this->__raiseError('No univis_id given.', 'univis_id');
        }
        if (!$this->checkPermission('studonGetMembers')) {
            return $this->__raiseError('No permision.', 'permission');
        }

        // Find object and reference
        require_once './Services/UnivIS/classes/class.ilUnivis.php';
        $objects = ilUnivis::_getUntrashedObjectsForUnivisId($univis_id);
        if (count($objects) == 0) {
            return $this->__raiseError('No reference found for the given univis_id', 'univis_id');
        } elseif (count($objects) > 1) {
            return $this->__raiseError('More than one reference found for the given univis_id', 'univis_id');
        }
        $object = current($objects);
        $obj_id = $object['obj_id'];
        $ref_id = $object['ref_id'];
        $obj_type = $object['type'];

        switch ($obj_type) {
            // Course
            case 'crs':

                if (!$course = ilObjectFactory::getInstanceByRefId($ref_id, false)) {
                    return $this->__raiseError('Cannot create course instance!', 'univis_id');
                }

                include_once 'Modules/Course/classes/class.ilCourseParticipants.php';
                $course_members = ilCourseParticipants::_getInstanceByObjId($course->getId());

                //return $course_members->getParticipants();

                $users = ilObjUser::_getUserData($course_members->getParticipants());

                $externals = array();
                foreach ($users as $user) {
                    if ($user['ext_account']) {
                        $externals[] = $user['ext_account'];
                    }
                }

                return $externals;


            // Group
            case 'grp':

                // TODO: support group
                return $this->__raiseError('The given resource is not a course!', 'univis_id');

            // Other types
            default:
                return $this->__raiseError('The given resource is not a course!', 'univis_id');
        }
    }


    public function studonIsSoapAssignable($sid, $univis_id)
    {
        $this->initAuth($sid);
        $this->initIlias();

        // basic check of arguments
        if (!$this->__checkSession($sid)) {
            return $this->__raiseError($this->sauth->getMessage(), $this->sauth->getMessageCode());
        }
        if (!$univis_id) {
            return $this->__raiseError('No univis_id given.', 'univis_id');
        }
        if (!$this->checkPermission('studonIsSoapAssignable')) {
            return $this->__raiseError('No permision.', 'permission');
        }

        // Find object and reference
        require_once './Services/UnivIS/classes/class.ilUnivis.php';
        $objects = ilUnivis::_getUntrashedObjectsForUnivisId($univis_id);
        if (count($objects) == 0) {
            return $this->__raiseError('No reference found for the given univis_id', 'univis_id');
        } elseif (count($objects) > 1) {
            return $this->__raiseError('More than one reference found for the given univis_id', 'univis_id');
        }
        $object = current($objects);
        $obj_id = $object['obj_id'];
        $ref_id = $object['ref_id'];
        $obj_type = $object['type'];

        
        // Check the subsrciption type
        switch ($obj_type) {
            // Course
            case 'crs':

                if (!$course = ilObjectFactory::getInstanceByRefId($ref_id, false)) {
                    return $this->__raiseError('Cannot create course instance!', 'univis_id');
                }

                if ($course->getSubscriptionLimitationType() == IL_CRS_SUBSCRIPTION_MYCAMPUS
                    or $course->getSubscriptionType() == IL_CRS_SUBSCRIPTION_MYCAMPUS) {
                    return true;
                } else {
                    return false;
                }
                
            // Group
            // no break
            case 'grp':
            
                // TODO: support soap assignment for group
                return false;
                
            // Other types
            default:
                return false;
        }
    }

    public function studonIsAssigned($sid, $identity, $univis_id)
    {
        $this->initAuth($sid);
        $this->initIlias();

        // basic check of arguments
        if (!$this->__checkSession($sid)) {
            return $this->__raiseError($this->sauth->getMessage(), $this->sauth->getMessageCode());
        }
        if (!$identity) {
            return $this->__raiseError('No identity given.', 'identity');
        }
        if (!$univis_id) {
            return $this->__raiseError('No univis_id given.', 'univis_is');
        }
        if (!$this->checkPermission('studonIsAssigned')) {
            return $this->__raiseError('No permision.', 'permission');
        }

        // Find the internal user for the identity
        $user_id = ilObjUser::_findUserIdByAccount($identity);
        if (!$user_id) {
            return false;
        }

        // Find object and reference
        require_once './Services/UnivIS/classes/class.ilUnivis.php';
        $objects = ilUnivis::_getUntrashedObjectsForUnivisId($univis_id);
        if (count($objects) == 0) {
            return $this->__raiseError('No reference found for the given univis_id', 'univis_id');
        } elseif (count($objects) > 1) {
            return $this->__raiseError('More than one reference found for the given univis_id', 'univis_id');
        }

        // Return if user is participant
        $object = current($objects);
        include_once 'Services/Membership/classes/class.ilParticipants.php';
        return ilParticipants::_isParticipant($object['ref_id'], $user_id);
    }


    public function studonAssignMember($sid, $identity, $univis_id)
    {
        $this->initAuth($sid);
        $this->initIlias();

        // basic check of arguments
        if (!$this->__checkSession($sid)) {
            return $this->__raiseError($this->sauth->getMessage(), $this->sauth->getMessageCode());
        }
        if (!$identity) {
            return $this->__raiseError('No identity given.', 'identity');
        }
        if (!$univis_id) {
            return $this->__raiseError('No univis_id given.', 'univis_is');
        }
        if (!$this->checkPermission('studonAssignMember')) {
            return $this->__raiseError('No permision.', 'permission');
        }

        // Find the internal user for the identity
        $user_id = ilObjUser::_findUserIdByAccount($identity);
        if (!$user_id) {
            global $lng, $ilSetting;
            require_once('Services/User/classes/class.ilUserUtil.php');
            $user_id = ilUserUtil::_createDummyAccount(
                $identity,
                $lng->txt('dummy_user_firstname_mycampus'),
                $lng->txt('dummy_user_lastname_mycampus'),
                $ilSetting->get('mail_external_sender_noreply')
            );
        }
        
        // Find object and reference
        require_once './Services/UnivIS/classes/class.ilUnivis.php';
        $objects = ilUnivis::_getUntrashedObjectsForUnivisId($univis_id);
        if (count($objects) == 0) {
            return $this->__raiseError('No reference found for the given univis_id', 'univis_id');
        } elseif (count($objects) > 1) {
            return $this->__raiseError('More than one reference found for the given univis_id', 'univis_id');
        }
        $object = current($objects);
        $obj_id = $object['obj_id'];
        $ref_id = $object['ref_id'];
        $obj_type = $object['type'];

        switch ($obj_type) {
            // Course
            case 'crs':

                if (!$course = ilObjectFactory::getInstanceByRefId($ref_id, false)) {
                    return $this->__raiseError('Cannot create course instance!', 'univis_id');
                }

                if ($course->getSubscriptionLimitationType() != IL_CRS_SUBSCRIPTION_MYCAMPUS
                    and $course->getSubscriptionType() != IL_CRS_SUBSCRIPTION_MYCAMPUS) {
                    return $this->__raiseError('External subscription not enabled for this course', 'status');
                }

                include_once 'Modules/Course/classes/class.ilCourseParticipants.php';
                $course_members = ilCourseParticipants::_getInstanceByObjId($course->getId());
                return $course_members->add($user_id, IL_CRS_MEMBER);


            // Group
            case 'grp':

                // TODO: support soap assignment for group
                return false;

            // Other types
            default:
                return false;
        }
    }

    public function studonExcludeMember($sid, $identity, $univis_id)
    {
        $this->initAuth($sid);
        $this->initIlias();

        // basic check of arguments
        if (!$this->__checkSession($sid)) {
            return $this->__raiseError($this->sauth->getMessage(), $this->sauth->getMessageCode());
        }
        if (!$identity) {
            return $this->__raiseError('No identity given.', 'identity');
        }
        if (!$univis_id) {
            return $this->__raiseError('No univis_id given.', 'univis_is');
        }
        if (!$this->checkPermission('studonExcludeMember')) {
            return $this->__raiseError('No permision.', 'permission');
        }

        // Find the internal user for the identity
        require_once './Services/UnivIS/classes/class.ilUnivis.php';
        $objects = ilUnivis::_getUntrashedObjectsForUnivisId($univis_id);
        $user_id = ilObjUser::_findUserIdByAccount($identity);
        if (!$user_id) {
            return $this->__raiseError('No user found for the given identity', 'identity');
        }

        // Find object and reference
        if (count($objects) == 0) {
            return $this->__raiseError('No reference found for the given univis_id', 'univis_id');
        } elseif (count($objects) > 1) {
            return $this->__raiseError('More than one reference found for the given univis_id', 'univis_id');
        }
        $object = current($objects);
        $obj_id = $object['obj_id'];
        $ref_id = $object['ref_id'];
        $obj_type = $object['type'];

        //
        switch ($obj_type) {
            // Course
            case 'crs':

                if (!$course = ilObjectFactory::getInstanceByRefId($ref_id, false)) {
                    return $this->__raiseError('Cannot create course instance!', 'univis_id');
                }

                if ($course->getSubscriptionLimitationType() != IL_CRS_SUBSCRIPTION_MYCAMPUS
                    and $course->getSubscriptionType() != IL_CRS_SUBSCRIPTION_MYCAMPUS) {
                    return $this->__raiseError('External subscription not enabled for this course', 'status');
                }

                include_once 'Modules/Course/classes/class.ilCourseParticipants.php';
                $course_members = ilCourseParticipants::_getInstanceByObjId($course->getId());
                if (!$course_members->checkLastAdmin(array($user_id))) {
                    return $this->__raiseError('Cannot deassign last administrator from course', 'status');
                }
                return $course_members->delete($user_id);


            // Group
            case 'grp':

                // TODO: support group
                return false;

            // Other types
            default:
                return false;
        }
    }


    public function studonCopyCourse($sid, $sourceRefId, $targetRefId, $typesToLink=[]) {

        $this->initAuth($sid);
        $this->initIlias();

        global $DIC;

        /** @var ilObjectDefinition $objDefinition */
        $objDefinition = $DIC['objDefinition'];
        $rbacsystem = $DIC->rbac()->system();
        $access = $DIC->access();
        $tree = $DIC->repositoryTree();


        // basic check of arguments
        if (!$this->__checkSession($sid)) {
            return $this->__raiseError($this->__getMessage(), $this->__getMessageCode());
        }

        // does source object exist
        if (!$source_object_type = ilObject::_lookupType($sourceRefId, true)) {
            return $this->__raiseError('No valid source given.', 'Client');
        }

        // does target object exist
        if (!$target_object_type = ilObject::_lookupType($targetRefId, true)) {
            return $this->__raiseError('No valid target given.', 'Client');
        }

        // check the source type
        $allowed_source_types = array('crs');
        if (!in_array($source_object_type, $allowed_source_types)) {
            return $this->__raiseError('No valid source type. Source must be reference id of a course', 'Client');
        }

        // check the target type
        $allowed_target_types = array('cat');
        if (!in_array($target_object_type, $allowed_target_types)) {
            return $this->__raiseError('No valid target type. Target must be reference id of a category', 'Client');
        }

        // checking copy permissions
        if (!$rbacsystem->checkAccess('copy', $sourceRefId)) {
            return $this->__raiseError("Missing copy permissions for object with reference id " . $sourceRefId, 'Client');
        }

        // check if user can create objects of this type in the target
        if (!$rbacsystem->checkAccess('create', $targetRefId, $target_object_type)) {
            return $this->__raiseError('No permission to create objects of type ' . $target_object_type . '!', 'Client');
        }

        // prepare the copy options for all sub objects
        $options = array();
        $nodedata = $tree->getNodeData($sourceRefId);
        $nodearray = $tree->getSubTree($nodedata);
        foreach ($nodearray as $node) {
            if (in_array($node['type'], $typesToLink)) {

                // check linking of sub object
                if (!$objDefinition->allowLink($node['type'])) {
                    return $this->__raiseError("Link for object " . $node['ref_id'] . " of type " . $node['type'] . " is not supported", 'Client');
                }
                if (!$access->checkAccess('write', '', $node['ref_id'])) {
                    return $this->__raiseError("Missing write permissions for object with reference id " .  $node['ref_id'], 'Client');
                }
                $options[$node['ref_id']] = array("type" => ilCopyWizardOptions::COPY_WIZARD_LINK);
            }
            else {

                // check copy of sub object
                if (!$objDefinition->allowCopy($node['type'])) {
                    return $this->__raiseError("Copy for object " . $node['ref_id'] . " of type " . $node['type'] . " is not supported", 'Client');
                }
                if (!$access->checkAccess('copy', '', $node['ref_id'])) {
                    return $this->__raiseError("Missing copy permissions for object with reference id " .  $node['ref_id'], 'Client');
                }
                $options[$node['ref_id']] = array("type" => ilCopyWizardOptions::COPY_WIZARD_COPY);
            }
        }

        // get client id from sid
        $clientid = substr($sid, strpos($sid, "::") + 2);
        $sessionid = str_replace("::" . $clientid, "", $sid);

        // call container clone
        try {
            $source_object = ilObjectFactory::getInstanceByRefId($sourceRefId);
            $ret = $source_object->cloneAllObject(
                $sessionid,
                $clientid,
                $source_object_type,
                $targetRefId,
                $sourceRefId,
                $options,
                true
            );
            return $ret['ref_id'];
        }
        catch (Exception $e) {
            return $this->__raiseError($e->getMessage(), $this->__getMessageCode());
        }
    }

    public function studonSetCourseProperties($sid, $refId,
        $title = null, $description = null, $online = null,
        $courseStart = null, $courseEnd = null,
        $activationStart = null, $activationEnd = null) {

        $this->initAuth($sid);
        $this->initIlias();

        global $DIC;
        $access = $DIC->access();

        // basic check of arguments
        if (!$this->__checkSession($sid)) {
            return $this->__raiseError($this->__getMessage(), $this->__getMessageCode());
        }

        // does source object exist
        if (!$source_object_type = ilObject::_lookupType($refId, true)) {
            return $this->__raiseError('No valid source given.', 'Client');
        }

        // check the source type
        $allowed_source_types = array('crs');
        if (!in_array($source_object_type, $allowed_source_types)) {
            return $this->__raiseError('No valid source type. Source must be reference id of a course', 'Client');
        }

        // checking write permissions
        if (!$access->checkAccess('write', '', $refId)) {
            return $this->__raiseError("Missing write permissions for object with reference id " . $refId, 'Client');
        }

        try {
            /** @var ilObjCourse $course */
            $course = ilObjectFactory::getInstanceByRefId($refId);

            if (isset($title)) {
                $course->setTitle($title);
            }
            if (isset($description)) {
                $course->setDescription($description);
            }
            if (isset($online)) {
                $course->setOfflineStatus(!$online);
            }
            if (!empty($courseStart)) {
                $course->setCourseStart(new ilDate((int) $courseStart, IL_CAL_UNIX));
            }
            if (!empty($courseEnd)) {
                $course->setCourseEnd(new ilDate((int) $courseEnd, IL_CAL_UNIX));
            }
            if (isset($activationStart)) {
                $course->setActivationStart($activationStart);
            }
            if (isset($activationEnd)) {
                $course->setActivationEnd($activationEnd);
            }

            $course->update();
            return true;
        }
        catch (Exception $e) {
            return $this->__raiseError($e->getMessage(), $this->__getMessageCode());
        }
    }

    public function studonAddCourseAdminsByIdentity($sid, $refId, $admins = []) {
        $this->initAuth($sid);
        $this->initIlias();

        global $DIC;
        $access = $DIC->access();
        $lng = $DIC->language();
        $settings = $DIC->settings();

        // basic check of arguments
        if (!$this->__checkSession($sid)) {
            return $this->__raiseError($this->__getMessage(), $this->__getMessageCode());
        }

        // does source object exist
        if (!$source_object_type = ilObject::_lookupType($refId, true)) {
            return $this->__raiseError('No valid source given.', 'Client');
        }

        // check the source type
        $allowed_source_types = array('crs');
        if (!in_array($source_object_type, $allowed_source_types)) {
            return $this->__raiseError('No valid source type. Source must be reference id of a course', 'Client');
        }

        // checking edit permissions permissions
        if (!$access->checkAccess('edit_permission', '', $refId)) {
            return $this->__raiseError("Missing edit permissions for object with reference id " . $refId, 'Client');
        }

        try {
            $course_members = ilCourseParticipants::_getInstanceByObjId(ilObject::_lookupObjId($refId));
            foreach ($admins as $identity) {
                $user_id = ilObjUser::_findUserIdByAccount($identity);
                if (!$user_id) {
                    $user_id = ilUserUtil::_createDummyAccount(
                        $identity,
                        $lng->txt('dummy_admin_firstname_tca'),
                        $lng->txt('dummy_admin_lastname_tca'),
                        $settings->get('mail_external_sender_noreply')
                    );
                }
                $course_members->add($user_id, IL_CRS_ADMIN);
                $course_members->updateNotification($user_id, true);
                $course_members->updateContact($user_id, true);

                // remove the soap admin from contacts
                $course_members->updateContact($DIC->user()->getId(), false);
            }
            return true;
        }
        catch (Exception $e) {
            return $this->__raiseError($e->getMessage(), $this->__getMessageCode());
        }

    }

    public function studonEnableLtiConsumer($sid, $refId, $consumerId,
        $adminRole = 'admin', $instructorRole = 'tutor', $memberRole = 'member') {

        $this->initAuth($sid);
        $this->initIlias();

        global $DIC;
        $access = $DIC->access();

        // basic check of arguments
        if (!$this->__checkSession($sid)) {
            return $this->__raiseError($this->__getMessage(), $this->__getMessageCode());
        }

        // does source object exist
        if (!$source_object_type = ilObject::_lookupType($refId, true)) {
            return $this->__raiseError('No valid source given.', 'Client');
        }

        // check the source type
        $allowed_source_types = array('crs');
        if (!in_array($source_object_type, $allowed_source_types)) {
            return $this->__raiseError('No valid source type. Source must be reference id of a course', 'Client');
        }

        // checking edit permissions permissions
        if (!$access->checkAccess('edit_permission', '', $refId)) {
            return $this->__raiseError("Missing edit permissions for object with reference id " . $refId, 'Client');
        }

        try {
            $connector = new ilLTIDataConnector();
            $consumer = ilLTIToolConsumer::fromGlobalSettingsAndRefId($consumerId, $refId, $connector);

            if (!$consumer->getEnabled()) {
                $consumer->setExtConsumerId($consumerId);
                $consumer->createSecret();
                $consumer->setRefId($refId);
                $consumer->setEnabled(true);
                $consumer->saveLTI($connector);
            }
            // needed to set the consumer key
            $connector->loadToolConsumer($consumer);

            $object_info = new ilLTIProviderObjectSetting($refId, $consumerId);
            $object_info->setAdminRole($adminRole);
            $object_info->setTutorRole($instructorRole);
            $object_info->setMemberRole($memberRole);
            $object_info->save();


            return [
                'consumerKey' => $consumer->getKey(),
                'consumerSecret' => $consumer->getSecret()
            ];
        }
        catch (Exception $e) {
            return $this->__raiseError($e->getMessage(), $this->__getMessageCode());
        }
    }


    /**
    *  check the admin permission via SOAP
    *
    *  currently checked for read permission in the user folder
    *  (may be set with a local role)
    */
    private function checkPermission($a_function = '')
    {
        global $rbacsystem;
        
        return $rbacsystem->checkAccess('read', USER_FOLDER_ID);
    }
}
