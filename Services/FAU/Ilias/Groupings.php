<?php

namespace FAU\ILIAS;

use ILIAS\DI\Container;
use ilObjCourseGrouping;
use ilObject;

/**
 * Functions to manage groupings of container objects for membership limitation
 */
class Groupings
{
    protected Container $dic;

    /**
     * Constructor
     */
    public function __construct(Container $dic)
    {
        $this->dic = $dic;
    }

    /**
     * Find a grouping that includes all given container references
     */
    public function findCommonGrouping(array $ref_ids) : ?ilObjCourseGrouping
    {
        foreach ($ref_ids as $ref_id) {
            $obj_id = ilObject::_lookupObjId($ref_id);
            foreach(ilObjCourseGrouping::_getGroupings($obj_id) as $grouping_id) {
                $grouping = new ilObjCourseGrouping($grouping_id);
                $targets = [];
                foreach ($grouping->getAssignedItems() as $condition) {
                    $targets[] = $condition['target_ref_id'];
                }
                if (empty(array_diff($ref_ids, $targets))) {
                    // all ref_ids are in the targets => this grouping fits
                    return $grouping;
                }
            }
        }
        // no object has a grouping that includes all others
        return null;
    }


    /**
     * Add a common grouping for some container references
     */
    public function createCommonGrouping(array $ref_ids, string $title) : ?ilObjCourseGrouping
    {
        $grouping = new ilObjCourseGrouping();
        if (empty($ref_ids)) {
            return null;
        }
        $ref_id = $ref_ids[0];
        $obj_id = ilObject::_lookupObjId($ref_id);
        $type = ilObject::_lookupType($obj_id);

        $grouping->setContainerRefId($ref_id);
        $grouping->setContainerObjId($obj_id);
        $grouping->setContainerType($type);
        $grouping->setTitle($title);
        $grouping->setUniqueField('login');
        $grouping->create($ref_id, $obj_id);

        foreach($ref_ids as $ref_id) {
            $obj_id = ilObject::_lookupObjId($ref_id);
            $grouping->assign($ref_id, $obj_id);
        }

        return $grouping;
    }

    /**
     * Add a container reference to a grouping
     */
    public function addReferenceToGrouping(int $ref_id, ilObjCourseGrouping $grouping)
    {
        $obj_id = ilObject::_lookupObjId($ref_id);
        $grouping->assign($ref_id, $obj_id);
    }
}