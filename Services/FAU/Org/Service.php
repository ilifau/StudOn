<?php declare(strict_types=1);

namespace FAU\Org;

use ILIAS\DI\Container;

/**
 * Service for user related data
 */
class Service
{
    protected Container $dic;
    protected Repository $repository;


    /**
     * Constructor
     */
    public function __construct(Container $dic)
    {
        $this->dic = $dic;
    }

    /**
     * Get the Migration Handler
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

}