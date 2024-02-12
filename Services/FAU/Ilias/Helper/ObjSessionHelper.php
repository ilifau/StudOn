<?php

namespace FAU\Ilias\Helper;

/**
 * trait for providing additional ilObjSession methods
 */
trait ObjSessionHelper 
{
    // fau: objectSub - class variable
    protected $reg_ref_id = null;
    // fau.

    // fau: fairSub#81 - fake getSubscriptionFair()
    public function getSubscriptionFair()
    {
        return 0;
    }
    // fau.

    // fau: objectSub - getter / setter
    public function getRegistrationRefId()
    {
        return $this->reg_ref_id;
    }
    public function setRegistrationRefId($a_ref_id)
    {
        $this->reg_ref_id = $a_ref_id;
    }
    // fau.    
}