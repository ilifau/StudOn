<?php

namespace FAU\Ilias\Helper;
use FAU\Service;
/**
 * trait for providing additional ilInitialisation methods
 */
trait InitialisationHelper
{
   // fau: fauService - new function to init the service factory
    /**
     * @param \ILIAS\DI\Container $c
     */
    private static function initFau(\ILIAS\DI\Container $c)
    {
        $c["fau"] = function ($c) {
            return new Service($c);
        };
    }
    // fau.
}
   
   
