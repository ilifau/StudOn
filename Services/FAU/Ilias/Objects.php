<?php

namespace FAU\Ilias;

use ILIAS\DI\Container;
use ilObject;
use FAU\Study\Data\Course;
use ilObjGroupAccess;
use FAU\Ilias\Data\ContainerInfo;
use FAU\Ilias\Data\ListProperty;
use ilWaitingList;
use ilCourseWaitingList;
use ilGroupWaitingList;

/**
 * Functions to handle with ILIAS objects
 */
class Objects
{
    protected Container $dic;

    public function __construct(Container $dic)
    {
        $this->dic = $dic;
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
     * Find parallel groups that are enclosed in the course
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
    public function getParallelGroupInfo($ref_id) : ContainerInfo
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
        if ($groupInfo->hasMemLimit()) {
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
        return $groupInfo;
    }

    /**
     * Get the infos about the parallel groups in a course or about all peer groups of a group
     * @param int $ref_id   ref_id of the course or of one parallel group
     * @return ContainerInfo[]
     */
    public function getParallelGroupsInfos($ref_id) : array
    {
        $infos = [];
        if (ilObject::_lookupType($ref_id, true) == 'grp') {
            $ref_id = (int) $this->findParentIliasCourse($ref_id);
        }
        foreach ( $this->findChildParallelGroups($ref_id) as $group_ref_id) {
            $group = $this->getParallelGroupInfo($group_ref_id);
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
     * Check if an object is a parallel group
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
        return $object instanceof \ilObjCourse || $object instanceof \ilObjGroup;
    }
}