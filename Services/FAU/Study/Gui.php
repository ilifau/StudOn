<?php

namespace FAU\Study;

use ILIAS\DI\Container;

class Gui
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



    public function getStudyModuleSelectionForSearch(int $user_id)
    {

    }

    public function getStudyModuleSelectionForEvent(int $user_id, int $event_id)
    {

    }

}