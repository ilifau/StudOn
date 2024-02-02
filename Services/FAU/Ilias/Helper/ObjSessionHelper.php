<?php

namespace FAU\Ilias\Helper;

/**
 * trait for providing additional ilObjSession methods
 */
trait ObjSessionHelper 
{
    // fau: fairSub#81 - fake getSubscriptionFair()
    public function getSubscriptionFair()
    {
        return 0;
    }
    // fau.

}