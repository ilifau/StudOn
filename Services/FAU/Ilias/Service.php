<?php declare(strict_types=1);

namespace FAU\Ilias;


use FAU\SubService;
use ilContainer;
use ilParticipants;
use ilWaitingList;
use ilObjCourse;
use ilObjGroup;

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
        if(!isset($this->objects)) {
            $this->objects = new Objects($this->dic);
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

    /**
     * Get the registration object
     * (not cached because of dependencies)
     * @return CourseRegistration|GroupRegistration|null
     * @see Objects::isRegistrationHandlerSupported();
     */
    public function getRegistration(\ilObject $object, ilParticipants $participants = null, ilWaitingList $waitingList = null)
    {
        if ($object instanceof ilObjCourse) {
            return new CourseRegistration($this->dic, $object, $participants, $waitingList);
        }
        elseif ($object instanceof ilObjGroup) {
            return new GroupRegistration($this->dic, $object, $participants, $waitingList);
        }
        return null;
    }

}