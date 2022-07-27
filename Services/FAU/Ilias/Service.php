<?php declare(strict_types=1);

namespace FAU\ILIAS;


use FAU\SubService;

/**
 * Tools needed for data processing
 */
class Service extends SubService
{
    protected Repository $repository;
    protected Objects $objects;
    protected Groupings $groupings;

    /**
     * Get the tools repository
     */
    public function repo() : Repository
    {
        if(!isset($this->repository)) {
            $this->repository = new Repository($this->dic->database(), $this->dic->logger()->fau());
        }
        return $this->repository;
    }

    /**
     * Get the functions to handle ILIAS objects
     */
    public function objects() : Objects
    {
        if(!isset($this->ilias)) {
            $this->ilias = new Objects($this->dic);
        }
        return $this->objects;
    }

    /**
     * Get the functions to handle groupings of ilias courses or groups
     */
    public function groupings() : Groupings
    {
        if (!isset($this->groupings)) {
            $this->groupings = new Groupings($this->dic);
        }
        return $this->groupings;
    }


}