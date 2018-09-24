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
	function __construct($use_nusoap = true)
	{
		parent::__construct($use_nusoap);
	}

	
	function studonGetResources($sid, $semester)
	{
		$this->initAuth($sid);
		$this->initIlias();

		// basic check of arguments
		if (!$this->__checkSession($sid))
		{
			return $this->__raiseError($this->sauth->getMessage(),$this->sauth->getMessageCode());
		}
		if (!$semester)
		{
			return $this->__raiseError('No semester given.', 'semester');
		}
		if (!$this->checkPermission('studonHasResource'))
		{
            return $this->__raiseError('No permision.', 'permission');
		}
		require_once './Services/UnivIS/classes/class.ilUnivis.php';
		require_once './Services/Link/classes/class.ilLink.php';
		$objects = ilUnivis::_getUntrashedObjectsForSemester($semester);
		$resources = array();
		foreach($objects as $data)
		{
			$resource = array();
			$resource['univis_id'] = $data['import_id'];
			$resource['perma_link'] = ilLink::_getStaticLink($data['ref_id']);
			$resources[] = $resource;
		}
		return $resources;		
	}
	
	function  studonHasResource($sid, $univis_id)
	{
		$this->initAuth($sid);
		$this->initIlias();

		// basic check of arguments
		if (!$this->__checkSession($sid))
		{
			return $this->__raiseError($this->sauth->getMessage(),$this->sauth->getMessageCode());
		}
		if (!$univis_id)
		{
			return $this->__raiseError('No univis_id given.', 'univis_id');
		}
		if (!$this->checkPermission('studonHasResource'))
		{
            return $this->__raiseError('No permision.', 'permission');
		}

		require_once './Services/UnivIS/classes/class.ilUnivis.php';
		$objects = ilUnivis::_getUntrashedObjectsForUnivisId($univis_id);
		if (count($objects) > 1)
		{
			return $this->__raiseError('More than one reference found for the given univis_id', 'univis_id');
		}
		elseif (count($objects) == 1)
		{
			return true;
		}
		else
		{
			return false;
		}
	}


    function  studonGetPermaLink($sid, $univis_id)
	{
		$this->initAuth($sid);
		$this->initIlias();

		// basic check of arguments
		if(!$this->__checkSession($sid))
		{
			return $this->__raiseError($this->sauth->getMessage(),$this->sauth->getMessageCode());
		}
		if (!$univis_id)
		{
			return $this->__raiseError('No univis_id given.', 'univis_id');
		}
		if (!$this->checkPermission('studonGetPermaLink'))
		{
            return $this->__raiseError('No permision.', 'permission');
		}

		// Find object and reference
		require_once './Services/UnivIS/classes/class.ilUnivis.php';
		$objects = ilUnivis::_getUntrashedObjectsForUnivisId($univis_id);
		if (count($objects) == 0)
		{
			return $this->__raiseError('No reference found for the given univis_id', 'univis_id');
		}
		elseif (count($objects) > 1)
		{
			return $this->__raiseError('More than one reference found for the given univis_id', 'univis_id');
		}
		$object = current($objects);
		$obj_id = $object['obj_id'];
		$ref_id = $object['ref_id'];

		// Get and return the link
		include_once './Services/Link/classes/class.ilLink.php';
		return ilLink::_getStaticLink($ref_id);
	}

    function  studonGetMembers($sid, $univis_id)
    {
        $this->initAuth($sid);
        $this->initIlias();

        // basic check of arguments
        if(!$this->__checkSession($sid))
        {
            return $this->__raiseError($this->sauth->getMessage(),$this->sauth->getMessageCode());
        }
        if (!$univis_id)
        {
            return $this->__raiseError('No univis_id given.', 'univis_id');
        }
        if (!$this->checkPermission('studonGetMembers'))
        {
            return $this->__raiseError('No permision.', 'permission');
        }

        // Find object and reference
        require_once './Services/UnivIS/classes/class.ilUnivis.php';
        $objects = ilUnivis::_getUntrashedObjectsForUnivisId($univis_id);
        if (count($objects) == 0)
        {
            return $this->__raiseError('No reference found for the given univis_id', 'univis_id');
        }
        elseif (count($objects) > 1)
        {
            return $this->__raiseError('More than one reference found for the given univis_id', 'univis_id');
        }
        $object = current($objects);
        $obj_id = $object['obj_id'];
        $ref_id = $object['ref_id'];
        $obj_type = $object['type'];

        switch ($obj_type)
        {
            // Course
            case 'crs':

                if(!$course = ilObjectFactory::getInstanceByRefId($ref_id, false))
                {
                    return $this->__raiseError('Cannot create course instance!','univis_id');
                }

                include_once 'Modules/Course/classes/class.ilCourseParticipants.php';
                $course_members = ilCourseParticipants::_getInstanceByObjId($course->getId());

                //return $course_members->getParticipants();

                $users = ilObjUser::_getUserData($course_members->getParticipants());

                $externals = array();
                foreach ($users as $user)
                {
                    if ($user['ext_account'])
                    {
                        $externals[] = $user['ext_account'];
                    }
                }

                return $externals;


            // Group
            case 'grp':

                // TODO: support group
                return $this->__raiseError('The given resource is not a course!','univis_id');

            // Other types
            default:
                return $this->__raiseError('The given resource is not a course!','univis_id');
        }
    }


	function  studonIsSoapAssignable($sid, $univis_id)
	{
		$this->initAuth($sid);
		$this->initIlias();

		// basic check of arguments
		if(!$this->__checkSession($sid))
		{
			return $this->__raiseError($this->sauth->getMessage(),$this->sauth->getMessageCode());
		}
		if (!$univis_id)
		{
			return $this->__raiseError('No univis_id given.', 'univis_id');
		}
		if (!$this->checkPermission('studonIsSoapAssignable'))
		{
            return $this->__raiseError('No permision.', 'permission');
		}

		// Find object and reference
		require_once './Services/UnivIS/classes/class.ilUnivis.php';
		$objects = ilUnivis::_getUntrashedObjectsForUnivisId($univis_id);
		if (count($objects) == 0)
		{
			return $this->__raiseError('No reference found for the given univis_id', 'univis_id');
		}
		elseif (count($objects) > 1)
		{
			return $this->__raiseError('More than one reference found for the given univis_id', 'univis_id');
		}
		$object = current($objects);
		$obj_id = $object['obj_id'];
		$ref_id = $object['ref_id'];
		$obj_type = $object['type'];

		
		// Check the subsrciption type
		switch ($obj_type)
		{
			// Course
			case 'crs':

				if(!$course = ilObjectFactory::getInstanceByRefId($ref_id, false))
				{
					return $this->__raiseError('Cannot create course instance!','univis_id');
				}

				if ($course->getSubscriptionLimitationType() == IL_CRS_SUBSCRIPTION_MYCAMPUS
                    or $course->getSubscriptionType() == IL_CRS_SUBSCRIPTION_MYCAMPUS)
				{
	 				return true;
				}
				else
				{
					return false;
				}
				
			// Group
			case 'grp':
			
			    // TODO: support soap assignment for group
			    return false;
			    
			// Other types
			default:
				return false;
		}
	}

    function studonIsAssigned($sid, $identity, $univis_id)
    {
        $this->initAuth($sid);
        $this->initIlias();

        // basic check of arguments
        if(!$this->__checkSession($sid))
        {
            return $this->__raiseError($this->sauth->getMessage(),$this->sauth->getMessageCode());
        }
        if (!$identity)
        {
            return $this->__raiseError('No identity given.', 'identity');
        }
        if (!$univis_id)
        {
            return $this->__raiseError('No univis_id given.', 'univis_is');
        }
        if (!$this->checkPermission('studonIsAssigned'))
        {
            return $this->__raiseError('No permision.', 'permission');
        }

        // Find the internal user for the identity
        $user_id = ilObjUser::_findUserIdByAccount($identity);
        if (!$user_id)
        {
            return false;
        }

        // Find object and reference
        require_once './Services/UnivIS/classes/class.ilUnivis.php';
        $objects = ilUnivis::_getUntrashedObjectsForUnivisId($univis_id);
        if (count($objects) == 0)
        {
            return $this->__raiseError('No reference found for the given univis_id', 'univis_id');
        }
        elseif (count($objects) > 1)
        {
            return $this->__raiseError('More than one reference found for the given univis_id', 'univis_id');
        }

        // Return if user is participant
        $object = current($objects);
        include_once 'Services/Membership/classes/class.ilParticipants.php';
        return ilParticipants::_isParticipant($object['ref_id'], $user_id);
    }


	function  studonAssignMember($sid, $identity, $univis_id)
	{
		$this->initAuth($sid);
		$this->initIlias();

		// basic check of arguments
		if(!$this->__checkSession($sid))
		{
			return $this->__raiseError($this->sauth->getMessage(),$this->sauth->getMessageCode());
		}
		if (!$identity)
		{
			return $this->__raiseError('No identity given.', 'identity');
		}
		if (!$univis_id)
		{
			return $this->__raiseError('No univis_id given.', 'univis_is');
		}
		if (!$this->checkPermission('studonAssignMember'))
		{
            return $this->__raiseError('No permision.', 'permission');
		}

		// Find the internal user for the identity
		$user_id = ilObjUser::_findUserIdByAccount($identity);
		if (!$user_id)
		{
			global $lng, $ilSetting;
			require_once('Services/User/classes/class.ilUserUtil.php');
	        $user_id = ilUserUtil::_createDummyAccount(
	        	$identity,
	        	$lng->txt('dummy_user_firstname_mycampus'),
	        	$lng->txt('dummy_user_lastname_mycampus'),
	        	$ilSetting->get('mail_external_sender_noreply'));
		}
		
		// Find object and reference
		require_once './Services/UnivIS/classes/class.ilUnivis.php';
		$objects = ilUnivis::_getUntrashedObjectsForUnivisId($univis_id);
		if (count($objects) == 0)
		{
			return $this->__raiseError('No reference found for the given univis_id', 'univis_id');
		}
		elseif (count($objects) > 1)
		{
			return $this->__raiseError('More than one reference found for the given univis_id', 'univis_id');
		}
		$object = current($objects);
		$obj_id = $object['obj_id'];
		$ref_id = $object['ref_id'];
		$obj_type = $object['type'];

		switch ($obj_type)
		{
			// Course
			case 'crs':

				if(!$course = ilObjectFactory::getInstanceByRefId($ref_id, false))
				{
					return $this->__raiseError('Cannot create course instance!','univis_id');
				}

				if ($course->getSubscriptionLimitationType() != IL_CRS_SUBSCRIPTION_MYCAMPUS
                    and $course->getSubscriptionType() != IL_CRS_SUBSCRIPTION_MYCAMPUS)
				{
	 				return $this->__raiseError('External subscription not enabled for this course', 'status');
				}

				include_once 'Modules/Course/classes/class.ilCourseParticipants.php';
				$course_members = ilCourseParticipants::_getInstanceByObjId($course->getId());
				return $course_members->add($user_id,IL_CRS_MEMBER);


			// Group
			case 'grp':

			    // TODO: support soap assignment for group
			    return false;

			// Other types
			default:
				return false;
		}
	}

	function  studonExcludeMember($sid, $identity, $univis_id)
	{
		$this->initAuth($sid);
		$this->initIlias();

		// basic check of arguments
		if(!$this->__checkSession($sid))
		{
			return $this->__raiseError($this->sauth->getMessage(),$this->sauth->getMessageCode());
		}
		if (!$identity)
		{
			return $this->__raiseError('No identity given.', 'identity');
		}
		if (!$univis_id)
		{
			return $this->__raiseError('No univis_id given.', 'univis_is');
		}
		if (!$this->checkPermission('studonExcludeMember'))
		{
            return $this->__raiseError('No permision.', 'permission');
		}

		// Find the internal user for the identity
		require_once './Services/UnivIS/classes/class.ilUnivis.php';
		$objects = ilUnivis::_getUntrashedObjectsForUnivisId($univis_id);
		$user_id = ilObjUser::_findUserIdByAccount($identity);
		if (!$user_id)
		{
			return $this->__raiseError('No user found for the given identity', 'identity');
		}

		// Find object and reference
		if (count($objects) == 0)
		{
			return $this->__raiseError('No reference found for the given univis_id', 'univis_id');
		}
		elseif (count($objects) > 1)
		{
			return $this->__raiseError('More than one reference found for the given univis_id', 'univis_id');
		}
		$object = current($objects);
		$obj_id = $object['obj_id'];
		$ref_id = $object['ref_id'];
		$obj_type = $object['type'];

		//
		switch ($obj_type)
		{
			// Course
			case 'crs':

				if(!$course = ilObjectFactory::getInstanceByRefId($ref_id, false))
				{
					return $this->__raiseError('Cannot create course instance!','univis_id');
				}

				if ($course->getSubscriptionLimitationType() != IL_CRS_SUBSCRIPTION_MYCAMPUS
                    and $course->getSubscriptionType() != IL_CRS_SUBSCRIPTION_MYCAMPUS)
				{
	 				return $this->__raiseError('External subscription not enabled for this course', 'status');
				}

				include_once 'Modules/Course/classes/class.ilCourseParticipants.php';
				$course_members = ilCourseParticipants::_getInstanceByObjId($course->getId());
				if(!$course_members->checkLastAdmin(array($user_id)))
				{
					return $this->__raiseError('Cannot deassign last administrator from course','status');
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


	/**
	*  check the admin permission via SOAP
	*
	*  currently checked for read permission in the user folder
	*  (may be set with a local role)
	*/
	private function checkPermission($a_function = '')
	{
		global $rbacsystem;
		
		return $rbacsystem->checkAccess('read',USER_FOLDER_ID);
	}
}
?>
