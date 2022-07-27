<?php declare(strict_types=1);

namespace FAU\Tools;

use FAU\SubService;

/**
 * Tools needed for data processing
 */
class Service extends SubService
{
    protected Convert $convert;

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