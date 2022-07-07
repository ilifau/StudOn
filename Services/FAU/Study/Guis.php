<?php

namespace FAU\Study;

use ILIAS\DI\Container;
use FAU\Study\GUI\Search;

class Guis
{
    protected Container $dic;
    protected Service $service;
    protected Repository $repo;

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
     * Get the Search GUI
     */
    public function search(): Search
    {
        return new Search();
    }


    public function getStudyModuleSelectionForSearch(int $user_id)
    {

    }

    public function getStudyModuleSelectionForEvent(int $user_id, int $event_id)
    {

    }

}