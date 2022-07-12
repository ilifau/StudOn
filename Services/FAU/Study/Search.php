<?php

namespace FAU\Study;

use ILIAS\DI\Container;
use FAU\Study\Data\SearchCondition;
use FAU\Study\Data\Event;

class Search
{
    protected Container $dic;
    protected Service $service;
    protected Repository $repo;

    protected SearchCondition $condition;

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
            $this->condition = $this->dic->fau()->preferences()->getSearchCondition();
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
        $this->dic->fau()->preferences()->setSearchCondition($condition);
    }

    /**
     * Get a list of events
     * @return Event[]
     */
    public function getEvents() : array
    {

    }
}