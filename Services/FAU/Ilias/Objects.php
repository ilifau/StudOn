<?php

namespace FAU\Ilias;

use FAU\Study\Data\ImportId;
use ILIAS\DI\Container;
use ilObject;
use FAU\Study\Data\Course;
use ilObjGroupAccess;
use FAU\Ilias\Data\ContainerInfo;
use FAU\Ilias\Data\ListProperty;
use ilWaitingList;
use ilCourseWaitingList;
use ilGroupWaitingList;
use ilGroupParticipants;
use ilObjCourse;
use ilObjGroup;
use FAU\Study\Data\Term;
use FAU\Study\Data\Event;
use FAU\Tools\Settings;
use ilObjCourseAccess;
use ilCourseParticipants;
use ilGroupParticipant;
use ilCourseParticipant;
use FAU\Ilias\Helper\WaitingListConstantsHelper;

/**
 * Functions to handle with ILIAS objects
 */
class Objects
{
    protected Container $dic;
    protected Settings $settings;

    public function __construct(Container $dic)
    {
        $this->dic = $dic;
        $this->settings = $dic->fau()->tools()->settings();
    }

    /**
     * Check if a container object has child objects which are not deleted
     */
    public function hasUndeletedContents(int $ref_id) : bool
    {
        /** @noinspection PhpParamsInspection */
        foreach ($this->dic->repositoryTree()->getChildIds($ref_id) as $child_id) {
            if (!ilObject::_isInTrash($child_id)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get a ref id for an object id that is not in the trash
     * @param int $obj_id
     * @return int|null
     */
    public function getUntrashedReference(int $obj_id) : ?int
    {
        foreach (ilObject::_getAllReferences($obj_id) as $ref_id) {
            if (!ilObject::_isInTrash($ref_id)) {
                return $ref_id;
            }
        }
        return null;
    }
    
/**
     * Get an overview list of courses and groups with pending registrations of the current user
     * The list includes visible courses and groups which are favourites or have the user on the waiting list
     * @param bool $sort_by_end  sort the list by the registration end instead of registration start
     * @return ContainerInfo[]
     */
    public function getRegistrationsOverviewInfos(bool $sort_by_end = false) : array
    {
        $infos = [];
        foreach ($this->dic->fau()->ilias()->repo()->findRegistrationsOverviewRefIds($this->dic->user()->id) as $ref_id) {
            $info = $this->getContainerInfo($ref_id);
            if (!$this->dic->access()->checkAccess('visible', '', $info->getRefId())
                || $info->isAssigned()    
                || (!$info->getRegEnabled() && !$info->isOnWaitingList())) {
                continue;
            }
            
            if ($sort_by_end) {
                $key = $info->getRegEnd()->get(IL_CAL_DATETIME) . '@' . $info->getRefId();
            }
            else {
                $key = $info->getRegStart()->get(IL_CAL_DATETIME) . '@' . $info->getRefId();
            }
            $infos[$key] = $info;
        }
        ksort($infos);
        return $infos;
    }
    
    /**
     * Get the reference to the ilias course or group for a course
     * @param bool $justForLinking   take also transferred objects into account for linking former courses
     */
    public function getIliasRefIdForCourse(Course $course, $justForLinking = false) : ?int
    {
        $obj_id = $course->getIliasObjId();
        if (empty($obj_id) && $justForLinking) {
            $obj_id = $course->getIliasObjIdTrans();
        }
        
        if (!empty($obj_id)) {
            foreach (ilObject::_getAllReferences($obj_id) as $ref_id) {
                if (!ilObject::_isInTrash($ref_id)) {
                    return $ref_id;
                }
            }
        }
        return null;
    }

    /**
     * Get the ref_ids on a repository path for which a collected export of course data is possible
     * @return int[]
     */
    public function getPathRefIdsWithCollectedExports(int $ref_id) : array
    {
        $export_ids = $this->dic->fau()->org()->repo()->getRefIdsWithCollectedExports();
        
        $result_ids = [];
        $allowed = false;
        foreach ($this->dic->repositoryTree()->getPathId($ref_id) as $path_id) {
            if ($allowed || in_array($path_id, $export_ids)) {
                $result_ids[] = $path_id;
                $allowed = true;
            }
        }
        return $result_ids;
    }
    
    /**
     * Find the parent course of a group
     */
    public function findParentIliasCourse(int $ref_id) : ?int
    {
        foreach ($this->dic->repositoryTree()->getPathId($ref_id) as $path_id) {
            if ($path_id != $ref_id && ilObject::_lookupType($path_id, true) == 'crs') {
                return $path_id;
            }
        }
        return null;
    }

    /**
     * Get a list if child groups
     * @param int $ref_id
     * @return string[] group titles indexed by their ref_id
     */
    public function getChildGroupsList(int $ref_id) : array
    {
        $list = [];
        /** @noinspection PhpParamsInspection */
        foreach ($this->dic->repositoryTree()->getChildIds($ref_id) as $child_id) {
            if (ilObject::_lookupType($child_id, true) == 'grp' && !ilObject::_isInTrash($child_id)) {
                $obj_id = ilObject::_lookupObjId($child_id);
                $list[$child_id] = ilObject::_lookupTitle($obj_id);
            }
        }
        asort($list);
        return $list;

    }

    /**
     * Find parallel groups that are enclosed in the course
     * @return int[]
     */
    public function findChildParallelGroups(int $ref_id, $useCache = true) : array
    {
        $ref_ids = [];
        /** @noinspection PhpParamsInspection */
        foreach ($this->dic->repositoryTree()->getChildIds($ref_id) as $child_id) {
            if (ilObject::_lookupType($child_id, true) == 'grp' && !ilObject::_isInTrash($child_id)) {
                $obj_id = ilObject::_lookupObjId($child_id);
                if ($this->dic->fau()->study()->isObjectForCampo($obj_id, $useCache)) {
                    $ref_ids[] = $child_id;
                }
            }
        }
        return $ref_ids;
    }
   
    /**
     * Extend the registration info generated for courses and groups
     * This adds the number of members, subscribers, free places and user status
     * Unified implementation for courses and groups as a basis for getContainerInfo()
     *
     * @see ilObjCourseAccess::lookupRegistrationInfo()
     * @see ilObjGroupAccess::lookupRegistrationInfo()
     * @see Objects::getContainerInfo()
     */
    public function extendRegistrationInfo(array $info, int $obj_id, int $ref_id, string $type) : array
    {
        $lng = $this->dic->language();
        $access = $this->dic->access();
        $user = $this->dic->user();
        
        switch ($type) {
            case 'crs':
                $partObj = ilCourseParticipant::_getInstanceByObjId($obj_id, $user->getId());
                break;
            case 'grp':
                $partObj = ilGroupParticipant::_getInstanceByObjId($obj_id, $user->getId());
                break;
            default:
                return $info;
        }
        
        $registration_possible = $info['reg_info_enabled'] ?? false;
        $has_mem_limit = $info['reg_info_mem_limit'] ?? false;
        $max_members = $info['reg_info_max_members'] ?? 0;

        $members = $partObj->getNumberOfMembers();
        $waiting = ilWaitingList::lookupListSize($obj_id);
        $free_places = max($max_members - $members, 0);
        
        // set the data fields needed for getContainerInfo
        $info['reg_info_members'] = $members;
        $info['reg_info_subscribers'] = $waiting;
        $info['reg_info_free_places'] = $free_places;
        $info['reg_info_is_assigned'] = $partObj->isAssigned();
        
        // decide if info about free places should be shown in properties
        if ($has_mem_limit && $registration_possible) {
            // show to all if registration is possible
            $show_mem_limit = true;
            $show_hidden_notice = false;
            
        } elseif ($has_mem_limit && $access->checkAccess('write', '', $ref_id)) {
            // show only to admins if registration is not possible
            $show_mem_limit = true;
            $show_hidden_notice = true;
            
        } else {
            $show_mem_limit = false;
            $show_hidden_notice = false;
        }

        // add one property with the info about membership limitation (max, free, waiting)
        if ($show_mem_limit) {
            $limits = array();
            if ($show_hidden_notice) {
                $limits[] = $this->dic->language()->txt("mem_max_users_hidden");
            }
            $limits[] = $lng->txt("mem_max_users") . $max_members;
            $limits[] = $lng->txt("mem_free_places") . ': ' . $free_places;
            if ($waiting > 0) {
                $limits[] = $lng->txt("subscribers_or_waiting_list") . ': ' . (string) ($waiting);
            }
            $info['reg_info_list_prop_limit']['property'] = '';
            $info['reg_info_list_prop_limit']['value'] = implode(' &nbsp; ', $limits);
        }

        // add one property with own registration status
        $info['reg_info_waiting_status'] = ilWaitingList::_getStatus($user->getId(), $obj_id);
        switch ($info['reg_info_waiting_status'] ) {
            case WaitingListConstantsHelper::REQUEST_NOT_TO_CONFIRM:
                $status = $lng->txt('on_waiting_list');
                break;
            case WaitingListConstantsHelper::REQUEST_TO_CONFIRM:
                $status = $lng->txt('sub_status_pending');
                break;
            case WaitingListConstantsHelper::REQUEST_CONFIRMED:
                $status = $lng->txt('sub_status_confirmed');
                break;
            default:
                $status = '';
        }
        if ($status) {
            $info['reg_info_list_prop_status']['property'] = $lng->txt('member_status');
            $info['reg_info_list_prop_status']['value'] = $status;
        }

        return $info;
    } 

    /**
     * Get the basic info of a registration container (course or group)
     */
    public function getContainerInfo(int $ref_id, bool $with_participants = false, bool $with_waiting_list = false) : ContainerInfo
    {
        $obj_id = ilObject::_lookupObjId($ref_id);
        $type = ilObject::_lookupType($obj_id);
        $title = ilObject::_lookupTitle($obj_id);
        $description = ilObject::_lookupDescription($obj_id);
        $import_id = ilObject::_lookupImportId($obj_id);

        $participants = null;
        $waiting_list = null;
        
        switch ($type) {
            case 'grp':
                $info = ilObjGroupAccess::lookupRegistrationInfo($obj_id, $ref_id);
                
                if ($with_participants) {
                    $participants = ilGroupParticipants::getInstance($ref_id);
                }
                if ($with_waiting_list) {
                    $waiting_list = new ilGroupWaitingList($obj_id);
                }
                break;
                
            case 'crs':
                $info = ilObjCourseAccess::lookupRegistrationInfo($obj_id, $ref_id);

                if ($with_participants) {
                    $participants = ilCourseParticipants::getInstance($ref_id);
                }
                if ($with_waiting_list) {
                    $waiting_list = new ilCourseWaitingList($obj_id);
                }
                break;

            default:
                // fault tolerance: provide a dummy info for other object types - should  not be needed
                $info = [
                    'reg_info_enabled' => false,
                    'reg_info_unlimited' => false,
                    'reg_info_start' => new \ilDateTime(),
                    'reg_info_end' => new \ilDateTime(),
                    'reg_info_mem_limit' => false,
                    'reg_info_waiting_list' => false,
                    'reg_info_max_members' => 0,
                    'reg_info_members' => 0,
                    'reg_info_subscribers' => 0,
                    'reg_info_waiting_status' => ilWaitingList::REQUEST_NOT_ON_LIST,
                    'reg_info_is_assigned' => 0
                ];

        }
        
        // set basic container info
        $cont_info  = new ContainerInfo(
            $title,
            $description,
            $import_id,
            (string) $type,
            (int) $ref_id,
            (int) $obj_id,
            (bool) $info['reg_info_enabled'],
            (bool) !$info['reg_info_unlimited'],
            $info['reg_info_start'],
            $info['reg_info_end'],
            (bool) $info['reg_info_mem_limit'],
            (bool) $info['reg_info_waiting_list'],
            (int) $info['reg_info_max_members'],
            (int) $info['reg_info_members'],
            (int) $info['reg_info_subscribers'],
            (int) $info['reg_info_waiting_status'],
            (bool) $info['reg_info_is_assigned']
        );

        // add optional lists
        if ($with_participants) {
            $cont_info = $cont_info->withParticipants($participants);
        }
        if ($with_waiting_list) {
            $cont_info = $cont_info->withWaitingList($waiting_list);
        }

        // add the registration info for parallel groups
        // not added by ilObjGroupAccess::lookupRegistrationInfo because registration is disabled for parallel group
        $limits = [];
        if ($cont_info->hasMaxMembers()) {
            $limits[] = $this->dic->language()->txt("mem_max_users") . $cont_info->getMaxMembers();
            $limits[] = $this->dic->language()->txt("mem_free_places") . ': ' . $cont_info->getFreePlaces();
        }
        if ($cont_info->hasMaxMembers() || $cont_info->getSubscribers() > 0) {
            $limits[] = $this->dic->language()->txt("subscribers_or_waiting_list") . ': ' . (string) ($cont_info->getSubscribers());
        }
        if (!empty($limits)) {
            $cont_info = $cont_info->withProperty((new ListProperty(null, implode(' &nbsp; ', $limits)))
                ->withKey(ListProperty::KEY_LIMITS));
        }

        // add other properties
        foreach (['reg_info_list_prop_status'] as $key) {
            if (isset($info[$key])) {
                $cont_info = $cont_info->withProperty((new ListProperty($info[$key]['property'], $info[$key]['value']))
                ->withKey(ListProperty::KEY_STATUS));
            }
        }

        return $cont_info;
    }    

    /**
     * Get the infos about the parallel groups in a course or about all peer groups of a group
     * @param int $ref_id   ref_id of the course or of one parallel group
     * @return ContainerInfo[]
     */
    public function getParallelGroupsInfos($ref_id, bool $with_participants = false, bool $with_waiting_list = false) : array
    {
        $infos = [];
        if (ilObject::_lookupType($ref_id, true) == 'grp') {
            $ref_id = (int) $this->findParentIliasCourse($ref_id);
        }
        foreach ( $this->findChildParallelGroups($ref_id) as $group_ref_id) {
            $group = $this->getContainerInfo($group_ref_id, $with_participants, $with_waiting_list);
            $infos[$group->getTitle(). $group->getRefId()] = $group;
        }
        ksort($infos);
        return array_values($infos);
    }

    /**
     * Get the waiting lists of a course and its enclosed parallel groups
     * @param int $ref_id ref_id of the course or of one parallel group
     * @return ilWaitingList[]  the first element is the list of the course
     */
    public function getCourseAndParallelGroupsWaitingLists($ref_id) : array
    {
        $lists = [];

        // add the list of the course
        if (ilObject::_lookupType($ref_id, true) == 'grp') {
            $ref_id = (int) $this->findParentIliasCourse($ref_id);
        }
        $lists[] = new ilCourseWaitingList(ilObject::_lookupObjId($ref_id));

        // add the lists of the groups
        foreach ( $this->findChildParallelGroups($ref_id) as $group_ref_id) {
            $lists[] = new ilGroupWaitingList(ilObject::_lookupObjId($group_ref_id));
        }
        return $lists;
    }


    /**
     * Get the object ids of an ILIAS course or group or of its nested parallel groups
     * - An ilias course with nested parallel groups will return the ids of its parallel groups
     * - An ilias course or group directly connected with a campo course will return its own id
     * Used to query for the selected modules of participants
     * Used to force a sending of members to campo
     *
     * @param ilObjCourse|ilObjGroup $object
     * @return int[]
     */
    public function getParallelObjectIds(\ilObject $object) : array
    {
        if (!$this->isRegistrationHandlerSupported($object)) {
            return [];
        }
        elseif (empty(ImportId::fromString($object->getImportId())->getEventId())) {
            return [];
        }
        elseif ($object->isParallelGroup() || !$object->hasParallelGroups()) {
            return [$object->getId()];
        }

        $obj_ids = [];
        foreach ($this->findChildParallelGroups($object->getRefId()) as $ref_id) {
            $obj_ids[] = ilObject::_lookupObjId($ref_id);
        }
        return $obj_ids;
    }


    /**
     * Check if an object is a parallel group
     * @param ilObjCourse|ilObjGroup $object
     */
    public function isParallelGroup(\ilObject $object) : bool
    {
        if ($this->isRegistrationHandlerSupported($object)) {
            return $object->isParallelGroup();
        }
        return false;
    }

    /**
     * Check if an object is a parallel group or parent course of a parallel group
     * @param ilObjCourse|ilObjGroup $object
     */
    public function isParallelGroupOrParentCourse(\ilObject $object) : bool
    {
        if ($this->isRegistrationHandlerSupported($object)) {
            return $object->isParallelGroup() || $object->hasParallelGroups();
        }
        return false;
    }

    /**
     * Check if the registration handler is supported for an object
     * @param ilObject $object
     * @return bool
     */
    public function isRegistrationHandlerSupported(\ilObject $object) : bool
    {
        return $object instanceof ilObjCourse || $object instanceof ilObjGroup;
    }

    /**
     * Check if referenced object is a course with enclosed parallel groups
     * @param int $ref_id
     * @return bool
     */
    public function refHasParallelGroups(int $ref_id) : bool
    {
        $type = ilObject::_lookupType($ref_id, true);
        $obj_id = ilObject::_lookupObjId($ref_id);
        $import_id = $this->dic->fau()->study()->repo()->getImportId($obj_id);
        return ($type == 'crs' && !empty($import_id->getEventId()) && empty($import_id->getCourseId()));
    }

    /**
     * Check if referenced object is a group for a parallel group
     * @param int $ref_id
     * @return bool
     */
    public function refIsParallelGroup(int $ref_id) : bool
    {
        $type = ilObject::_lookupType($ref_id, true);
        $obj_id = ilObject::_lookupObjId($ref_id);
        $import_id = $this->dic->fau()->study()->repo()->getImportId($obj_id);
        return ($type == 'grp' && !empty($import_id->getCourseId()));
    }



    /**
     * Handle the update of an ILIAS object
     * eventually save a change of the maximum members
     * @param int $obj_id
     */
    public function handleUpdate(int $obj_id)
    {
        $importId = ImportId::fromString(\ilObject::_lookupImportId($obj_id));
        $course_id = $importId->getCourseId();
        $stagingRepo = $this->dic->fau()->staging()->repo();

        if (empty($course_id) || empty($stagingRepo)) {
            // not a relevant course not connected
            return;
        }

        // get the related campo course
        foreach ($this->dic->fau()->study()->repo()->getCoursesByIliasObjId($obj_id) as $campoCourse) {
            break;
        }
        if (!isset($campoCourse)) {
            return;
        }

        // check if the maximum of members is changed
        $maximum = null;
        switch (ilObject::_lookupType($obj_id)) {

            case 'grp':
                $group = new ilObjGroup($obj_id, false);
                if ($group->isMembershipLimited()) {
                    $maximum = $group->getMaxMembers();
                }
                break;

            case 'crs':
                $course = new ilObjCourse($obj_id, false);
                if ($course->isSubscriptionMembershipLimited()) {
                    $maximum = $course->getSubscriptionMaxMembers();
                }
                break;
        }
        if($maximum !== null) {
            $maximum = (int) $maximum;
        }

        // transmit the change of maximum members to campo, if needed
        if ($maximum !== $campoCourse->getAttendeeMaximum()) {

            // directly write the change back to staging database for campo
            if (!empty($stagingCourse = $this->dic->fau()->staging()->repo()->getStudOnCourse((int) $course_id))) {
                $this->dic->fau()->staging()->repo()->save(
                    $stagingCourse->withAttendeeMaximum($maximum)
                );
            }

            // prevent multiple changes for further updates
            $this->dic->fau()->study()->repo()->save($campoCourse->withAttendeeMaximum($maximum));
        }
    }

    /**
     * Create an ILIAS course for a campo event and/or course (parallel group)
     * The ilias course will always work as a container for the event
     * If a campo course is given then the ilias course should work as container for that parallel group
     */
    public function createIliasCourse(int $parent_ref_id, Term $term, Event $event, ?Course $course): ilObjCourse
    {
        $object = new ilObjCourse();
        $object->setTitle($event->getTitle()); // will be changed updateIliasCourse
        $object->setImportId(ImportId::fromObjects($term, $event, $course)->toString());
        $object->setOwner($this->settings->getDefaultOwnerId());
        $object->create();
        $object->createReference();
        $object->putInTree($parent_ref_id);
        $object->setPermissions($parent_ref_id);
        if ($this->dic->fau()->study()->repo()->countCoursesOfEventInTerm($event->getEventId(), $term) > 1) {
            $object->applyDidacticTemplate($this->settings->getCourseDidacticTemplateId());
        }
        $object->setOfflineStatus(false);
        $object->update();
        return $object;
    }

    /**
     * Create an ILIAS group for a campo course (parallel group)
     */
    public function createIliasGroup(int $parent_ref_id, Term $term, Event $event, Course $course): ilObjGroup
    {
        $object = new ilObjGroup();
        $object->setTitle($course->getTitle()); // will be changed updateIliasGroup
        $object->setImportId(ImportId::fromObjects($term, $event, $course)->toString());
        $object->setOwner($this->settings->getDefaultOwnerId());
        $object->create();
        $object->createReference();
        $object->putInTree($parent_ref_id);
        $object->setPermissions($parent_ref_id);
        $object->applyDidacticTemplate($this->settings->getGroupDidacticTemplateId());
        return $object;
    }


    /**
     * Build the object title
     */
    public function buildTitle(Term $term, Event $event, ?Course $course) : string
    {
        if (isset($course)) {
            $title = $course->getTitle();
            if ($this->dic->fau()->study()->repo()->countCoursesOfEventInTerm($event->getEventId(), $term) > 1) {
                $title .= $course->getKParallelgroupId() 
                    ? ' ( ' . $this->dic->language()->txt('fau_campo_course') . ' ' . $course->getKParallelgroupId() . ')' 
                    : '';
            }
        }
        else {
            $title = $event->getTitle();
        }
        return (string) $title;
    }

    /**
     * Build the object description
     */
    public function buildDescription(Event $event, ?Course $course) : string
    {
        $desc = [];
        if ($event->getEventtype()) {
            $desc[] = $event->getEventtype();
        }
        if ($event->getShorttext()) {
            $desc[] = $event->getShorttext();
        }
        if (isset($course)) {
            if ($course->getHoursPerWeek()) {
                $desc[] = $course->getHoursPerWeek() . ' ' . $this->dic->language()->txt('fau_sws');
            }
            if ($course->getTeachingLanguage()) {
                $desc[] = $course->getTeachingLanguage();
            }
        }

        return implode(', ', $desc);
    }

}