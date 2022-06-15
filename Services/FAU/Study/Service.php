<?php declare(strict_types=1);

namespace FAU\Study;

use ILIAS\DI\Container;

/**
 * Service for study related data
 */
class Service
{
    protected Container $dic;
    protected Repository $repository;
    protected Matching $matching;
    protected Gui $gui;


    /**
     * Constructor
     */
    public function __construct(Container $dic)
    {
        $this->dic = $dic;
    }

    /**
     * Get the handler for date scheme migrations (no caching needed)
     */
    public function migration() : Migration
    {
        return new Migration($this->dic->database());
    }


    /**
     * Get the repository for user data
     */
    public function repo() : Repository
    {
        if(!isset($this->repository)) {
            $this->repository = new Repository($this->dic->database(), $this->dic->logger()->fau());
        }
        return $this->repository;
    }

    /**
     * Get the matching functions
     */
    public function matching() : Matching
    {
        if(!isset($this->matching)) {
            $this->matching = new Matching($this->dic);
        }
        return $this->matching;
    }


    /**
     * Get the GUI Handler
     */
    public function gui() : Gui
    {
        if(!isset($this->gui)) {
            $this->gui = new Gui($this->dic);
        }
        return $this->gui;
    }


}