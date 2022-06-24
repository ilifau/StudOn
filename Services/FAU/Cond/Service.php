<?php declare(strict_types=1);

namespace FAU\Cond;

use ILIAS\DI\Container;

/**
 * Service for registration restrictions and conditions
 */
class Service
{
    protected ?Container $dic;
    protected ?Repository $repository;
    protected ?HardRestrictions $hard;
    protected ?SoftConditions $soft;


    /**
     * Constructor
     */
    public function __construct(Container $dic)
    {
        $this->dic = $dic;
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
     * Get the handler for hard restrictions
     */
    public function hard() : HardRestrictions
    {
        if (!isset($this->hard)) {
            $this->hard = new HardRestrictions($this->dic);
        }
        return $this->hard;
    }

    /**
     * Get the handler for soft conditions
     */
    public function soft() : SoftConditions
    {
        if (!isset($this->soft)) {
            $this->soft = new SoftConditions($this->dic);
        }
        return $this->soft;
    }

}