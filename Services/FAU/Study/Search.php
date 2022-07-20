<?php

namespace FAU\Study;

use ILIAS\DI\Container;
use FAU\Study\Data\SearchCondition;
use FAU\Study\Data\Event;
use FAU\Study\Data\SearchResultEvent;
use ilObjCourseListGUI;
use ilObject;

class Search
{
    protected Container $dic;
    protected Service $service;
    protected Repository $repo;
    protected SearchCondition $condition;

    protected int $default_limit = 100;
    protected int $default_offset = 0;


    /**
     * Constructor
     */
    public function __construct(Container $dic)
    {
        $this->dic = $dic;
        $this->service = $dic->fau()->study();
        $this->repo = $dic->fau()->study()->repo();
    }

    /**
     * Get the search
     * @return SearchCondition
     */
    public function getCondition() : SearchCondition
    {
        if (!isset($this->condition)) {
            $this->condition = $this->dic->fau()->tools()->preferences()->getSearchCondition();
        }
        return $this->condition;
    }

    /**
     * Set the search condition
     * @param SearchCondition $condition
     */
    public function setCondition(SearchCondition $condition)
    {
        $this->condition = $condition;
        $this->dic->fau()->tools()->preferences()->setSearchCondition($condition);
    }

    /**
     * Get a list of ilias courses
     * @return SearchResultEvent[]
     */
    public function getEventList() : array
    {
        $this->setCondition($this->getProcessedCondition($this->getCondition()));

        $list = [];
        $result = $this->repo->searchEvents($this->getCondition());
        foreach ($result as $event) {
            foreach ($event->getCourses() as $course) {
                $type = ilObject::_lookupType($course->getObjId());
                if ($type == 'crs') {
                    $ref_id = $course->getRefId();
                    $obj_id = $course->getObjId();
                }
                else {
                    $ref_id = $this->dic->fau()->sync()->trees()->findParentIliasCourse($course->getRefId());
                    $obj_id = ilObject::_lookupObjId($ref_id);
                }

                if (isset($ref_id)) {
                    $event = $event->withIliasRefId($ref_id)
                       ->withIliasTitle(ilObject::_lookupTitle($obj_id))
                       ->withIliasDescription(ilObject::_lookupDescription($obj_id))
                       ->withVisible($this->dic->access()->checkAccess('visible', '', $ref_id))
                       ->withMoveable($this->dic->access()->checkAccess('delete', 'cut', $ref_id));
                }

                // provide sort key
                $list[($event->getIliasTitle() ?? $event->getEventTitle()) . $event->getEventId()] = $event;
            }
        }

        ksort($list, SORT_NATURAL);
        return array_values($list);
    }

    /**
     * Get a condition with added process values
     * @param SearchCondition $condition
     * @return SearchCondition
     */
    protected function getProcessedCondition(SearchCondition $condition) : SearchCondition
    {
        if (!empty($condition->getIliasRefId())) {
            /** @noinspection PhpParamsInspection */
            $node = $this->dic->repositoryTree()->getNodeTreeData($condition->getIliasRefId());
            $condition = $condition->withIliasPath($node['path']);
        }
        if (empty($condition->getLimit())) {
            $condition = $condition->withLimit($this->default_limit);
        }
        if (empty($condition->getOffset())) {
            $condition = $condition->withOffset($this->default_offset);
        }

        return $condition;
    }
}