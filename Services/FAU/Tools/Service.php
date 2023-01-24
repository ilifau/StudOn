<?php declare(strict_types=1);

namespace FAU\Tools;

use FAU\SubService;

/**
 * Tools needed for data processing
 */
class Service extends SubService
{
    protected Convert $convert;
    protected Format $format;

    /**
     * Get the functions to convert data
     */
    public function convert() : Convert
    {
        if(!isset($this->convert)) {
            $this->convert = new Convert($this->dic);
        }
        return $this->convert;
    }

    /**
     * Get the functions to format data
     */
    public function format() : Format
    {
        if(!isset($this->format)) {
            $this->format = new Format($this->dic);
        }
        return $this->format;
    }


}