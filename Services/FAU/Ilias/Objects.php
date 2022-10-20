<?php

namespace FAU\Ilias;

use FAU\Staging\Data\StudonChange;
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
     * Get the reference to the ilias course or group for a course
     */
    public function getIliasRefIdForCourse(Course $course) : ?int
    {
        if (!empty($course->getIliasObjId())) {
            foreach (ilObject::_getAllReferences($course->getIliasObjId()) as $ref_id) {
                if (!ilObject::_isInTrash($ref_id)) {
                    return $ref_id;
                }
            }
        }
        return null;
    }

    /**
     * Find the parent course of a group
     */
    public function findParentIliasCourse(int $ref_id) : ?int
    {
        foreach ($this->dic->repositoryTree()->getPathId($ref_id) as $path_id) {
            if (ilObject::_lookupType($path_id, true) == 'crs') {
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
    public function findChildParallelGroups(int $ref_id) : array
    {
        $ref_ids = [];
        /** @noinspection PhpParamsInspection */
        foreach ($this->dic->repositoryTree()->getChildIds($ref_id) as $child_id) {
            if (ilObject::_lookupType($child_id, true) == 'grp' && !ilObject::_isInTrash($child_id)) {
                $obj_id = ilObject::_lookupObjId($child_id);
                if ($this->dic->fau()->study()->isObjectForCampo($obj_id)) {
                    $ref_ids[] = $child_id;
                }
            }
        }
        return $ref_ids;
    }

    /**
     * Get the basic info of a parallel group
     */
    public function getParallelGroupInfo(int $ref_id, bool $with_participants = false, bool $with_waiting_list = false) : ContainerInfo
    {
        $obj_id = ilObject::_lookupObjId($ref_id);
        $info = ilObjGroupAccess::lookupRegistrationInfo($obj_id, $ref_id);

        $groupInfo  = new ContainerInfo(
            ilObject::_lookupTitle($obj_id),
            ilObject::_lookupDescription($obj_id),
            'grp',
            $ref_id,
            $obj_id,
            (bool) $info['reg_info_mem_limit'],
            (bool) $info['reg_info_waiting_list'],
            (int) $info['reg_info_max_members'],
            (int) $info['reg_info_members'],
            (int) $info['reg_info_subscribers'],
            (int) $info['reg_info_waiting_status'],
            (bool) $info['ref_info_is_assigned']
        );


        // add the registration info fpr parallel groups
        // not added by ilObjGroupAccess::lookupRegistrationInfo because registration is disabled for parallel group
        if ($groupInfo->hasMaxMembers()) {
            $limits = array();
            $limits[] = $this->dic->language()->txt("mem_max_users") . $groupInfo->getMaxMembers();
            $limits[] = $this->dic->language()->txt("mem_free_places") . ': ' . $groupInfo->getFreePlaces();
            if ($groupInfo->getSubscribers() > 0) {
                $limits[] = $this->dic->language()->txt("subscribers_or_waiting_list") . ': ' . (string) ($groupInfo->getSubscribers());
            }
            $groupInfo = $groupInfo->withProperty(new ListProperty(null, implode(' &nbsp; ', $limits)));
        }

        // add other properties
        foreach (['reg_info_list_prop_status'] as $key) {
            if (isset($info[$key])) {
                $groupInfo = $groupInfo->withProperty(new ListProperty($info[$key]['property'], $info[$key]['value']));
            }
        }

        // optionally add the participants and waiting list
        if ($with_participants) {
            $groupInfo = $groupInfo->withParticipants(ilGroupParticipants::getInstance($ref_id));
        }
        if ($with_waiting_list) {
            $groupInfo = $groupInfo->withWaitingList(new ilGroupWaitingList($obj_id));
        }

        return $groupInfo;
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
            $group = $this->getParallelGroupInfo($group_ref_id, $with_participants, $with_waiting_list);
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
     * Get the object ids of all parallel groups of an ilias course (self or enclosed groups)
     * (Needed to query for the selected module ids of participants)
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
     * eventually transmit a change of the maximum members
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
            $time = $this->dic->fau()->tools()->convert()->unixToDbTimestamp(time());
            $stagingRepo->saveChange(new StudonChange(
                null,
                null,
                $course_id,
                null,
                StudonChange::TYPE_ATTENDEE_MAXIMUM_CHANGED,
                $maximum,
                $time,
                $time,
                null
            ));


            // prevent multiple change entries for further updates
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
}