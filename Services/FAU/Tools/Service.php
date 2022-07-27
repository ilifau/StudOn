<?php declare(strict_types=1);

namespace FAU\Tools;

use Throwable;
use FAU\SubService;

/**
 * Tools needed for data processing
 */
class Service extends SubService
{
    protected Repository $repository;
    protected Ilias $ilias;
    protected Convert $convert;
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
    public function ilias() : Ilias
    {
        if(!isset($this->ilias)) {
            $this->ilias = new Ilias($this->dic);
        }
        return $this->ilias;
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


    /**
     * Get the functions to convert data
     */
    public function convert() : Convert
    {
        if(!isset($this->convert)) {
            $this->con = new Convert($this->dic);
        }
        return $this->convert;
    }


}