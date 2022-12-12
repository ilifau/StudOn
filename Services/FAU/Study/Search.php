<?php

namespace FAU\Study;

use ILIAS\DI\Container;
use FAU\Study\Data\SearchCondition;
use FAU\Study\Data\SearchResultEvent;
use ilObject;

class Search
{
    protected Container $dic;
    protected Service $service;
    protected Repository $repo;
    protected SearchCondition $condition;

    protected int $default_limit = 100;
    protected $cache_seconds = 600;

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
     * Get the saved search condition
     */
    public function getCondition() : SearchCondition
    {
        if (!isset($this->condition)) {
            $this->condition = $this->dic->fau()->tools()->preferences()->getSearchCondition();
        }
        return $this->condition;
    }

    /**
     * Set and save the search condition
     */
    public function setCondition(SearchCondition $condition)
    {
        $this->condition = $this->getProcessedCondition($condition);
        $this->dic->fau()->tools()->preferences()->setSearchCondition($this->condition);
    }

    /**
     * Get the list of events
     *
     * Calls the repo to search for events
     * Aggregates found ilias groups to their parent course
     * Stores the number of list entries in the search condition for paging
     * Returns a slice of the found list according to the paging values in the search condition
     * (Note: paging can't be done with LIMIT in the repo query because the group to course aggregation is done here)
     *
     * @return SearchResultEvent[]
     */
    public function getEventList(?SearchCondition $a_condition = null) : array
    {
        $condition = $a_condition ?? $this->getCondition();
        $cache = $this->getCache();

        // try to get the list from the cache
        $list = null;
        try {
            if (!empty($entry = $cache->getEntry($condition->getSignature()))) {
                $list = unserialize($entry);
            }
        }
        catch (\Exception $e) {
            // ignore
        }

        // generate the list, if not taken from cache
        if (!is_array($list)) {
            $list = [];
            $result = $this->repo->searchEvents($condition);
            foreach ($result as $event) {

                if (empty($event->getObjects())) {
                    // add event without ilias object
                    $list[$event->getSortKey()] = $event;
                }

                foreach ($event->getObjects() as $object) {

                    $type = ilObject::_lookupType($object->getObjId());
                    if ($type == 'crs') {
                        $ref_id = $object->getRefId();
                        $obj_id = $object->getObjId();
                        $event = $event->withIliasRefId($ref_id)->withIliasObjId($obj_id);
                        $list[$event->getSortKey()] = $event;
                    }
                    elseif ($type == 'grp') {
                        $ref_id = $this->dic->fau()->ilias()->objects()->findParentIliasCourse($object->getRefId());
                        $obj_id = ilObject::_lookupObjId($ref_id);
                        $event = $event->withIliasRefId($ref_id)->withIliasObjId($obj_id)->withNested(true);
                        $list[$event->getSortKey()] = $event;
                        break; // only add entry for the parent
                    }
                }
            }

            // sort the list and get only the entries for the current page
            ksort($list, SORT_NATURAL);

            // store whole result in cache
            try {
                $cache->storeEntry($condition->getSignature(), serialize($list));
            }
            catch (\Exception $e) {
                // ignore
            }
        }

        // add the total number of list entries
        $condition = $condition->withFound(count($list));
        // save the condition in preferences if it was taken from there
        if (!isset($a_condition)) {
            $this->setCondition($condition);
        }

        // get only the entries for the current page
        if (!empty($condition->getLimit())) {
            $list = array_slice($list, $condition->getOffset(), $condition->getLimit());
        }

        // do further lookups only for the page entries
        foreach ($list as $index => $event) {
            $list[$index] = $event
                ->withIliasTitle(ilObject::_lookupTitle($event->getIliasObjId()))
                ->withIliasDescription(ilObject::_lookupDescription($event->getIliasObjId()))
                ->withVisible($this->dic->access()->checkAccess('visible', '', $event->getIliasRefId()))
                ->withMoveable($this->dic->access()->checkAccess('delete', 'cut', $event->getIliasRefId()));
        }

        return array_values($list);
    }

    /**
     * Get the Search cache
     * @return \ilCache
     */
    protected function getCache() :\ilCache
    {
        $cache = new \ilCache('Services/FAU', "Search", true);
        $cache->setExpiresAfter($this->cache_seconds);
        return $cache;
    }


    /**
     * Clear the search cache for a condition
     */
    public function clearCacheForCondition() {
        $this->getCache()->deleteEntry($this->getCondition()->getSignature());
    }

    /**
     * Get a condition with added values
     */
    public function getProcessedCondition(SearchCondition $condition) : SearchCondition
    {
        if (!empty($condition->getIliasRefId()) && empty($condition->getIliasPath())) {
            /** @noinspection PhpParamsInspection */
            $node = $this->dic->repositoryTree()->getNodeTreeData($condition->getIliasRefId());
            $condition = $condition->withIliasPath($node['path']);
        }
        if (empty($condition->getLimit())) {
            $condition = $condition->withLimit($this->default_limit);
        }
        return $condition;
    }
}