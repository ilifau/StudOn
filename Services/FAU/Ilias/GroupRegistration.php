<?php

namespace FAU\Ilias;

use ilObjGroup;
use ilGroupParticipants;
use ilGroupWaitingList;
use ILIAS\DI\Container;

class GroupRegistration extends Registration
{
    /** @var ilObjGroup */
    protected  $object;

    /** @var ilGroupParticipants */
    protected  $participants;

    /** @var ilGroupWaitingList */
    protected  $waitingList;

    /**
     * @param Container $dic
     * @param ilObjGroup                   $object
     * @param ilGroupParticipants|null     $participants
     * @param ilGroupWaitingList|null      $waitingList
     */
    public function __construct(Container $dic, $object, $participants = null, $waitingList = null)
    {
       parent::__construct($dic, $object, $participants, $waitingList);

       if (!isset($this->participants)) {
           $this->participants = ilGroupParticipants::_getInstanceByObjId($this->object->getId());
       }
       if (!isset($this->waitingList)) {
           $this->waitingList = new ilGroupWaitingList($this->object->getId());
       }
    }




}